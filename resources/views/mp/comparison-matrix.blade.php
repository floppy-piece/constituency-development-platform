<x-layout title="Proposal Feasibility Matrix">
    <div x-data="matrixPage()" x-init="initMatrix()" class="space-y-6">
        
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-100">Proposal Comparison & Feasibility Matrix</h1>
                <p class="text-sm text-slate-400">Weigh competing constituent requests against real infrastructure metrics, demographic poverty indexes, and CIDP plans.</p>
            </div>
        </div>

        <!-- Selection Controls -->
        <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl shadow-xl grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Proposal A Selector -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-emerald-400 mb-2">Select Proposal A</label>
                <select x-model="proposalA" class="w-full bg-slate-950 border border-slate-800 text-slate-200 text-sm rounded-xl p-3 focus:ring-2 focus:ring-emerald-500">
                    <option value="">-- Choose First Proposal --</option>
                    <template x-for="req in availableRequests" :key="req.id">
                        <option :value="req.id" x-text="req.category + ': ' + req.content.substring(0, 50) + '...'"></option>
                    </template>
                </select>
            </div>

            <!-- Proposal B Selector -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-blue-400 mb-2">Select Proposal B</label>
                <select x-model="proposalB" class="w-full bg-slate-950 border border-slate-800 text-slate-200 text-sm rounded-xl p-3 focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Choose Second Proposal --</option>
                    <template x-for="req in availableRequests" :key="req.id">
                        <option :value="req.id" x-text="req.category + ': ' + req.content.substring(0, 50) + '...'"></option>
                    </template>
                </select>
            </div>

            <div class="md:col-span-2 flex justify-end">
                <button @click="runComparison()" :disabled="!proposalA || !proposalB || loading" class="px-6 py-2.5 bg-emerald-500 hover:bg-emerald-600 disabled:opacity-50 text-slate-950 font-bold rounded-xl text-sm transition">
                    <span x-show="!loading">Compare Feasibility & Objective Impact</span>
                    <span x-show="loading" style="display:none;">Calculating Matrix...</span>
                </button>
            </div>
        </div>

        <!-- Comparison Matrix Result Grid -->
        <template x-if="comparisonData">
            <div class="space-y-6">
                <!-- AI Objective Winner Banner -->
                <div class="p-6 rounded-2xl border flex flex-col md:flex-row items-start md:items-center justify-between gap-4"
                     :class="comparisonData.recommended_winner === 'proposal_a' ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400' : 'bg-blue-500/10 border-blue-500/30 text-blue-400'">
                    <div class="flex items-start space-x-3">
                        <svg class="w-6 h-6 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <div>
                            <span class="text-xs uppercase tracking-wider font-semibold block text-slate-400">Gemma 4 Strategic Priority Recommendation:</span>
                            <span class="text-lg font-bold text-slate-100" x-text="comparisonData[comparisonData.recommended_winner].title"></span>
                        </div>
                    </div>
                    <span class="text-xs font-black uppercase tracking-widest px-3 py-1.5 rounded-full border border-current shrink-0" x-text="'+' + comparisonData.score_difference + ' Points Higher Impact (' + comparisonData.confidence_score + '% Confidence)'"></span>
                </div>

                <!-- AI Deep Strategic Reasoning, Trade-Offs & Suggested Fix -->
                <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl space-y-4 shadow-lg">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-emerald-400 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        Gemma 4 Multi-Factor Decision Analysis
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-xs text-slate-300">
                        <div class="bg-slate-950 p-4 rounded-xl border border-slate-800 space-y-2">
                            <span class="font-bold text-slate-200 block text-emerald-400">Strategic Rationale</span>
                            <p class="leading-relaxed" x-text="comparisonData.ai_reasoning"></p>
                        </div>
                        <div class="bg-slate-950 p-4 rounded-xl border border-slate-800 space-y-2">
                            <span class="font-bold text-slate-200 block text-blue-400">Trade-Off Analysis</span>
                            <p class="leading-relaxed" x-text="comparisonData.trade_off_analysis"></p>
                        </div>
                        <div class="bg-slate-950 p-4 rounded-xl border border-slate-800 space-y-2">
                            <span class="font-bold text-slate-200 block text-amber-400">AI Suggested Resolution & Fix</span>
                            <p class="leading-relaxed" x-text="comparisonData.suggested_fix"></p>
                        </div>
                    </div>
                </div>

                <!-- Side by Side Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <!-- Proposal A Details -->
                    <div class="bg-slate-900 border rounded-2xl p-6 space-y-4" :class="comparisonData.recommended_winner === 'proposal_a' ? 'border-emerald-500' : 'border-slate-800'">
                        <div class="flex justify-between items-start">
                            <span class="text-xs font-bold text-emerald-400 uppercase tracking-widest">Proposal A</span>
                            <span class="text-2xl font-black text-slate-100" x-text="comparisonData.proposal_a.score + '/100'"></span>
                        </div>
                        <h3 class="text-lg font-bold text-slate-100" x-text="comparisonData.proposal_a.title"></h3>

                        <div class="space-y-2 text-xs text-slate-300 pt-2 border-t border-slate-800">
                            <div class="flex justify-between">
                                <span class="text-slate-500">Citizen Reports (Demand):</span>
                                <span class="font-bold text-slate-100" x-text="comparisonData.proposal_a.citizen_reports + ' citizens'"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Linked Facility:</span>
                                <span class="font-bold text-slate-100" x-text="comparisonData.proposal_a.facility_name"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Current Capacity:</span>
                                <span class="font-bold text-slate-100" x-text="comparisonData.proposal_a.capacity"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Current Enrollment:</span>
                                <span class="font-bold text-slate-100" x-text="comparisonData.proposal_a.enrollment"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Avg Travel Distance:</span>
                                <span class="font-bold text-slate-100" x-text="comparisonData.proposal_a.avg_travel_distance_km + ' km'"></span>
                            </div>
                            <!-- Budget & Period Metrics -->
                            <div class="flex justify-between">
                                <span class="text-slate-500">Estimated Budget:</span>
                                <span class="font-bold text-emerald-400" x-text="comparisonData.proposal_a.estimated_budget_kes"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Implementation Period:</span>
                                <span class="font-bold text-slate-100" x-text="comparisonData.proposal_a.implementation_period"></span>
                            </div>
                            <!-- Demographic & CIDP Information -->
                            <div class="flex justify-between pt-1 border-t border-slate-800/60">
                                <span class="text-slate-500">Poverty Index Score:</span>
                                <span class="font-bold text-amber-400" x-text="comparisonData.proposal_a.poverty_index_score"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Listed in Local Dev Plan (CIDP):</span>
                                <span class="font-bold" :class="comparisonData.proposal_a.is_in_cidp_plan ? 'text-emerald-400' : 'text-slate-400'" x-text="comparisonData.proposal_a.is_in_cidp_plan ? 'Yes (Priority Target)' : 'No'"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Proposal B Details -->
                    <div class="bg-slate-900 border rounded-2xl p-6 space-y-4" :class="comparisonData.recommended_winner === 'proposal_b' ? 'border-blue-500' : 'border-slate-800'">
                        <div class="flex justify-between items-start">
                            <span class="text-xs font-bold text-blue-400 uppercase tracking-widest">Proposal B</span>
                            <span class="text-2xl font-black text-slate-100" x-text="comparisonData.proposal_b.score + '/100'"></span>
                        </div>
                        <h3 class="text-lg font-bold text-slate-100" x-text="comparisonData.proposal_b.title"></h3>

                        <div class="space-y-2 text-xs text-slate-300 pt-2 border-t border-slate-800">
                            <div class="flex justify-between">
                                <span class="text-slate-500">Citizen Reports (Demand):</span>
                                <span class="font-bold text-slate-100" x-text="comparisonData.proposal_b.citizen_reports + ' citizens'"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Linked Facility:</span>
                                <span class="font-bold text-slate-100" x-text="comparisonData.proposal_b.facility_name"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Current Capacity:</span>
                                <span class="font-bold text-slate-100" x-text="comparisonData.proposal_b.capacity"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Current Enrollment:</span>
                                <span class="font-bold text-slate-100" x-text="comparisonData.proposal_b.enrollment"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Avg Travel Distance:</span>
                                <span class="font-bold text-slate-100" x-text="comparisonData.proposal_b.avg_travel_distance_km + ' km'"></span>
                            </div>
                            <!-- Budget & Period Metrics -->
                            <div class="flex justify-between">
                                <span class="text-slate-500">Estimated Budget:</span>
                                <span class="font-bold text-emerald-400" x-text="comparisonData.proposal_b.estimated_budget_kes"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Implementation Period:</span>
                                <span class="font-bold text-slate-100" x-text="comparisonData.proposal_b.implementation_period"></span>
                            </div>
                            <!-- Demographic & CIDP Information -->
                            <div class="flex justify-between pt-1 border-t border-slate-800/60">
                                <span class="text-slate-500">Poverty Index Score:</span>
                                <span class="font-bold text-amber-400" x-text="comparisonData.proposal_b.poverty_index_score"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Listed in Local Dev Plan (CIDP):</span>
                                <span class="font-bold" :class="comparisonData.proposal_b.is_in_cidp_plan ? 'text-emerald-400' : 'text-slate-400'" x-text="comparisonData.proposal_b.is_in_cidp_plan ? 'Yes (Priority Target)' : 'No'"></span>
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