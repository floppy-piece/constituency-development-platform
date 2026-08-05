<x-layout title="All Requests">
    <div x-data="requestsPage()" x-init="init()" class="space-y-6">

        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4 border-b border-stone-400/40 pb-4">
            <div>
                <h1 class="text-2xl font-bold text-stone-900 font-typewriter tracking-wide uppercase" style="color:white;">All Requests</h1>
                <p class="text-xs text-stone-700 font-typewriter mt-1" style="color:white">Search and filter every citizen submission for your constituency.</p>
            </div>
            <a href="/mp/dashboard" class="text-xs text-emerald-900 font-typewriter font-semibold hover:underline">← Back to sector overview</a>
        </div>

        <!-- Filters -->
        <div class="torn-card p-5 space-y-4 border border-stone-300">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3 font-typewriter">
                <div class="md:col-span-2">
                    <label class="block text-[10px] uppercase tracking-wider text-stone-700 mb-1 font-bold">Search</label>
                    <input type="search" x-model="filters.q" @keydown.enter="applyFilters()"
                           placeholder="Search summary, raw message, category…"
                           class="w-full bg-white border border-stone-300 rounded px-3 py-2 text-xs text-stone-900 focus:outline-none focus:border-stone-500 shadow-xs">
                </div>
                <div>
                    <label class="block text-[10px] uppercase tracking-wider text-stone-700 mb-1 font-bold">Sector</label>
                    <select x-model="filters.sector" class="w-full bg-white border border-stone-300 rounded px-3 py-2 text-xs text-stone-900 shadow-xs">
                        <option value="">All sectors</option>
                        <template x-for="s in filterOptions.sectors" :key="s">
                            <option :value="s" x-text="s"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] uppercase tracking-wider text-stone-700 mb-1 font-bold">Category</label>
                    <select x-model="filters.category" class="w-full bg-white border border-stone-300 rounded px-3 py-2 text-xs text-stone-900 shadow-xs">
                        <option value="">All categories</option>
                        <template x-for="c in filterOptions.categories" :key="c">
                            <option :value="c" x-text="c"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] uppercase tracking-wider text-stone-700 mb-1 font-bold">Urgency</label>
                    <select x-model="filters.urgency" class="w-full bg-white border border-stone-300 rounded px-3 py-2 text-xs text-stone-900 shadow-xs">
                        <option value="">All</option>
                        <option value="high">High</option>
                        <option value="medium">Medium</option>
                        <option value="low">Low</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] uppercase tracking-wider text-stone-700 mb-1 font-bold">Status</label>
                    <select x-model="filters.status" class="w-full bg-white border border-stone-300 rounded px-3 py-2 text-xs text-stone-900 shadow-xs">
                        <option value="">All statuses</option>
                        <template x-for="st in filterOptions.statuses" :key="st">
                            <option :value="st" x-text="st.replace('_', ' ')"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] uppercase tracking-wider text-stone-700 mb-1 font-bold">From date</label>
                    <input type="date" x-model="filters.date_from" class="w-full bg-white border border-stone-300 rounded px-3 py-2 text-xs text-stone-900 shadow-xs">
                </div>
                <div>
                    <label class="block text-[10px] uppercase tracking-wider text-stone-700 mb-1 font-bold">To date</label>
                    <input type="date" x-model="filters.date_to" class="w-full bg-white border border-stone-300 rounded px-3 py-2 text-xs text-stone-900 shadow-xs">
                </div>
                <div>
                    <label class="block text-[10px] uppercase tracking-wider text-stone-700 mb-1 font-bold">Sort</label>
                    <select x-model="filters.sort" class="w-full bg-white border border-stone-300 rounded px-3 py-2 text-xs text-stone-900 shadow-xs">
                        <option value="newest">Newest first</option>
                        <option value="oldest">Oldest first</option>
                        <option value="urgency">Urgency</option>
                        <option value="priority">Priority score</option>
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <label class="flex items-center gap-2 text-xs text-stone-800 pb-2 font-typewriter">
                        <input type="checkbox" x-model="filters.equity_only" class="rounded border-stone-400 bg-white shadow-xs">
                        Equity flagged only
                    </label>
                </div>
            </div>
            <div class="flex flex-wrap gap-2 font-typewriter pt-2 border-t border-stone-300">
                <button @click="applyFilters()" class="px-4 py-2 text-xs rounded bg-emerald-100 text-emerald-900 border border-emerald-300 font-semibold shadow-xs">
                    Apply filters
                </button>
                <button @click="resetFilters()" class="px-4 py-2 text-xs rounded border border-stone-400 bg-white text-stone-900 hover:bg-stone-100 shadow-xs">
                    Reset
                </button>
            </div>
        </div>

        <div x-show="loading" class="space-y-3">
            <div class="h-24 bg-stone-200 animate-pulse rounded"></div>
            <div class="h-24 bg-stone-200 animate-pulse rounded"></div>
        </div>

        <div x-show="!loading && items.length === 0" class="text-center py-12 border border-dashed border-stone-400 rounded">
            <p class="text-stone-700 font-typewriter text-xs">No requests match these filters.</p>
        </div>

        <div x-show="!loading && items.length > 0" class="space-y-3">
            <p class="text-xs text-stone-600 font-typewriter font-semibold" x-text="metaLabel"></p>
            <template x-for="item in items" :key="item.request_id">
                <div class="torn-card p-5 space-y-3 border border-stone-300 shadow-sm hover:border-stone-400 transition">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="space-y-2 min-w-0 flex-1">
                            <div class="flex flex-wrap gap-2 text-[10px] font-typewriter font-semibold">
                                <span class="px-2 py-0.5 rounded bg-white border border-stone-300 text-stone-900 shadow-xs" x-text="'#' + item.request_id"></span>
                                <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-900 border border-emerald-300" x-text="item.sector || item.category"></span>
                                <span class="px-2 py-0.5 rounded capitalize border"
                                      :class="{
                                          'bg-red-100 text-red-900 border-red-300': item.urgency === 'high',
                                          'bg-amber-100 text-amber-900 border-amber-300': item.urgency === 'medium',
                                          'bg-emerald-100 text-emerald-900 border-emerald-300': item.urgency === 'low'
                                      }"
                                      x-text="(item.urgency || 'low') + ' urgency'"></span>
                                <span class="px-2 py-0.5 rounded bg-stone-200 text-stone-800 capitalize" x-text="(item.status || '').replace('_', ' ')"></span>
                                <span x-show="item.equity_flag" class="px-2 py-0.5 rounded bg-rose-100 text-rose-900 border border-rose-300">Equity</span>
                            </div>
                            <p class="text-xs font-ledger text-stone-900 font-medium" x-text="item.content"></p>
                            <div class="flex flex-wrap gap-3 text-[10px] text-stone-600 font-typewriter">
                                <span x-text="formatDate(item.created_at)"></span>
                                <span x-text="'Score ' + (item.priority_score ?? 0).toFixed(1)"></span>
                                <span x-text="item.user?.phone_number || ''"></span>
                                <span x-show="item.cluster_summary" class="text-sky-900 font-semibold" x-text="item.cluster_summary"></span>
                            </div>
                        </div>
                        <button @click="openDetail(item)" class="px-3 py-1.5 text-xs rounded bg-[#e6dfd1] border border-stone-400/60 text-stone-900 hover:bg-[#dcd5c1] shadow-xs font-typewriter shrink-0">
                            Details
                        </button>
                    </div>
                </div>
            </template>

            <div class="flex items-center justify-between pt-2 font-typewriter" x-show="pagination.last_page > 1">
                <button @click="prevPage()" :disabled="pagination.current_page <= 1"
                        class="px-3 py-1.5 text-xs rounded border border-stone-400 bg-white text-stone-900 disabled:opacity-40 hover:bg-stone-100 shadow-xs">Previous</button>
                <span class="text-xs text-stone-700 font-medium" x-text="'Page ' + pagination.current_page + ' of ' + pagination.last_page"></span>
                <button @click="nextPage()" :disabled="pagination.current_page >= pagination.last_page"
                        class="px-3 py-1.5 text-xs rounded border border-stone-400 bg-white text-stone-900 disabled:opacity-40 hover:bg-stone-100 shadow-xs">Next</button>
            </div>
        </div>

        <!-- Detail modal -->
        <div x-show="selected" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-stone-900/60 backdrop-blur-xs" x-cloak>
            <div @click.away="selected = null" class="bg-[#f2eee3] border border-stone-400 rounded-lg w-full max-w-xl p-6 space-y-4 max-h-[90vh] overflow-y-auto shadow-2xl font-typewriter">
                <div class="flex justify-between items-start gap-3 border-b border-stone-300 pb-3">
                    <h3 class="text-base font-bold text-stone-900 uppercase">Request #<span x-text="selected?.request_id"></span></h3>
                    <button @click="selected = null" class="text-stone-600 hover:text-stone-900 font-bold text-lg leading-none">&times;</button>
                </div>
                <p class="text-stone-900 text-xs font-ledger" x-text="selected?.content"></p>
                <div class="grid grid-cols-2 gap-3 text-[10px] text-stone-700 border-t border-stone-300 pt-3">
                    <div><span class="block text-stone-500 uppercase font-bold">Sector</span><span class="text-stone-900" x-text="selected?.sector"></span></div>
                    <div><span class="block text-stone-500 uppercase font-bold">Category</span><span class="text-stone-900" x-text="selected?.category"></span></div>
                    <div><span class="block text-stone-500 uppercase font-bold">Urgency</span><span class="capitalize text-stone-900" x-text="selected?.urgency"></span></div>
                    <div><span class="block text-stone-500 uppercase font-bold">Status</span><span class="capitalize text-stone-900" x-text="(selected?.status || '').replace('_', ' ')"></span></div>
                    <div><span class="block text-stone-500 uppercase font-bold">Submitted</span><span class="text-stone-900" x-text="formatDate(selected?.created_at)"></span></div>
                    <div><span class="block text-stone-500 uppercase font-bold">Priority</span><span class="text-stone-900" x-text="(selected?.priority_score ?? 0).toFixed(1)"></span></div>
                </div>
                <div x-show="selected?.raw_message" class="border-t border-stone-300 pt-3">
                    <span class="text-[10px] uppercase text-stone-500 font-bold">Raw message</span>
                    <p class="text-xs text-stone-700 italic font-ledger mt-1" x-text="selected?.raw_message"></p>
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-stone-300">
                    <button @click="updateStatus(selected.request_id, 'in_progress')" class="px-3 py-1.5 text-xs rounded border border-sky-300 bg-sky-100 text-sky-900 font-semibold shadow-xs">Start work</button>
                    <button @click="updateStatus(selected.request_id, 'resolved')" class="px-3 py-1.5 text-xs rounded border border-emerald-300 bg-emerald-100 text-emerald-900 font-semibold shadow-xs">Mark resolved</button>
                    <button @click="selected = null" class="px-3 py-1.5 text-xs rounded bg-stone-300 text-stone-900 font-semibold hover:bg-stone-400 shadow-xs">Close</button>
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