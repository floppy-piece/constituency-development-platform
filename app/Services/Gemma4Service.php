<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class Gemma4Service
{
    protected string $apiKey;
    protected string $endpoint;
    protected string $model;

    public function __construct()
    {
        $this->apiKey = config('services.gemma.api_key', '');
        $this->model = config('services.gemma.model', 'gemma-4-26b-a4b-it');

        $this->endpoint = config(
            'services.gemma.endpoint',
            'https://generativelanguage.googleapis.com/v1beta/models'
        );
    }

    /**
     * Process constituent request data including text and optional media attachments
     * (Images, Audio, Video, or PDFs).
     */
    public function processRequestData(
        ?string $text,
        ?string $filePath,
        string $fileType = '',
        bool $isInConstituency = true,
        $recentRequests = []
    ): array {
        if (empty($this->apiKey)) {
            Log::error('Gemma 4 API Key is missing from config.');
            return $this->fallbackResponse($text);
        }

        // Format recent requests safely
        $existingRequestsText = collect($recentRequests)->map(function ($req) {
            $summary = $req->content ?? $req->raw_message ?? 'No text';
            $id = $req->request_id ?? $req->getKey();

            return "ID: {$id} | Summary: {$summary}";
        })->implode("\n");

        if (empty($existingRequestsText)) {
            $existingRequestsText = 'None';
        }

        // System Prompt instructing Gemma 4 on text, audio, video, image, and document analysis
        $systemPrompt = "You are a JSON generator and constituency advisor for Kenya.\n"
            . "Task Instructions:\n"
            . "1. Translate Swahili/Sheng/informal text to clear, professional English.\n"
            . "2. IF A MEDIA ATTACHMENT IS PRESENT (Image, Audio, Video, or PDF Document):\n"
            . "   - Carefully inspect/analyze the visual or audio content.\n"
            . "   - Determine what physical infrastructure issue or public problem is shown or described (e.g., road potholes, water bursts, broken school facilities, damaged bridges).\n"
            . "3. Include relevant findings directly in `translated_summary` (e.g. 'Citizen reports water pipe leak. Attached photo shows major burst flooding a road near a school.').\n"
            . "4. Rate urgency strictly as 'low', 'medium', or 'high' based on combined text and media severity based on every aspect:cost, period of completion, effect if not solved immediately, and all the other aspects that is involved and return the process of requests thoughts and suggest possible fix for the solution.\n"
            . "5. Compare the submission to existing requests. If it matches an existing issue, return that integer ID in 'matched_request_id'. Otherwise return null.\n\n"
            . "EXISTING REQUESTS:\n"
            . "{$existingRequestsText}";

        $userContent = "User Submission: " . ($text ?: "No text provided (Attachment provided).");

        // 1. Build initial text part
        $parts = [
            ['text' => $userContent],
        ];

        // 2. Attach Multimodal File (Image, Video, Audio, or PDF) if valid path exists
        if ($filePath && file_exists($filePath)) {
            $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';

            if ($this->isSupportedMimeType($mimeType)) {
                $base64Data = base64_encode(file_get_contents($filePath));

                $parts[] = [
                    'inline_data' => [
                        'mime_type' => $mimeType,
                        'data'      => $base64Data,
                    ],
                ];

                Log::info("Gemma 4: Attached image payload ({$mimeType}) for analysis.");
            } else {
                Log::warning("Gemma 4: Unsupported attachment MIME type '{$mimeType}'. Proceeding with text only.");
            }
        }

        $url = rtrim($this->endpoint, '/') . "/{$this->model}:generateContent?key={$this->apiKey}";

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($url, [
                'system_instruction' => [
                    'parts' => [
                        ['text' => $systemPrompt],
                    ],
                ],
                'contents' => [
                    [
                        'role'  => 'user',
                        'parts' => $parts,
                    ],
                ],
                'generationConfig' => [
                    'response_mime_type' => 'application/json',
                    'response_schema'    => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'translated_summary' => ['type' => 'STRING'],
                            'urgency'            => [
                                'type' => 'STRING',
                                'enum' => ['low', 'medium', 'high']
                            ],
                            'matched_request_id' => [
                                'type'     => 'INTEGER',
                                'nullable' => true,
                            ],
                        ],
                        'required' => ['translated_summary', 'urgency'],
                    ],
                    'temperature' => 0.1,
                ],
            ]);

            if (! $response->successful()) {
                Log::error("Gemma 4 API HTTP Error ({$response->status()}): " . $response->body());
                return $this->fallbackResponse($text);
            }

            $responseData = $response->json();
            $rawContent = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? null;

            Log::info('Gemma 4 Vision API Success - AI Response Generated', [
                'raw_content'           => $rawContent,
                'full_response_payload' => $responseData,
            ]);

            if (! $rawContent) {
                return $this->fallbackResponse($text);
            }

            $parsed = $this->extractJson($rawContent);

            if (! is_array($parsed)) {
                Log::error('Failed to parse Gemma 4 JSON output', ['raw' => $rawContent]);
                return $this->fallbackResponse($text);
            }

            return [
                'translated_summary' => $parsed['translated_summary'] ?? $text ?? 'Issue reported by citizen with image.',
                'urgency'            => strtolower($parsed['urgency'] ?? 'low'),
                'matched_request_id' => is_numeric($parsed['matched_request_id'] ?? null)
                    ? (int) $parsed['matched_request_id']
                    : null,
            ];

        } catch (Throwable $e) {
            Log::error('Gemma 4 Exception: ' . $e->getMessage());
            return $this->fallbackResponse($text);
        }
    }

    /**
     * Use Gemma 4 as the AI decision-making engine to evaluate and prioritize competing proposals.
     */
    public function evaluateAndCompareProposals(array $proposalA, array $proposalB): array
    {
        if (empty($this->apiKey)) {
            return $this->fallbackEvaluation($proposalA, $proposalB, 'Missing API Key.');
        }

        $systemPrompt = "You are an expert AI Public Sector Planning & Infrastructure Policy Advisor for Kenya.\n"
            . "Your task is to analyze and compare two competing constituent proposal requests submitted to a Member of Parliament (MP).\n"
            . "Even if both appear urgent, you MUST rigorously differentiate them and determine a definitive higher-priority winner.\n"
            . "Evaluate both proposals comprehensively using all available data points and contextual metrics, including:\n"
            . "1. Citizen Demand & Volume of complaints/requests.\n"
            . "2. Physical Infrastructure Deficit (Enrollment vs Capacity overcrowding, travel distance).\n"
            . "3. Demographic Vulnerability & Poverty Index Score.\n"
            . "4. Estimated Financial/Budgetary impact and implementation Period.\n"
            . "5. Critical consequences and compounding risks if left unaddressed immediately.\n"
            . "6. Alignment with County Integrated Development Plans (CIDP).\n"
            . "Provide a concrete, actionable `suggested_fix` for how the constituency development team should resolve the winning proposal.";

        $payload = [
            'proposal_a' => $proposalA,
            'proposal_b' => $proposalB,
        ];

        $url = rtrim($this->endpoint, '/') . "/{$this->model}:generateContent?key={$this->apiKey}";

        try {
            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                ->timeout(20)
                ->connectTimeout(10)
                ->post($url, [
                    'system_instruction' => [
                        'parts' => [['text' => $systemPrompt]],
                    ],
                    'contents' => [
                        ['role' => 'user', 'parts' => [['text' => json_encode($payload)]]],
                    ],
                    'generationConfig' => [
                        'response_mime_type' => 'application/json',
                        'response_schema'    => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'recommended_winner' => [
                                    'type' => 'STRING',
                                    'enum' => ['proposal_a', 'proposal_b']
                                ],
                                'score_proposal_a'   => ['type' => 'INTEGER'],
                                'score_proposal_b'   => ['type' => 'INTEGER'],
                                'ai_reasoning'       => ['type' => 'STRING'],
                                'trade_off_analysis' => ['type' => 'STRING'],
                                'suggested_fix'      => ['type' => 'STRING'],
                                'confidence_score'   => ['type' => 'INTEGER'],
                            ],
                            'required' => [
                                'recommended_winner', 
                                'score_proposal_a', 
                                'score_proposal_b', 
                                'ai_reasoning', 
                                'trade_off_analysis', 
                                'suggested_fix', 
                                'confidence_score'
                            ],
                        ],
                        'temperature' => 0.1,
                    ],
                ]);

            if ($response->successful()) {
                $rawText = $response->json('candidates.0.content.parts.0.text') ?? '{}';
                $parsed = $this->extractJson($rawText);

                if (is_array($parsed)) {
                    return [
                        'recommended_winner' => $parsed['recommended_winner'] ?? 'proposal_a',
                        'score_proposal_a'   => (int) ($parsed['score_proposal_a'] ?? 50),
                        'score_proposal_b'   => (int) ($parsed['score_proposal_b'] ?? 50),
                        'ai_reasoning'       => $parsed['ai_reasoning'] ?? 'Decision evaluated by Gemma 4.',
                        'trade_off_analysis' => $parsed['trade_off_analysis'] ?? 'N/A',
                        'suggested_fix'      => $parsed['suggested_fix'] ?? 'N/A',
                        'confidence_score'   => (int) ($parsed['confidence_score'] ?? 80),
                    ];
                }
            } else {
                Log::error("Gemma 4 Proposal Comparison Error ({$response->status()}): " . $response->body());
            }
        } catch (Throwable $e) {
            Log::warning("Gemma 4 API Timeout/Exception encountered: " . $e->getMessage() . ". Falling back to algorithmic score evaluation.");
        }

        // Graceful algorithmic fallback when timeout occurs
        return $this->fallbackEvaluation($proposalA, $proposalB, 'API Timeout / Network unreachable. Evaluated via base matrix scoring.');
    }

    private function fallbackEvaluation(array $proposalA, array $proposalB, string $reason): array
    {
        $scoreA = $proposalA['base_score'] ?? 50;
        $scoreB = $proposalB['base_score'] ?? 50;
        $winner = $scoreA >= $scoreB ? 'proposal_a' : 'proposal_b';

        return [
            'recommended_winner' => $winner,
            'score_proposal_a'   => (int) $scoreA,
            'score_proposal_b'   => (int) $scoreB,
            'ai_reasoning'       => "Fallback Mode: {$reason} Winner determined by weighted infrastructure and citizen demand metrics.",
            'trade_off_analysis' => 'Proposal A balances broader citizen report volume against Proposal B metrics.',
            'suggested_fix'      => 'Deploy phased resource allocation prioritizing the facility with higher overcrowding ratios and CIDP alignment.',
            'confidence_score'   => 60,
        ];
    }

    /**
     * Translate view text/content to a specific Kenyan local language using Gemma 4.
     */
    public function translateContent(string|array $content, string $targetLanguage): string|array
    {
        if (empty($content)) {
            return $content ?? '';
        }
    
        if (empty($this->apiKey) || strtolower($targetLanguage) === 'en') {
            return $content;
        }
    
        $cacheKey = 'gemma_trans_' . md5(is_array($content) ? json_encode($content) : $content) . '_' . strtolower($targetLanguage);
    
        return Cache::rememberForever($cacheKey, function () use ($content, $targetLanguage) {
            $isInputArray = is_array($content);
            $textToTranslate = $isInputArray ? json_encode($content, JSON_UNESCAPED_UNICODE) : $content;
    
            $systemPrompt = "You are a direct translation engine for a Kenyan web app.\n"
                . "Target Language: {$targetLanguage}.\n"
                . "Output a JSON object with a single key: \"translated\".\n"
                . "Do not include explanations or markdown fences.";
    
            $url = rtrim($this->endpoint, '/') . "/{$this->model}:generateContent?key={$this->apiKey}";
    
            try {
                $response = Http::withHeaders(['Content-Type' => 'application/json'])
                    ->timeout(15)
                    ->post($url, [
                        'system_instruction' => [
                            'parts' => [['text' => $systemPrompt]],
                        ],
                        'contents' => [
                            ['role' => 'user', 'parts' => [['text' => $textToTranslate]]],
                        ],
                        'generationConfig' => [
                            'temperature' => 0.1,
                            'responseMimeType' => 'application/json',
                        ],
                    ]);
    
                if ($response->successful()) {
                    $rawResponse = $response->json('candidates.0.content.parts.0.text') ?? '';
    
                    $cleanText = $textToTranslate;
    
                    // 1. Extract the JSON object substring { ... } ignoring chain-of-thought prefix
                    if (preg_match('/\{[\s\S]*\}/', $rawResponse, $matches)) {
                        $json = json_decode($matches[0], true);
                        if (isset($json['translated'])) {
                            $cleanText = $json['translated'];
                        }
                    } else {
                        // Fallback: If no JSON structure is found, strip backticks/bullets and grab the last line
                        $lines = array_values(array_filter(array_map('trim', explode("\n", $rawResponse))));
                        if (!empty($lines)) {
                            $lastLine = end($lines);
                            $cleanText = preg_replace('/^[\*\-\s"`\']+|[\*\-\s"`\']+$/', '', $lastLine);
                        }
                    }
    
                    Log::info("Gemma 4 Translation Success [{$targetLanguage}]", [
                        'translated' => $cleanText
                    ]);
    
                    return $isInputArray ? (json_decode($cleanText, true) ?? $content) : trim($cleanText);
                }
            } catch (\Throwable $e) {
                Log::error("Gemma 4 Translation Error: " . $e->getMessage());
            }
    
            return $content;
        });
    }

    /**
     * Check if the attachment MIME type is supported by Gemini models inline payload.
     */
    private function isSupportedMimeType(string $mimeType): bool
    {
        return str_starts_with($mimeType, 'image/')
            || str_starts_with($mimeType, 'audio/')
            || str_starts_with($mimeType, 'video/')
            || $mimeType === 'application/pdf'
            || $mimeType === 'text/plain';
    }

    /**
     * Robust extraction method handling valid JSON, markdown codeblocks,
     * and fallback regex parsing for pseudo-JSON bullet outputs.
     */
    private function extractJson(string $text): ?array
    {
        // 1. Direct JSON parse
        $direct = json_decode($text, true);
        if (is_array($direct)) {
            return $direct;
        }

        // 2. Clean out code fences
        $cleaned = preg_replace('/```(?:json)?\s*([\s\S]*?)\s*```/', '$1', $text);
        $cleanedResult = json_decode(trim($cleaned), true);
        if (is_array($cleanedResult)) {
            return $cleanedResult;
        }

        // 3. Locate valid JSON braces {...} inside raw text
        if (preg_match_all('/\{[\s\S]*?\}/', $text, $matches)) {
            foreach (array_reverse($matches[0]) as $jsonCandidate) {
                $decoded = json_decode($jsonCandidate, true);
                if (is_array($decoded) && (isset($decoded['translated_summary']) || isset($decoded['recommended_winner']))) {
                    return $decoded;
                }
            }
        }

        // 4. Fallback parser: Extract key-value pairs formatted as bullet points (* `key`: "value")
        $extracted = [];

        if (preg_match('/translated_summary[`\s]*:[\s]*"([^"]+)"/i', $text, $matchSummary)) {
            $extracted['translated_summary'] = $matchSummary[1];
        }

        if (preg_match('/urgency[`\s]*:[\s]*"?(low|medium|high)"?/i', $text, $matchUrgency)) {
            $extracted['urgency'] = strtolower($matchUrgency[1]);
        }

        if (preg_match('/matched_request_id[`\s]*:[\s]*(null|\d+)/i', $text, $matchId)) {
            $extracted['matched_request_id'] = $matchId[1] === 'null' ? null : (int) $matchId[1];
        }

        if (! empty($extracted) && isset($extracted['translated_summary'])) {
            return $extracted;
        }

        return null;
    }

    private function fallbackResponse(?string $text): array
    {
        return [
            'translated_summary' => $text ?: 'Issue reported by citizen.',
            'urgency'            => 'low',
            'matched_request_id' => null,
        ];
    }
}