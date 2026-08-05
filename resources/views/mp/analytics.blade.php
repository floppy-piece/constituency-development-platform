<x-layout title="Analytics & Trends - MP Portal">
    <div x-data="analyticsComponent()" x-init="initAnalytics()" class="space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-stone-400/40 pb-4">
            <div>
                <h1 class="text-2xl font-black text-stone-900 font-typewriter tracking-tight uppercase" >Analytics & Trends</h1>
                <p class="text-xs text-stone-700 font-typewriter mt-1" >Live operational reporting from constituent request data — turned into development planning signals.</p>
            </div>
            
            <button @click="fetchAnalytics()" class="bg-white hover:bg-stone-100 border border-stone-400 text-stone-900 font-typewriter font-semibold px-4 py-2 rounded text-xs transition flex items-center gap-2 shadow-xs">
                <svg class="w-4 h-4 text-emerald-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <span>Refresh Data</span>
            </button>
        </div>

        <!-- Loading Spinner -->
        <div x-show="loading" class="p-12 text-center text-stone-700 font-typewriter text-xs">
            <svg class="animate-spin h-8 w-8 text-emerald-900 mx-auto mb-3" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>Loading Analytics Data...</span>
        </div>

        <div x-show="!loading" class="space-y-6" x-cloak>
            <!-- Top Metrics Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 font-typewriter">
                <div class="torn-card p-5 border border-stone-300 shadow-sm">
                    <span class="text-[10px] font-bold text-stone-600 uppercase tracking-wider">Total Requests</span>
                    <div class="text-2xl font-black text-stone-900 font-ledger mt-1" x-text="metrics.total_requests || 0"></div>
                </div>

                <div class="torn-card p-5 border border-stone-300 shadow-sm">
                    <span class="text-[10px] font-bold text-stone-600 uppercase tracking-wider">Open Requests</span>
                    <div class="text-2xl font-black text-sky-900 font-ledger mt-1" x-text="metrics.open_requests || 0"></div>
                </div>

                <div class="torn-card p-5 border border-stone-300 shadow-sm">
                    <span class="text-[10px] font-bold text-stone-600 uppercase tracking-wider">Resolved Requests</span>
                    <div class="text-2xl font-black text-emerald-900 font-ledger mt-1" x-text="metrics.resolved_requests || 0"></div>
                </div>

                <div class="torn-card p-5 border border-stone-300 shadow-sm">
                    <span class="text-[10px] font-bold text-stone-600 uppercase tracking-wider">Active Wards</span>
                    <div class="text-2xl font-black text-stone-900 font-ledger mt-1" x-text="(metrics.ward_distribution || []).length"></div>
                </div>
            </div>

            <!-- Community Priorities -->
            <div class="torn-card p-6 space-y-4 border border-stone-300 shadow-sm font-typewriter">
                <div>
                    <h2 class="text-sm font-bold text-stone-900 uppercase">Community Priorities</h2>
                    <p class="text-xs text-stone-600 mt-0.5">Share of open requests by category — a planning signal for where to invest next.</p>
                </div>
                <div class="space-y-3">
                    <template x-for="(item, idx) in (metrics.community_priorities || [])" :key="item.category">
                        <div class="flex items-center gap-4">
                            <span class="w-6 text-xs font-bold text-stone-500" x-text="(idx + 1) + '.'"></span>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2 mb-1">
                                    <span class="text-xs font-semibold text-stone-900 truncate font-ledger" x-text="item.category"></span>
                                    <span class="text-[10px] text-stone-600 whitespace-nowrap"
                                          x-text="item.count + ' · ' + item.percentage + '%'"></span>
                                </div>
                                <div class="h-1.5 rounded-full bg-stone-200 border border-stone-300 overflow-hidden">
                                    <div class="h-full rounded-full bg-emerald-600 transition-all"
                                         :style="'width: ' + Math.max(item.percentage, 2) + '%'"></div>
                                </div>
                            </div>
                        </div>
                    </template>
                    <template x-if="!metrics.community_priorities || metrics.community_priorities.length === 0">
                        <p class="text-xs text-stone-500 py-4 text-center">No open requests to rank yet.</p>
                    </template>
                </div>

                <template x-if="metrics.ward_category_top && metrics.ward_category_top.length">
                    <div class="pt-4 border-t border-stone-300">
                        <h3 class="text-xs font-bold text-stone-900 uppercase mb-3">Top ward × category combinations</h3>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="row in metrics.ward_category_top" :key="row.ward_name + '-' + row.category">
                                <span class="text-[10px] bg-white border border-stone-300 text-stone-800 px-2.5 py-1 rounded shadow-xs font-ledger"
                                      x-text="row.ward_name + ' · ' + row.category + ' (' + row.count + ')'"></span>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 font-typewriter">
                <div class="lg:col-span-2 torn-card p-6 space-y-4 border border-stone-300 shadow-sm">
                    <h2 class="text-sm font-bold text-stone-900 uppercase">Requests by Ward</h2>
                    <div class="h-72">
                        <canvas id="wardChart"></canvas>
                    </div>
                </div>

                <div class="torn-card p-6 space-y-4 border border-stone-300 shadow-sm">
                    <h2 class="text-sm font-bold text-stone-900 uppercase">Category Breakdown</h2>
                    <div class="h-72 flex items-center justify-center">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Ward Severity Table -->
            <div class="torn-card p-6 space-y-4 border border-stone-300 shadow-sm font-typewriter">
                <div>
                    <h2 class="text-sm font-bold text-stone-900 uppercase">Ward Severity</h2>
                    <p class="text-xs text-stone-600 mt-0.5">Color-coded by share of high-urgency reports — 80% of heatmap impact without extra geocoding.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-stone-800">
                        <thead class="text-[10px] uppercase bg-stone-200 text-stone-700 font-bold">
                            <tr>
                                <th class="p-2.5 rounded-l">Ward Name</th>
                                <th class="p-2.5">Total Requests</th>
                                <th class="p-2.5">High Urgency</th>
                                <th class="p-2.5">High Share</th>
                                <th class="p-2.5 rounded-r">Severity</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-300 font-ledger">
                            <template x-for="ward in metrics.ward_distribution" :key="ward.ward_name">
                                <tr>
                                    <td class="p-2.5 font-semibold text-stone-900" x-text="ward.ward_name || 'Unassigned'"></td>
                                    <td class="p-2.5" x-text="ward.total_requests"></td>
                                    <td class="p-2.5" x-text="ward.high_urgency_count ?? 0"></td>
                                    <td class="p-2.5" x-text="(ward.high_urgency_share ?? 0) + '%'"></td>
                                    <td class="p-2.5">
                                        <span class="px-2 py-0.5 text-[10px] rounded font-bold uppercase tracking-wide border shadow-xs"
                                              :class="{
                                                  'bg-red-100 text-red-900 border-red-300': ward.severity === 'high',
                                                  'bg-amber-100 text-amber-900 border-amber-300': ward.severity === 'medium',
                                                  'bg-emerald-100 text-emerald-900 border-emerald-300': ward.severity === 'low' || !ward.severity
                                              }"
                                              x-text="ward.severity || 'low'">
                                        </span>
                                    </td>
                                </tr>
                            </template>
                            <template x-if="!metrics.ward_distribution || metrics.ward_distribution.length === 0">
                                <tr>
                                    <td colspan="5" class="p-4 text-center text-stone-500">No ward metrics recorded yet.</td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        function analyticsComponent() {
            return {
                loading: true,
                metrics: {
                    total_requests: 0,
                    resolved_requests: 0,
                    open_requests: 0,
                    categories: {},
                    community_priorities: [],
                    ward_distribution: [],
                    ward_category_top: []
                },
                wardChartInstance: null,
                categoryChartInstance: null,

                initAnalytics() {
                    Chart.defaults.color = '#292524';
                    Chart.defaults.borderColor = '#d6d3d1';
                    Chart.defaults.font.family = 'Courier New, monospace';
                    this.fetchAnalytics();
                },

                fetchAnalytics() {
                    this.loading = true;

                    fetch('/api/mp/analytics/data')
                        .then(res => res.json())
                        .then(res => {
                            if (res.status === 'success') {
                                this.metrics = res.data;
                                this.$nextTick(() => {
                                    this.renderCharts();
                                });
                            }
                        })
                        .catch(err => console.error('Failed to load analytics data:', err))
                        .finally(() => {
                            this.loading = false;
                        });
                },

                renderCharts() {
                    if (this.wardChartInstance) this.wardChartInstance.destroy();
                    if (this.categoryChartInstance) this.categoryChartInstance.destroy();

                    const wardCtx = document.getElementById('wardChart')?.getContext('2d');
                    if (wardCtx) {
                        const wardLabels = (this.metrics.ward_distribution || []).map(w => w.ward_name || 'Unknown');
                        const wardValues = (this.metrics.ward_distribution || []).map(w => w.total_requests);
                        const wardColors = (this.metrics.ward_distribution || []).map(w => {
                            if (w.severity === 'high') return '#fecaca';
                            if (w.severity === 'medium') return '#fef08a';
                            return '#a7f3d0';
                        });

                        const wardBorderColors = (this.metrics.ward_distribution || []).map(w => {
                            if (w.severity === 'high') return '#f87171';
                            if (w.severity === 'medium') return '#facc15';
                            return '#34d399';
                        });

                        this.wardChartInstance = new Chart(wardCtx, {
                            type: 'bar',
                            data: {
                                labels: wardLabels,
                                datasets: [{
                                    label: 'Requests',
                                    data: wardValues,
                                    backgroundColor: wardColors,
                                    borderColor: wardBorderColors,
                                    borderWidth: 1,
                                    borderRadius: 2
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: { legend: { display: false } },
                                scales: { 
                                    y: { beginAtZero: true, grid: { color: '#e7e5e4' } },
                                    x: { grid: { display: false } }
                                }
                            }
                        });
                    }

                    const categoryCtx = document.getElementById('categoryChart')?.getContext('2d');
                    if (categoryCtx) {
                        const catLabels = Object.keys(this.metrics.categories || {});
                        const catValues = Object.values(this.metrics.categories || {});

                        this.categoryChartInstance = new Chart(categoryCtx, {
                            type: 'doughnut',
                            data: {
                                labels: catLabels,
                                datasets: [{
                                    data: catValues,
                                    backgroundColor: ['#a7f3d0', '#bae6fd', '#fef08a', '#e9d5ff', '#fecaca', '#7dd3fc'],
                                    borderColor: '#f2eee3',
                                    borderWidth: 2
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                cutout: '70%'
                            }
                        });
                    }
                }
            };
        }
    </script>
</x-layout>