<x-layout title="Priority Board">
    <div x-data="prioritiesPage()" x-init="init()" class="space-y-6">

        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-100">Priority Board</h1>
                <p class="text-sm text-slate-400 mt-1">AI recommends ranking and a fundable bundle. You decide the final allocation.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button @click="rescore()" class="px-4 py-2 rounded-xl text-sm bg-slate-900 border border-slate-800 text-slate-300 hover:bg-slate-800 transition">
                    Recalculate AI scores
                </button>
                <button @click="toggleLock()" class="px-4 py-2 rounded-xl text-sm font-semibold border transition"
                        :class="locked ? 'bg-amber-500/10 text-amber-300 border-amber-500/30' : 'bg-emerald-500/10 text-emerald-300 border-emerald-500/30'">
                    <span x-text="locked ? 'Unlock priorities' : 'Lock priorities'"></span>
                </button>
            </div>
        </div>

        <div class="bg-sky-500/10 border border-sky-500/20 text-sky-200 rounded-2xl px-4 py-3 text-sm">
            <span x-text="banner"></span>
            <template x-if="locked">
                <span class="block text-amber-300 mt-1 text-xs">Priorities are locked. Unlock to promote, demote, or reorder.</span>
            </template>
        </div>

        <!-- Sprint D: Budget-aware bundle -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-4">
            <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-100">Maximize impact under budget</h2>
                    <p class="text-xs text-slate-400 mt-1">Enter available CDF / allocation funds. Gemma estimates costs; the optimizer picks the highest-impact fundable set.</p>
                </div>
                <div class="flex flex-wrap items-end gap-2">
                    <div>
                        <label class="block text-[10px] uppercase tracking-wider text-slate-500 mb-1">Available budget (KES)</label>
                        <input type="number" min="0" step="10000" x-model.number="budgetKes"
                               class="w-48 bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-emerald-500/40"
                               placeholder="e.g. 10000000">
                    </div>
                    <button @click="saveBudget()" class="px-3 py-2 text-xs rounded-xl border border-slate-700 text-slate-300 hover:bg-slate-800">
                        Save budget
                    </button>
                    <button @click="proposeBundle(false)" :disabled="bundleLoading"
                            class="px-3 py-2 text-xs rounded-xl bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 font-semibold disabled:opacity-40">
                        <span x-text="bundleLoading ? 'Optimizing…' : 'Propose fundable bundle'"></span>
                    </button>
                    <button @click="proposeBundle(true)" :disabled="bundleLoading"
                            class="px-3 py-2 text-xs rounded-xl border border-sky-500/30 text-sky-300 hover:bg-sky-500/10 disabled:opacity-40"
                            title="Re-estimate costs with Gemma 4, then optimize">
                        Refresh costs + bundle
                    </button>
                </div>
            </div>

            <p x-show="budgetMessage" class="text-xs text-slate-400" x-text="budgetMessage"></p>

            <template x-if="bundle">
                <div class="space-y-4 border-t border-slate-800 pt-4">
                    <p class="text-sm text-emerald-200/90" x-text="bundle.summary"></p>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs">
                        <div class="bg-slate-950/80 rounded-xl p-3 border border-slate-800">
                            <span class="block text-slate-500 uppercase tracking-wider">Bundle cost</span>
                            <span class="text-slate-100 font-semibold" x-text="'Ksh ' + formatKes(bundle.total_cost_kes)"></span>
                        </div>
                        <div class="bg-slate-950/80 rounded-xl p-3 border border-slate-800">
                            <span class="block text-slate-500 uppercase tracking-wider">Remaining</span>
                            <span class="text-slate-100 font-semibold" x-text="'Ksh ' + formatKes(bundle.remaining_budget_kes)"></span>
                        </div>
                        <div class="bg-slate-950/80 rounded-xl p-3 border border-slate-800">
                            <span class="block text-slate-500 uppercase tracking-wider">Impact score</span>
                            <span class="text-slate-100 font-semibold" x-text="(bundle.total_impact_score ?? 0).toFixed(1)"></span>
                        </div>
                        <div class="bg-slate-950/80 rounded-xl p-3 border border-slate-800">
                            <span class="block text-slate-500 uppercase tracking-wider">Selected</span>
                            <span class="text-slate-100 font-semibold" x-text="(bundle.selected?.length || 0) + ' / ' + ((bundle.selected?.length || 0) + (bundle.deferred?.length || 0))"></span>
                        </div>
                    </div>

                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <h3 class="text-xs font-semibold uppercase tracking-wider text-emerald-400 mb-2">Fund this cycle</h3>
                            <div class="space-y-2 max-h-64 overflow-y-auto">
                                <template x-for="item in (bundle.selected || [])" :key="'sel-' + item.request_id">
                                    <div class="bg-emerald-500/5 border border-emerald-500/20 rounded-xl p-3 text-sm">
                                        <p class="text-slate-200 line-clamp-2" x-text="item.content"></p>
                                        <div class="flex flex-wrap gap-2 mt-2 text-[11px] text-slate-400">
                                            <span x-text="item.category"></span>
                                            <span x-text="'Score ' + (item.priority_score ?? 0).toFixed(1)"></span>
                                            <span class="text-emerald-300" x-text="'Ksh ' + formatKes(item.estimated_cost_kes)"></span>
                                            <span x-text="item.cost_source || ''"></span>
                                        </div>
                                    </div>
                                </template>
                                <p x-show="!(bundle.selected && bundle.selected.length)" class="text-xs text-slate-500">No requests fit this budget.</p>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Deferred (over budget)</h3>
                            <div class="space-y-2 max-h-64 overflow-y-auto">
                                <template x-for="item in (bundle.deferred || [])" :key="'def-' + item.request_id">
                                    <div class="bg-slate-950/60 border border-slate-800 rounded-xl p-3 text-sm opacity-80">
                                        <p class="text-slate-300 line-clamp-2" x-text="item.content"></p>
                                        <div class="flex flex-wrap gap-2 mt-2 text-[11px] text-slate-500">
                                            <span x-text="'Score ' + (item.priority_score ?? 0).toFixed(1)"></span>
                                            <span x-text="'Ksh ' + formatKes(item.estimated_cost_kes)"></span>
                                        </div>
                                    </div>
                                </template>
                                <p x-show="!(bundle.deferred && bundle.deferred.length)" class="text-xs text-slate-500">All open requests fit under the cap.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <div x-show="loading" class="space-y-3">
            <div class="h-28 bg-slate-800/50 animate-pulse rounded-xl"></div>
            <div class="h-28 bg-slate-800/50 animate-pulse rounded-xl"></div>
        </div>

        <div x-show="!loading && priorities.length === 0" class="text-center py-12 border border-dashed border-slate-800 rounded-xl">
            <p class="text-slate-400">No open requests to prioritize yet.</p>
        </div>

        <div x-show="!loading && priorities.length > 0" class="space-y-4">
            <template x-for="(item, index) in priorities" :key="item.request_id">
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-4"
                     :class="isInBundle(item.request_id) ? 'ring-1 ring-emerald-500/40' : ''">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="flex items-start gap-3">
                            <div class="w-12 h-12 rounded-xl bg-slate-950 border border-slate-800 flex items-center justify-center">
                                <span class="text-lg font-black text-emerald-400" x-text="'#' + item.display_rank"></span>
                            </div>
                            <div>
                                <p class="text-slate-100 font-semibold" x-text="item.content"></p>
                                <div class="flex flex-wrap gap-2 mt-2 text-xs">
                                    <span class="px-2 py-0.5 rounded-full bg-slate-800 text-slate-300 capitalize" x-text="item.category"></span>
                                    <span class="px-2 py-0.5 rounded-full bg-slate-800 text-slate-300 capitalize" x-text="item.urgency + ' urgency'"></span>
                                    <span class="px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-300 border border-emerald-500/20"
                                          x-text="'Score ' + (item.priority_score ?? 0).toFixed(1)"></span>
                                    <span class="px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-200 border border-amber-500/20"
                                          x-show="item.estimated_cost_kes"
                                          x-text="'Est. Ksh ' + formatKes(item.estimated_cost_kes)"></span>
                                    <span class="px-2 py-0.5 rounded-full bg-rose-500/10 text-rose-300 border border-rose-500/30 text-[10px] uppercase tracking-wide"
                                          x-show="item.equity_flag"
                                          x-text="item.equity_boost ? ('Equity +' + item.equity_boost) : 'Equity check'"></span>
                                    <span class="px-2 py-0.5 rounded-full border text-[10px] uppercase tracking-wide"
                                          :class="item.ai_rank_source === 'mp_override' ? 'bg-amber-500/10 text-amber-300 border-amber-500/30' : 'bg-sky-500/10 text-sky-300 border-sky-500/30'"
                                          x-text="item.ai_rank_source === 'mp_override' ? 'MP override' : 'AI score'"></span>
                                    <span x-show="isInBundle(item.request_id)"
                                          class="px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-200 border border-emerald-500/30 text-[10px] uppercase tracking-wide">
                                        In proposed bundle
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <button @click="move(item, 'promote')" :disabled="locked || index === 0"
                                    class="px-3 py-1.5 text-xs rounded-lg border border-slate-700 text-slate-200 disabled:opacity-40 hover:bg-slate-800">
                                Promote
                            </button>
                            <button @click="move(item, 'demote')" :disabled="locked || index === priorities.length - 1"
                                    class="px-3 py-1.5 text-xs rounded-lg border border-slate-700 text-slate-200 disabled:opacity-40 hover:bg-slate-800">
                                Demote
                            </button>
                            <button @click="toggleFactors(item.request_id)"
                                    class="px-3 py-1.5 text-xs rounded-lg bg-slate-800 text-slate-200 hover:bg-slate-700">
                                Why this rank?
                            </button>
                        </div>
                    </div>

                    <template x-if="openFactors[item.request_id]">
                        <div class="bg-slate-950/80 border border-slate-800 rounded-xl p-4 text-sm space-y-2">
                            <p class="text-slate-300" x-text="item.priority_factors?.reason || item.evaluation_thoughts || 'No factor breakdown yet.'"></p>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs text-slate-400 pt-2">
                                <div>
                                    <span class="block uppercase tracking-wider text-slate-500">Urgency</span>
                                    <span x-text="(item.priority_factors?.urgency ?? '—') + '/10'"></span>
                                </div>
                                <div>
                                    <span class="block uppercase tracking-wider text-slate-500">Reports</span>
                                    <span x-text="item.priority_factors?.reports ?? item.similar_count ?? 1"></span>
                                </div>
                                <div>
                                    <span class="block uppercase tracking-wider text-slate-500">Equity (poverty)</span>
                                    <span x-text="(item.priority_factors?.poverty_rate_percentage ?? 50) + '%'"></span>
                                </div>
                                <div>
                                    <span class="block uppercase tracking-wider text-slate-500">Est. cost</span>
                                    <span x-text="item.estimated_cost_kes ? ('Ksh ' + formatKes(item.estimated_cost_kes)) : '—'"></span>
                                </div>
                                <div x-show="item.equity_flag">
                                    <span class="block uppercase tracking-wider text-slate-500">Equity</span>
                                    <span class="text-rose-300" x-text="(item.detected_language || 'flagged') + (item.equity_boost ? (' · +' + item.equity_boost) : '')"></span>
                                </div>
                            </div>
                            <template x-if="item.equity_flag && item.equity_reasons?.length">
                                <p class="text-[11px] text-rose-200/80" x-text="item.equity_reasons.join(' ')"></p>
                            </template>
                            <p class="text-[11px] text-slate-500" x-show="item.cost_rationale" x-text="item.cost_rationale"></p>
                            <p class="text-[11px] text-slate-500" x-text="item.priority_factors?.formula"></p>
                            <template x-if="item.override_reason">
                                <p class="text-xs text-amber-300 pt-1" x-text="'MP override reason: ' + item.override_reason"></p>
                            </template>
                        </div>
                    </template>
                </div>
            </template>
        </div>

        <!-- Override reason modal -->
        <div x-show="pendingMove" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm" x-cloak>
            <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-md p-6 space-y-4">
                <h3 class="text-lg font-bold text-slate-100">MP override reason</h3>
                <p class="text-sm text-slate-400">Required when changing the AI recommendation order.</p>
                <textarea x-model="overrideReason" rows="3"
                          class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-sm text-slate-200 focus:outline-none focus:border-emerald-500/40"
                          placeholder="e.g. School term starts next week; this affects more learners."></textarea>
                <p x-show="error" class="text-xs text-red-400" x-text="error"></p>
                <div class="flex justify-end gap-2">
                    <button @click="cancelMove()" class="px-4 py-2 text-xs rounded-xl bg-slate-800 text-slate-300">Cancel</button>
                    <button @click="confirmMove()" class="px-4 py-2 text-xs rounded-xl bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 font-semibold">
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
