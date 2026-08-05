<x-layout title="Proposal Feasibility Matrix">
    <div x-data="matrixPage()" x-init="initMatrix()" class="space-y-6">
        
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-stone-400/40 pb-4">
            <div>
                <h1 class="text-2xl font-bold text-stone-900 font-typewriter tracking-wide uppercase" style="color:white;">Proposal Comparison & Feasibility Matrix</h1>
                <p class="text-xs text-stone-700 font-typewriter mt-1" style="color:white;">Weigh competing constituent requests against real infrastructure metrics, demographic poverty indexes, and CIDP plans.</p>
            </div>
        </div>

        <!-- Selection Controls -->
        <div class="torn-card p-6 space-y-4 border border-stone-300">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 font-typewriter">
                <!-- Proposal A Selector -->
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-stone-700 mb-2">Select Proposal A</label>
                    <select x-model="proposalA" class="w-full bg-white border border-stone-300 text-stone-900 text-xs rounded px-3 py-2 shadow-xs focus:outline-none focus:border-stone-500">
                        <option value="">-- Choose First Proposal --</option>
                        <template x-for="req in availableRequests" :key="req.id">
                            <option :value="req.id" x-text="req.category + ': ' + req.content.substring(0, 50) + '...'"></option>
                        </template>
                    </select>
                </div>

                <!-- Proposal B Selector -->
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-stone-700 mb-2">Select Proposal B</label>
                    <select x-model="proposalB" class="w-full bg-white border border-stone-300 text-stone-900 text-xs rounded px-3 py-2 shadow-xs focus:outline-none focus:border-stone-500">
                        <option value="">-- Choose Second Proposal --</option>
                        <template x-for="req in availableRequests" :key="req.id">
                            <option :value="req.id" x-text="req.category + ': ' + req.content.substring(0, 50) + '...'"></option>
                        </template>
                    </select>
                </div>
            </div>

            <div class="flex justify-end pt-2 border-t border-stone-300 font-typewriter">
                <button @click="runComparison()" :disabled="!proposalA || !proposalB || loading"
                        class="px-4 py-2 bg-emerald-100 hover:bg-emerald-200 disabled:opacity-40 text-emerald-900 border border-emerald-300 font-semibold rounded text-xs transition shadow-xs">
                    <span x-show="!loading">Compare Feasibility & Objective Impact</span>
                    <span x-show="loading" style="display:none;">Calculating Matrix...</span>
                </button>
            </div>
        </div>

        <!-- Comparison Matrix Result Grid -->
        <template x-if="comparisonData">
            <div class="space-y-6">
                <!-- AI Objective Winner Banner -->
                <div class="torn-card p-5 border border-stone-300 flex flex-col md:flex-row items-start md:items-center justify-between gap-4"
                     :class="comparisonData.recommended_winner === 'proposal_a' ? 'bg-emerald-50/50 border-emerald-300' : 'bg-sky-50/50 border-sky-300'">
                    <div class="flex items-start space-x-3">
                        <svg class="w-5 h-5 mt-0.5 shrink-0 text-stone-800" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <div class="font-typewriter">
                            <span class="text-[10px] uppercase tracking-wider font-bold block text-stone-600">Gemma 4 Strategic Priority Recommendation:</span>
                            <span class="text-sm font-bold text-stone-900" x-text="comparisonData[comparisonData.recommended_winner].title"></span>
                        </div>
                    </div>
                    <span class="text-[10px] font-bold uppercase tracking-widest px-3 py-1.5 rounded border border-stone-400 bg-white text-stone-900 shrink-0 font-typewriter shadow-xs"
                          x-text="'+' + comparisonData.score_difference + ' Points Higher Impact (' + comparisonData.confidence_score + '% Confidence)'"></span>
                </div>

                <!-- AI Deep Strategic Reasoning, Trade-Offs & Suggested Fix -->
                <div class="torn-card p-5 space-y-4 border border-stone-300">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-stone-900 flex items-center gap-2 font-typewriter">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        Gemma 4 Multi-Factor Decision Analysis
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs font-typewriter">
                        <div class="bg-white p-4 rounded border border-stone-300 space-y-2 shadow-xs">
                            <span class="font-bold text-stone-900 block text-emerald-900 uppercase text-[10px]">Strategic Rationale</span>
                            <p class="leading-relaxed text-stone-700 font-ledger" x-text="comparisonData.ai_reasoning"></p>
                        </div>
                        <div class="bg-white p-4 rounded border border-stone-300 space-y-2 shadow-xs">
                            <span class="font-bold text-stone-900 block text-sky-900 uppercase text-[10px]">Trade-Off Analysis</span>
                            <p class="leading-relaxed text-stone-700 font-ledger" x-text="comparisonData.trade_off_analysis"></p>
                        </div>
                        <div class="bg-white p-4 rounded border border-stone-300 space-y-2 shadow-xs">
                            <span class="font-bold text-stone-900 block text-amber-900 uppercase text-[10px]">AI Suggested Resolution & Fix</span>
                            <p class="leading-relaxed text-stone-700 font-ledger" x-text="comparisonData.suggested_fix"></p>
                        </div>
                    </div>
                </div>

                <!-- Side by Side Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <!-- Proposal A Details -->
                    <div class="torn-card p-5 space-y-4 border border-stone-300 shadow-sm" :class="comparisonData.recommended_winner === 'proposal_a' ? 'border-2 border-emerald-600 bg-emerald-50/20' : ''">
                        <div class="flex justify-between items-start font-typewriter">
                            <span class="text-[10px] font-bold text-emerald-900 uppercase tracking-widest bg-emerald-100 border border-emerald-300 px-2 py-0.5 rounded">Proposal A</span>
                            <span class="text-xl font-black text-stone-900" x-text="comparisonData.proposal_a.score + '/100'"></span>
                        </div>
                        <h3 class="text-sm font-bold text-stone-900 font-typewriter" x-text="comparisonData.proposal_a.title"></h3>

                        <div class="space-y-2 text-xs text-stone-700 pt-2 border-t border-stone-300 font-typewriter">
                            <div class="flex justify-between">
                                <span class="text-stone-600">Citizen Reports (Demand):</span>
                                <span class="font-bold text-stone-900 font-ledger" x-text="comparisonData.proposal_a.citizen_reports + ' citizens'"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-stone-600">Linked Facility:</span>
                                <span class="font-bold text-stone-900 font-ledger" x-text="comparisonData.proposal_a.facility_name"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-stone-600">Current Capacity:</span>
                                <span class="font-bold text-stone-900 font-ledger" x-text="comparisonData.proposal_a.capacity"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-stone-600">Current Enrollment:</span>
                                <span class="font-bold text-stone-900 font-ledger" x-text="comparisonData.proposal_a.enrollment"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-stone-600">Avg Travel Distance:</span>
                                <span class="font-bold text-stone-900 font-ledger" x-text="comparisonData.proposal_a.avg_travel_distance_km + ' km'"></span>
                            </div>
                            <!-- Budget & Period Metrics -->
                            <div class="flex justify-between">
                                <span class="text-stone-600">Estimated Budget:</span>
                                <span class="font-bold text-emerald-900 font-ledger" x-text="comparisonData.proposal_a.estimated_budget_kes"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-stone-600">Implementation Period:</span>
                                <span class="font-bold text-stone-900 font-ledger" x-text="comparisonData.proposal_a.implementation_period"></span>
                            </div>
                            <!-- Demographic & CIDP Information -->
                            <div class="flex justify-between pt-1 border-t border-stone-300/60">
                                <span class="text-stone-600">Poverty Index Score:</span>
                                <span class="font-bold text-amber-900 font-ledger" x-text="comparisonData.proposal_a.poverty_index_score"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-stone-600">Listed in Local Dev Plan (CIDP):</span>
                                <span class="font-bold font-ledger" :class="comparisonData.proposal_a.is_in_cidp_plan ? 'text-emerald-900' : 'text-stone-600'" x-text="comparisonData.proposal_a.is_in_cidp_plan ? 'Yes (Priority Target)' : 'No'"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Proposal B Details -->
                    <div class="torn-card p-5 space-y-4 border border-stone-300 shadow-sm" :class="comparisonData.recommended_winner === 'proposal_b' ? 'border-2 border-sky-600 bg-sky-50/20' : ''">
                        <div class="flex justify-between items-start font-typewriter">
                            <span class="text-[10px] font-bold text-sky-900 uppercase tracking-widest bg-sky-100 border border-sky-300 px-2 py-0.5 rounded">Proposal B</span>
                            <span class="text-xl font-black text-stone-900" x-text="comparisonData.proposal_b.score + '/100'"></span>
                        </div>
                        <h3 class="text-sm font-bold text-stone-900 font-typewriter" x-text="comparisonData.proposal_b.title"></h3>

                        <div class="space-y-2 text-xs text-stone-700 pt-2 border-t border-stone-300 font-typewriter">
                            <div class="flex justify-between">
                                <span class="text-stone-600">Citizen Reports (Demand):</span>
                                <span class="font-bold text-stone-900 font-ledger" x-text="comparisonData.proposal_b.citizen_reports + ' citizens'"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-stone-600">Linked Facility:</span>
                                <span class="font-bold text-stone-900 font-ledger" x-text="comparisonData.proposal_b.facility_name"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-stone-600">Current Capacity:</span>
                                <span class="font-bold text-stone-900 font-ledger" x-text="comparisonData.proposal_b.capacity"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-stone-600">Current Enrollment:</span>
                                <span class="font-bold text-stone-900 font-ledger" x-text="comparisonData.proposal_b.enrollment"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-stone-600">Avg Travel Distance:</span>
                                <span class="font-bold text-stone-900 font-ledger" x-text="comparisonData.proposal_b.avg_travel_distance_km + ' km'"></span>
                            </div>
                            <!-- Budget & Period Metrics -->
                            <div class="flex justify-between">
                                <span class="text-stone-600">Estimated Budget:</span>
                                <span class="font-bold text-emerald-900 font-ledger" x-text="comparisonData.proposal_b.estimated_budget_kes"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-stone-600">Implementation Period:</span>
                                <span class="font-bold text-stone-900 font-ledger" x-text="comparisonData.proposal_b.implementation_period"></span>
                            </div>
                            <!-- Demographic & CIDP Information -->
                            <div class="flex justify-between pt-1 border-t border-stone-300/60">
                                <span class="text-stone-600">Poverty Index Score:</span>
                                <span class="font-bold text-amber-900 font-ledger" x-text="comparisonData.proposal_b.poverty_index_score"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-stone-600">Listed in Local Dev Plan (CIDP):</span>
                                <span class="font-bold font-ledger" :class="comparisonData.proposal_b.is_in_cidp_plan ? 'text-emerald-900' : 'text-stone-600'" x-text="comparisonData.proposal_b.is_in_cidp_plan ? 'Yes (Priority Target)' : 'No'"></span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </template>

    </div>

    <script>
        function matrixPage() {
            return {
                availableRequests: [],
                proposalA: '',
                proposalB: '',
                loading: false,
                comparisonData: null,

                async initMatrix() {
                    const res = await fetch('/api/mp/dashboard');
                    const data = await res.json();
                    if (data.status === 'success') {
                        this.availableRequests = data.recent_requests || [];
                    }
                },

                async runComparison() {
                    this.loading = true;
                    try {
                        const res = await fetch('/api/mp/compare-proposals', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                proposal_a_id: this.proposalA,
                                proposal_b_id: this.proposalB
                            })
                        });
                        const data = await res.json();
                        if (data.status === 'success') {
                            this.comparisonData = data.comparison;
                        }
                    } catch (e) {
                        console.error('Failed to calculate comparison matrix:', e);
                    } finally {
                        this.loading = false;
                    }
                }
            }
        }
    </script>
</x-layout>