<x-layout title="Priority Board">
    <div x-data="prioritiesPage()" x-init="init()" class="space-y-6">

        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4 border-b border-stone-400/40 pb-4">
            <div>
                <h1 class="text-2xl font-bold text-stone-900 font-typewriter tracking-wide uppercase">Priority Board</h1>
                <p class="text-xs text-stone-700 font-typewriter mt-1">AI recommends ranking and a fundable bundle. You decide the final allocation.</p>
            </div>
            <div class="flex flex-wrap gap-2 font-typewriter">
                <button @click="rescore()" class="px-4 py-2 rounded text-xs bg-[#e6dfd1] border border-stone-400/60 text-stone-900 hover:bg-[#dcd5c1] transition shadow-sm">
                    Recalculate AI scores
                </button>
                <button @click="toggleLock()" class="px-4 py-2 rounded text-xs font-semibold border transition shadow-sm"
                        :class="locked ? 'bg-amber-100 text-amber-900 border-amber-300' : 'bg-emerald-100 text-emerald-900 border-emerald-300'">
                    <span x-text="locked ? 'Unlock priorities' : 'Lock priorities'"></span>
                </button>
            </div>
        </div>

        <div class="torn-card bg-[#f2eee3] border border-stone-400 text-stone-900 rounded-none px-4 py-3 text-xs font-typewriter shadow-sm">
            <span x-text="banner"></span>
            <template x-if="locked">
                <span class="block text-amber-900 mt-1">Priorities are locked. Unlock to promote, demote, or reorder.</span>
            </template>
        </div>

        <!-- Sprint D: Budget-aware bundle -->
        <div class="torn-card p-5 space-y-4 border border-stone-300">
            <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 border-b border-stone-300 pb-3">
                <div>
                    <h2 class="text-base font-bold text-stone-900 font-typewriter uppercase">Maximize impact under budget</h2>
                    <p class="text-xs text-stone-700 font-typewriter mt-1">Enter available CDF / allocation funds. Gemma estimates costs; the optimizer picks the highest-impact fundable set.</p>
                </div>
                <div class="flex flex-wrap items-end gap-2 font-typewriter">
                    <div>
                        <label class="block text-[10px] uppercase tracking-wider text-stone-700 mb-1">Available budget (KES)</label>
                        <input type="number" min="0" step="10000" x-model.number="budgetKes"
                               class="w-48 bg-white border border-stone-300 rounded px-3 py-2 text-xs text-stone-900 focus:outline-none focus:border-stone-500 shadow-xs"
                               placeholder="e.g. 10000000">
                    </div>
                    <button @click="saveBudget()" class="px-3 py-2 text-xs rounded border border-stone-400 bg-white text-stone-900 hover:bg-stone-100 shadow-xs">
                        Save budget
                    </button>
                    <button @click="proposeBundle(false)" :disabled="bundleLoading"
                            class="px-3 py-2 text-xs rounded bg-emerald-100 text-emerald-900 border border-emerald-300 font-semibold disabled:opacity-40 shadow-xs">
                        <span x-text="bundleLoading ? 'Optimizing…' : 'Propose fundable bundle'"></span>
                    </button>
                    <button @click="proposeBundle(true)" :disabled="bundleLoading"
                            class="px-3 py-2 text-xs rounded border border-sky-300 bg-sky-100 text-sky-900 hover:bg-sky-200 disabled:opacity-40 shadow-xs"
                            title="Re-estimate costs with Gemma 4, then optimize">
                        Refresh costs + bundle
                    </button>
                </div>
            </div>

            <p x-show="budgetMessage" class="text-xs text-stone-700 font-typewriter" x-text="budgetMessage"></p>

            <template x-if="bundle">
                <div class="space-y-4 border-t border-dashed border-stone-300 pt-4">
                    <p class="text-xs text-emerald-900 font-typewriter font-medium" x-text="bundle.summary"></p>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs font-typewriter">
                        <div class="bg-white rounded p-3 border border-stone-300 shadow-xs">
                            <span class="block text-[10px] text-stone-600 uppercase tracking-wider">Bundle cost</span>
                            <span class="text-stone-900 font-bold" x-text="'Ksh ' + formatKes(bundle.total_cost_kes)"></span>
                        </div>
                        <div class="bg-white rounded p-3 border border-stone-300 shadow-xs">
                            <span class="block text-[10px] text-stone-600 uppercase tracking-wider">Remaining</span>
                            <span class="text-stone-900 font-bold" x-text="'Ksh ' + formatKes(bundle.remaining_budget_kes)"></span>
                        </div>
                        <div class="bg-white rounded p-3 border border-stone-300 shadow-xs">
                            <span class="block text-[10px] text-stone-600 uppercase tracking-wider">Impact score</span>
                            <span class="text-stone-900 font-bold" x-text="(bundle.total_impact_score ?? 0).toFixed(1)"></span>
                        </div>
                        <div class="bg-white rounded p-3 border border-stone-300 shadow-xs">
                            <span class="block text-[10px] text-stone-600 uppercase tracking-wider">Selected</span>
                            <span class="text-stone-900 font-bold" x-text="(bundle.selected?.length || 0) + ' / ' + ((bundle.selected?.length || 0) + (bundle.deferred?.length || 0))"></span>
                        </div>
                    </div>

                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-emerald-900 mb-2 font-typewriter">Fund this cycle</h3>
                            <div class="space-y-2 max-h-64 overflow-y-auto">
                                <template x-for="item in (bundle.selected || [])" :key="'sel-' + item.request_id">
                                    <div class="bg-emerald-50/50 border border-emerald-300 rounded p-3 text-xs shadow-xs">
                                        <p class="text-stone-900 font-ledger line-clamp-2" x-text="item.content"></p>
                                        <div class="flex flex-wrap gap-2 mt-2 text-[10px] text-stone-700 font-typewriter">
                                            <span x-text="item.category"></span>
                                            <span x-text="'Score ' + (item.priority_score ?? 0).toFixed(1)"></span>
                                            <span class="text-emerald-900 font-bold" x-text="'Ksh ' + formatKes(item.estimated_cost_kes)"></span>
                                            <span x-text="item.cost_source || ''"></span>
                                        </div>
                                    </div>
                                </template>
                                <p x-show="!(bundle.selected && bundle.selected.length)" class="text-xs text-stone-600 font-typewriter">No requests fit this budget.</p>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-stone-700 mb-2 font-typewriter">Deferred (over budget)</h3>
                            <div class="space-y-2 max-h-64 overflow-y-auto">
                                <template x-for="item in (bundle.deferred || [])" :key="'def-' + item.request_id">
                                    <div class="bg-white border border-stone-300 rounded p-3 text-xs opacity-80 shadow-xs">
                                        <p class="text-stone-700 font-ledger line-clamp-2" x-text="item.content"></p>
                                        <div class="flex flex-wrap gap-2 mt-2 text-[10px] text-stone-600 font-typewriter">
                                            <span x-text="'Score ' + (item.priority_score ?? 0).toFixed(1)"></span>
                                            <span x-text="'Ksh ' + formatKes(item.estimated_cost_kes)"></span>
                                        </div>
                                    </div>
                                </template>
                                <p x-show="!(bundle.deferred && bundle.deferred.length)" class="text-xs text-stone-600 font-typewriter">All open requests fit under the cap.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <div x-show="loading" class="space-y-3">
            <div class="h-28 bg-stone-200 animate-pulse rounded"></div>
            <div class="h-28 bg-stone-200 animate-pulse rounded"></div>
        </div>

        <div x-show="!loading && priorities.length === 0" class="text-center py-12 border border-dashed border-stone-400 rounded">
            <p class="text-stone-700 font-typewriter text-xs">No open requests to prioritize yet.</p>
        </div>

        <div x-show="!loading && priorities.length > 0" class="space-y-4">
            <template x-for="(item, index) in priorities" :key="item.request_id">
                <div class="torn-card p-5 space-y-4 border border-stone-300 shadow-sm"
                     :class="isInBundle(item.request_id) ? 'border-2 border-emerald-600 bg-emerald-50/20' : ''">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="flex items-start gap-3">
                            <div class="w-12 h-12 rounded bg-white border border-stone-300 flex items-center justify-center shadow-xs shrink-0">
                                <span class="text-lg font-black text-stone-900 font-typewriter" x-text="'#' + item.display_rank"></span>
                            </div>
                            <div>
                                <p class="text-xs font-ledger text-stone-900 font-medium" x-text="item.content"></p>
                                <div class="flex flex-wrap gap-2 mt-2 text-[10px] font-typewriter">
                                    <span class="px-2 py-0.5 rounded bg-stone-200 text-stone-800 uppercase" x-text="item.category"></span>
                                    <span class="px-2 py-0.5 rounded bg-stone-200 text-stone-800 uppercase" x-text="item.urgency + ' urgency'"></span>
                                    <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-900 border border-emerald-300 font-semibold"
                                          x-text="'Score ' + (item.priority_score ?? 0).toFixed(1)"></span>
                                    <span class="px-2 py-0.5 rounded bg-amber-100 text-amber-900 border border-amber-300"
                                          x-show="item.estimated_cost_kes"
                                          x-text="'Est. Ksh ' + formatKes(item.estimated_cost_kes)"></span>
                                    <span class="px-2 py-0.5 rounded bg-rose-100 text-rose-900 border border-rose-300 text-[10px] uppercase tracking-wide font-semibold"
                                          x-show="item.equity_flag"
                                          x-text="item.equity_boost ? ('Equity +' + item.equity_boost) : 'Equity check'"></span>
                                    <span class="px-2 py-0.5 rounded border text-[10px] uppercase tracking-wide font-semibold"
                                          :class="item.ai_rank_source === 'mp_override' ? 'bg-amber-100 text-amber-900 border-amber-300' : 'bg-sky-100 text-sky-900 border-sky-300'"
                                          x-text="item.ai_rank_source === 'mp_override' ? 'MP override' : 'AI score'"></span>
                                    <span x-show="isInBundle(item.request_id)"
                                          class="px-2 py-0.5 rounded bg-emerald-200 text-emerald-950 border border-emerald-400 text-[10px] uppercase tracking-wide font-bold">
                                        In proposed bundle
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="flex gap-2 font-typewriter">
                            <button @click="move(item, 'promote')" :disabled="locked || index === 0"
                                    class="px-3 py-1.5 text-xs rounded border border-stone-400 bg-white text-stone-900 disabled:opacity-40 hover:bg-stone-100 shadow-xs">
                                Promote
                            </button>
                            <button @click="move(item, 'demote')" :disabled="locked || index === priorities.length - 1"
                                    class="px-3 py-1.5 text-xs rounded border border-stone-400 bg-white text-stone-900 disabled:opacity-40 hover:bg-stone-100 shadow-xs">
                                Demote
                            </button>
                            <button @click="toggleFactors(item.request_id)"
                                    class="px-3 py-1.5 text-xs rounded bg-[#e6dfd1] border border-stone-400/60 text-stone-900 hover:bg-[#dcd5c1] shadow-xs">
                                Why this rank?
                            </button>
                        </div>
                    </div>

                    <template x-if="openFactors[item.request_id]">
                        <div class="bg-white border border-stone-300 rounded p-4 text-xs space-y-2 shadow-inner font-ledger">
                            <p class="text-stone-900" x-text="item.priority_factors?.reason || item.evaluation_thoughts || 'No factor breakdown yet.'"></p>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-[10px] text-stone-700 font-typewriter pt-2 border-t border-stone-200">
                                <div>
                                    <span class="block uppercase tracking-wider text-stone-600 font-bold">Urgency</span>
                                    <span class="text-stone-900" x-text="(item.priority_factors?.urgency ?? '—') + '/10'"></span>
                                </div>
                                <div>
                                    <span class="block uppercase tracking-wider text-stone-600 font-bold">Reports</span>
                                    <span class="text-stone-900" x-text="item.priority_factors?.reports ?? item.similar_count ?? 1"></span>
                                </div>
                                <div>
                                    <span class="block uppercase tracking-wider text-stone-600 font-bold">Equity (poverty)</span>
                                    <span class="text-stone-900" x-text="(item.priority_factors?.poverty_rate_percentage ?? 50) + '%'"></span>
                                </div>
                                <div>
                                    <span class="block uppercase tracking-wider text-stone-600 font-bold">Est. cost</span>
                                    <span class="text-stone-900" x-text="item.estimated_cost_kes ? ('Ksh ' + formatKes(item.estimated_cost_kes)) : '—'"></span>
                                </div>
                                <div x-show="item.equity_flag">
                                    <span class="block uppercase tracking-wider text-stone-600 font-bold">Equity</span>
                                    <span class="text-rose-800 font-semibold" x-text="(item.detected_language || 'flagged') + (item.equity_boost ? (' · +' + item.equity_boost) : '')"></span>
                                </div>
                            </div>
                            <template x-if="item.equity_flag && item.equity_reasons?.length">
                                <p class="text-[11px] text-rose-900 font-typewriter" x-text="item.equity_reasons.join(' ')"></p>
                            </template>
                            <p class="text-[11px] text-stone-600 font-typewriter" x-show="item.cost_rationale" x-text="item.cost_rationale"></p>
                            <p class="text-[11px] text-stone-600 font-typewriter" x-text="item.priority_factors?.formula"></p>
                            <template x-if="item.override_reason">
                                <p class="text-xs text-amber-900 font-typewriter font-bold pt-1" x-text="'MP override reason: ' + item.override_reason"></p>
                            </template>
                        </div>
                    </template>
                </div>
            </template>
        </div>

        <!-- Override reason modal -->
        <div x-show="pendingMove" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-stone-900/60 backdrop-blur-xs" x-cloak>
            <div @click.away="cancelMove()" class="bg-[#f2eee3] border border-stone-400 rounded-lg w-full max-w-md p-6 space-y-4 shadow-2xl font-typewriter">
                <h3 class="text-base font-bold text-stone-900 uppercase">MP override reason</h3>
                <p class="text-xs text-stone-700">Required when changing the AI recommendation order.</p>
                <textarea x-model="overrideReason" rows="3"
                          class="w-full bg-white border border-stone-300 rounded p-3 text-xs text-stone-900 focus:outline-none focus:border-stone-500 shadow-xs font-ledger"
                          placeholder="e.g. School term starts next week; this affects more learners."></textarea>
                <p x-show="error" class="text-xs text-red-700 font-bold" x-text="error"></p>
                <div class="flex justify-end gap-2 pt-2 border-t border-stone-300">
                    <button @click="cancelMove()" class="px-4 py-2 text-xs rounded bg-stone-300 text-stone-900 font-semibold hover:bg-stone-400 shadow-xs">Cancel</button>
                    <button @click="confirmMove()" class="px-4 py-2 text-xs rounded bg-emerald-100 text-emerald-900 border border-emerald-300 font-semibold shadow-xs">
                        Save override
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function prioritiesPage() {
            return {
                loading: true,
                priorities: [],
                locked: false,
                banner: 'AI recommendation — not an allocation decision until you lock priorities.',
                openFactors: {},
                pendingMove: null,
                overrideReason: '',
                error: '',
                budgetKes: null,
                budgetMessage: '',
                bundle: null,
                bundleLoading: false,
                bundleSelectedIds: {},

                async init() {
                    await this.fetchPriorities();
                },

                formatKes(n) {
                    if (n == null || n === '') return '—';
                    return Number(n).toLocaleString('en-KE');
                },

                isInBundle(id) {
                    return !!this.bundleSelectedIds[id];
                },

                async fetchPriorities() {
                    this.loading = true;
                    try {
                        const res = await fetch('/api/mp/priorities');
                        const data = await res.json();
                        if (data.status === 'success') {
                            this.priorities = data.priorities || [];
                            this.locked = !!data.locked;
                            this.banner = data.banner || this.banner;
                            if (data.available_budget_kes != null) {
                                this.budgetKes = data.available_budget_kes;
                            }
                        }
                    } catch (e) {
                        console.error(e);
                    } finally {
                        this.loading = false;
                    }
                },

                async saveBudget() {
                    this.budgetMessage = '';
                    try {
                        const res = await fetch('/api/mp/priorities/budget', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                available_budget_kes: this.budgetKes === '' || this.budgetKes == null ? null : Number(this.budgetKes),
                            }),
                        });
                        const data = await res.json();
                        if (data.status === 'success') {
                            this.budgetKes = data.available_budget_kes;
                            this.budgetMessage = data.message || 'Budget saved.';
                        } else {
                            this.budgetMessage = data.message || 'Could not save budget.';
                        }
                    } catch (e) {
                        this.budgetMessage = 'Network error while saving budget.';
                    }
                },

                async proposeBundle(refreshCosts) {
                    this.bundleLoading = true;
                    this.budgetMessage = '';
                    try {
                        const res = await fetch('/api/mp/priorities/budget-bundle', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                available_budget_kes: this.budgetKes ? Number(this.budgetKes) : null,
                                refresh_costs: !!refreshCosts,
                                persist_budget: true,
                            }),
                        });
                        const data = await res.json();
                        if (data.status === 'success') {
                            this.bundle = data.bundle;
                            this.budgetKes = data.available_budget_kes ?? this.budgetKes;
                            this.bundleSelectedIds = {};
                            (data.bundle?.selected || []).forEach((item) => {
                                this.bundleSelectedIds[item.request_id] = true;
                            });
                            await this.fetchPriorities();
                        } else {
                            this.budgetMessage = data.message || 'Bundle optimization failed.';
                        }
                    } catch (e) {
                        this.budgetMessage = 'Network error while proposing bundle.';
                    } finally {
                        this.bundleLoading = false;
                    }
                },

                toggleFactors(id) {
                    this.openFactors[id] = !this.openFactors[id];
                },

                move(item, direction) {
                    if (this.locked) return;
                    this.pendingMove = { id: item.request_id, direction };
                    this.overrideReason = '';
                    this.error = '';
                },

                cancelMove() {
                    this.pendingMove = null;
                    this.overrideReason = '';
                    this.error = '';
                },

                async confirmMove() {
                    if (!this.overrideReason || this.overrideReason.trim().length < 5) {
                        this.error = 'Please provide a short reason (at least 5 characters).';
                        return;
                    }

                    try {
                        const res = await fetch(`/api/mp/requests/${this.pendingMove.id}/override-priority`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                direction: this.pendingMove.direction,
                                reason: this.overrideReason.trim(),
                            }),
                        });
                        const data = await res.json();
                        if (data.status === 'success') {
                            this.priorities = data.priorities || [];
                            this.cancelMove();
                        } else {
                            this.error = data.message || 'Override failed.';
                        }
                    } catch (e) {
                        this.error = 'Network error while saving override.';
                    }
                },

                async toggleLock() {
                    try {
                        const res = await fetch('/api/mp/priorities/lock', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ locked: !this.locked }),
                        });
                        const data = await res.json();
                        if (data.status === 'success') {
                            this.locked = !!data.locked;
                        }
                    } catch (e) {
                        console.error(e);
                    }
                },

                async rescore() {
                    this.loading = true;
                    try {
                        const res = await fetch('/api/mp/priorities/rescore', {
                            method: 'POST',
                            headers: { 'Accept': 'application/json' },
                        });
                        const data = await res.json();
                        if (data.status === 'success') {
                            this.priorities = data.priorities || [];
                        }
                    } catch (e) {
                        console.error(e);
                    } finally {
                        this.loading = false;
                    }
                }
            }
        }
    </script>
</x-layout>