<x-layout title="All Requests">
    <div x-data="requestsPage()" x-init="init()" class="space-y-6">

        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-100">All Requests</h1>
                <p class="text-sm text-slate-400 mt-1">Search and filter every citizen submission for your constituency.</p>
            </div>
            <a href="/mp/dashboard" class="text-sm text-emerald-400 hover:text-emerald-300">← Back to sector overview</a>
        </div>

        <!-- Filters -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
                <div class="md:col-span-2">
                    <label class="block text-[10px] uppercase tracking-wider text-slate-500 mb-1">Search</label>
                    <input type="search" x-model="filters.q" @keydown.enter="applyFilters()"
                           placeholder="Search summary, raw message, category…"
                           class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-emerald-500/40">
                </div>
                <div>
                    <label class="block text-[10px] uppercase tracking-wider text-slate-500 mb-1">Sector</label>
                    <select x-model="filters.sector" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-100">
                        <option value="">All sectors</option>
                        <template x-for="s in filterOptions.sectors" :key="s">
                            <option :value="s" x-text="s"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] uppercase tracking-wider text-slate-500 mb-1">Category</label>
                    <select x-model="filters.category" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-100">
                        <option value="">All categories</option>
                        <template x-for="c in filterOptions.categories" :key="c">
                            <option :value="c" x-text="c"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] uppercase tracking-wider text-slate-500 mb-1">Urgency</label>
                    <select x-model="filters.urgency" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-100">
                        <option value="">All</option>
                        <option value="high">High</option>
                        <option value="medium">Medium</option>
                        <option value="low">Low</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] uppercase tracking-wider text-slate-500 mb-1">Status</label>
                    <select x-model="filters.status" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-100">
                        <option value="">All statuses</option>
                        <template x-for="st in filterOptions.statuses" :key="st">
                            <option :value="st" x-text="st.replace('_', ' ')"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] uppercase tracking-wider text-slate-500 mb-1">From date</label>
                    <input type="date" x-model="filters.date_from" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-100">
                </div>
                <div>
                    <label class="block text-[10px] uppercase tracking-wider text-slate-500 mb-1">To date</label>
                    <input type="date" x-model="filters.date_to" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-100">
                </div>
                <div>
                    <label class="block text-[10px] uppercase tracking-wider text-slate-500 mb-1">Sort</label>
                    <select x-model="filters.sort" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-100">
                        <option value="newest">Newest first</option>
                        <option value="oldest">Oldest first</option>
                        <option value="urgency">Urgency</option>
                        <option value="priority">Priority score</option>
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <label class="flex items-center gap-2 text-xs text-slate-300 pb-2">
                        <input type="checkbox" x-model="filters.equity_only" class="rounded border-slate-700 bg-slate-950">
                        Equity flagged only
                    </label>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <button @click="applyFilters()" class="px-4 py-2 rounded-xl text-sm font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                    Apply filters
                </button>
                <button @click="resetFilters()" class="px-4 py-2 rounded-xl text-sm border border-slate-700 text-slate-300 hover:bg-slate-800">
                    Reset
                </button>
            </div>
        </div>

        <div x-show="loading" class="space-y-3">
            <div class="h-24 bg-slate-800/50 animate-pulse rounded-xl"></div>
            <div class="h-24 bg-slate-800/50 animate-pulse rounded-xl"></div>
        </div>

        <div x-show="!loading && items.length === 0" class="text-center py-12 border border-dashed border-slate-800 rounded-xl">
            <p class="text-slate-400">No requests match these filters.</p>
        </div>

        <div x-show="!loading && items.length > 0" class="space-y-3">
            <p class="text-xs text-slate-500" x-text="metaLabel"></p>
            <template x-for="item in items" :key="item.request_id">
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-3 hover:border-slate-700 transition">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="space-y-2 min-w-0 flex-1">
                            <div class="flex flex-wrap gap-2 text-xs">
                                <span class="px-2 py-0.5 rounded-full bg-slate-800 text-slate-200" x-text="'#' + item.request_id"></span>
                                <span class="px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-300 border border-emerald-500/20" x-text="item.sector || item.category"></span>
                                <span class="px-2 py-0.5 rounded-full capitalize border"
                                      :class="{
                                          'bg-red-500/10 text-red-400 border-red-500/20': item.urgency === 'high',
                                          'bg-yellow-500/10 text-yellow-400 border-yellow-500/20': item.urgency === 'medium',
                                          'bg-emerald-500/10 text-emerald-400 border-emerald-500/20': item.urgency === 'low'
                                      }"
                                      x-text="(item.urgency || 'low') + ' urgency'"></span>
                                <span class="px-2 py-0.5 rounded-full bg-slate-800 text-slate-400 capitalize" x-text="(item.status || '').replace('_', ' ')"></span>
                                <span x-show="item.equity_flag" class="px-2 py-0.5 rounded-full bg-rose-500/10 text-rose-300 border border-rose-500/30">Equity</span>
                            </div>
                            <p class="text-slate-100 font-medium" x-text="item.content"></p>
                            <div class="flex flex-wrap gap-3 text-[11px] text-slate-500">
                                <span x-text="formatDate(item.created_at)"></span>
                                <span x-text="'Score ' + (item.priority_score ?? 0).toFixed(1)"></span>
                                <span x-text="item.user?.phone_number || ''"></span>
                                <span x-show="item.cluster_summary" class="text-sky-400" x-text="item.cluster_summary"></span>
                            </div>
                        </div>
                        <button @click="openDetail(item)" class="px-3 py-1.5 text-xs rounded-lg bg-slate-800 text-slate-200 hover:bg-slate-700 shrink-0">
                            Details
                        </button>
                    </div>
                </div>
            </template>

            <div class="flex items-center justify-between pt-2" x-show="pagination.last_page > 1">
                <button @click="prevPage()" :disabled="pagination.current_page <= 1"
                        class="px-3 py-1.5 text-xs rounded-lg border border-slate-700 text-slate-300 disabled:opacity-40">Previous</button>
                <span class="text-xs text-slate-500" x-text="'Page ' + pagination.current_page + ' of ' + pagination.last_page"></span>
                <button @click="nextPage()" :disabled="pagination.current_page >= pagination.last_page"
                        class="px-3 py-1.5 text-xs rounded-lg border border-slate-700 text-slate-300 disabled:opacity-40">Next</button>
            </div>
        </div>

        <!-- Detail modal -->
        <div x-show="selected" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm" x-cloak>
            <div @click.away="selected = null" class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-xl p-6 space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-start gap-3">
                    <h3 class="text-lg font-bold text-slate-100">Request #<span x-text="selected?.request_id"></span></h3>
                    <button @click="selected = null" class="text-slate-400 hover:text-slate-200">&times;</button>
                </div>
                <p class="text-slate-200 text-sm" x-text="selected?.content"></p>
                <div class="grid grid-cols-2 gap-3 text-xs text-slate-400">
                    <div><span class="block text-slate-500 uppercase">Sector</span><span x-text="selected?.sector"></span></div>
                    <div><span class="block text-slate-500 uppercase">Category</span><span x-text="selected?.category"></span></div>
                    <div><span class="block text-slate-500 uppercase">Urgency</span><span class="capitalize" x-text="selected?.urgency"></span></div>
                    <div><span class="block text-slate-500 uppercase">Status</span><span class="capitalize" x-text="(selected?.status || '').replace('_', ' ')"></span></div>
                    <div><span class="block text-slate-500 uppercase">Submitted</span><span x-text="formatDate(selected?.created_at)"></span></div>
                    <div><span class="block text-slate-500 uppercase">Priority</span><span x-text="(selected?.priority_score ?? 0).toFixed(1)"></span></div>
                </div>
                <div x-show="selected?.raw_message">
                    <span class="text-[10px] uppercase text-slate-500">Raw message</span>
                    <p class="text-xs text-slate-400 italic mt-1" x-text="selected?.raw_message"></p>
                </div>
                <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
                    <button @click="updateStatus(selected.request_id, 'in_progress')" class="px-3 py-1.5 text-xs rounded-lg border border-sky-500/30 text-sky-300">Start work</button>
                    <button @click="updateStatus(selected.request_id, 'resolved')" class="px-3 py-1.5 text-xs rounded-lg border border-emerald-500/30 text-emerald-300">Mark resolved</button>
                    <button @click="selected = null" class="px-3 py-1.5 text-xs rounded-lg bg-slate-800 text-slate-300">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function requestsPage() {
            return {
                loading: true,
                items: [],
                selected: null,
                filterOptions: { categories: [], sectors: [], statuses: [], urgencies: [] },
                filters: {
                    q: '',
                    sector: '',
                    category: '',
                    urgency: '',
                    status: '',
                    date_from: '',
                    date_to: '',
                    sort: 'newest',
                    equity_only: false,
                },
                pagination: { current_page: 1, last_page: 1, total: 0, per_page: 20 },

                get metaLabel() {
                    return `${this.pagination.total || 0} request(s) found`;
                },

                async init() {
                    const params = new URLSearchParams(window.location.search);
                    ['q', 'sector', 'category', 'urgency', 'status', 'date_from', 'date_to', 'sort'].forEach((k) => {
                        if (params.has(k)) this.filters[k] = params.get(k);
                    });
                    if (params.get('equity_flag') === '1' || params.get('equity_flag') === 'true') {
                        this.filters.equity_only = true;
                    }
                    await this.fetchRequests();
                },

                buildQuery(page = 1) {
                    const q = new URLSearchParams();
                    q.set('page', String(page));
                    q.set('per_page', '20');
                    Object.entries(this.filters).forEach(([key, val]) => {
                        if (key === 'equity_only') {
                            if (val) q.set('equity_flag', '1');
                            return;
                        }
                        if (val !== '' && val != null) q.set(key, val);
                    });
                    return q;
                },

                async fetchRequests(page = 1) {
                    this.loading = true;
                    try {
                        const q = this.buildQuery(page);
                        const res = await fetch('/api/mp/issues?' + q.toString());
                        const data = await res.json();
                        if (data.status === 'success') {
                            this.filterOptions = data.filters || this.filterOptions;
                            const pageData = data.data || {};
                            this.items = pageData.data || [];
                            this.pagination = {
                                current_page: pageData.current_page || 1,
                                last_page: pageData.last_page || 1,
                                total: pageData.total || 0,
                                per_page: pageData.per_page || 20,
                            };
                        }
                    } catch (e) {
                        console.error(e);
                    } finally {
                        this.loading = false;
                    }
                },

                applyFilters() {
                    const q = this.buildQuery(1);
                    const url = '/mp/requests?' + q.toString();
                    window.history.replaceState({}, '', url);
                    this.fetchRequests(1);
                },

                resetFilters() {
                    this.filters = {
                        q: '', sector: '', category: '', urgency: '', status: '',
                        date_from: '', date_to: '', sort: 'newest', equity_only: false,
                    };
                    this.applyFilters();
                },

                prevPage() {
                    if (this.pagination.current_page > 1) {
                        this.fetchRequests(this.pagination.current_page - 1);
                    }
                },

                nextPage() {
                    if (this.pagination.current_page < this.pagination.last_page) {
                        this.fetchRequests(this.pagination.current_page + 1);
                    }
                },

                openDetail(item) {
                    this.selected = item;
                },

                formatDate(dateString) {
                    if (!dateString) return 'N/A';
                    return new Date(dateString).toLocaleString();
                },

                async updateStatus(id, status) {
                    try {
                        const res = await fetch(`/api/mp/requests/${id}/status`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                            body: JSON.stringify({ status }),
                        });
                        const data = await res.json();
                        if (data.status === 'success') {
                            this.selected = null;
                            await this.fetchRequests(this.pagination.current_page);
                        }
                    } catch (e) {
                        console.error(e);
                    }
                },
            }
        }
    </script>
</x-layout>
