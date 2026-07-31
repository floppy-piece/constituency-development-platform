<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConstituencyRequest extends Model
{
    use HasFactory;

    protected $table = 'requests';
    protected $primaryKey = 'request_id';

    protected $fillable = [
        'user_id',
        'mp_id',
        'ward_id',
        'cluster_id',
        'raw_message',
        'content',
        'upload_file_path',
        'file_type',
        'source_channel',
        'detected_language',
        'equity_flag',
        'equity_reasons',
        'equity_boost',
        'urgency',
        'urgency_score',
        'category',
        'primary_topic',
        'latitude',
        'longitude',
        'is_within_constituency',
        'similarity_hash',
        'similar_count',
        'cluster_ward_ids',
        'status',
        'resolved_at',
        'verification_status',
        'verification_requested_at',
        'verified_at',
        'verification_note',
        'verification_file_path',
        'confidence',
        'evaluation_thoughts',
        'suggested_fix',
        'priority_score',
        'priority_factors',
        'estimated_cost_kes',
        'cost_source',
        'cost_rationale',
        'mp_priority_rank',
        'override_reason',
        'overridden_by',
        'overridden_at',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'is_within_constituency' => 'boolean',
        'confidence' => 'float',
        'urgency_score' => 'integer',
        'similar_count' => 'integer',
        'cluster_ward_ids' => 'array',
        'resolved_at' => 'datetime',
        'verification_requested_at' => 'datetime',
        'verified_at' => 'datetime',
        'equity_flag' => 'boolean',
        'equity_reasons' => 'array',
        'equity_boost' => 'integer',
        'priority_score' => 'float',
        'priority_factors' => 'array',
        'estimated_cost_kes' => 'integer',
        'mp_priority_rank' => 'integer',
        'overridden_at' => 'datetime',
    ];

    public const STATUS_PENDING_REVIEW = 'pending_review';
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_RESOLVED = 'resolved';

    public const CHANNEL_TELEGRAM = 'telegram';
    public const CHANNEL_WHATSAPP = 'whatsapp';

    public const VERIFICATION_PENDING = 'pending';
    public const VERIFICATION_CONFIRMED = 'confirmed';
    public const VERIFICATION_DISPUTED = 'disputed';
    public const VERIFICATION_EXPIRED = 'expired';

    public const STATUSES = [
        self::STATUS_PENDING_REVIEW,
        self::STATUS_PENDING,
        self::STATUS_IN_PROGRESS,
        self::STATUS_RESOLVED,
    ];

    public const CONFIDENCE_THRESHOLD = 0.70;

    public static function statusFromConfidence(float $confidence): string
    {
        return $confidence < self::CONFIDENCE_THRESHOLD
            ? self::STATUS_PENDING_REVIEW
            : self::STATUS_PENDING;
    }

    public static function isValidStatus(string $status): bool
    {
        return in_array($status, self::STATUSES, true);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function mp()
    {
        return $this->belongsTo(Mp::class, 'mp_id', 'mp_id');
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(ConstituencyFacility::class, 'facility_id', 'facility_id');
    }

    public function ward()
    {
        return $this->belongsTo(Ward::class, 'ward_id', 'ward_id');
    }

    public function cluster(): BelongsTo
    {
        return $this->belongsTo(IssueCluster::class, 'cluster_id', 'cluster_id');
    }
}
