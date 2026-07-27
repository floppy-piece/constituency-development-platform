<x-layout title="Analytics & Trends - MP Portal">
    <div x-data="analyticsComponent()" x-init="initAnalytics()" class="space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-slate-100 tracking-tight">Analytics & Trends</h1>
                <p class="text-sm text-slate-400">Live operational reporting from constituent request data — turned into development planning signals.</p>
            </div>
            
            <button @click="fetchAnalytics()" class="bg-slate-900 hover:bg-slate-800 border border-slate-800 text-slate-200 font-medium px-4 py-2.5 rounded-xl transition text-sm flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <span>Refresh Data</span>
            </button>
        </div>

        <!-- Loading Spinner -->
        <div x-show="loading" class="p-12 text-center text-slate-400">
            <svg class="animate-spin h-8 w-8 text-emerald-400 mx-auto mb-3" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>Loading Analytics Data...</span>
        </div>

        <div x-show="!loading" class="space-y-6" x-cloak>
            <!-- Top Metrics Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Requests</span>
                    <div class="text-3xl font-black text-slate-100 mt-2" x-text="metrics.total_requests || 0"></div>
                </div>

                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Open Requests</span>
                    <div class="text-3xl font-black text-sky-400 mt-2" x-text="metrics.open_requests || 0"></div>
                </div>

                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Resolved Requests</span>
                    <div class="text-3xl font-black text-emerald-400 mt-2" x-text="metrics.resolved_requests || 0"></div>
                </div>

                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Active Wards</span>
                    <div class="text-3xl font-black text-slate-100 mt-2" x-text="(metrics.ward_distribution || []).length"></div>
                </div>
            </div>

            <!-- Community Priorities -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
                <div>
                    <h2 class="text-lg font-bold text-slate-200">Community Priorities</h2>
                    <p class="text-sm text-slate-400 mt-1">Share of open requests by category — a planning signal for where to invest next.</p>
                </div>
                <div class="space-y-3">
                    <template x-for="(item, idx) in (metrics.community_priorities || [])" :key="item.category">
                        <div class="flex items-center gap-4">
                            <span class="w-6 text-sm font-bold text-slate-500" x-text="(idx + 1) + '.'"></span>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2 mb-1">
                                    <span class="text-sm font-semibold text-slate-100 truncate" x-text="item.category"></span>
                                    <span class="text-xs text-slate-400 whitespace-nowrap"
                                          x-text="item.count + ' · ' + item.percentage + '%'"></span>
                                </div>
                                <div class="h-2 rounded-full bg-slate-800 overflow-hidden">
                                    <div class="h-full rounded-full bg-emerald-500/80 transition-all"
                                         :style="'width: ' + Math.max(item.percentage, 2) + '%'"></div>
                                </div>
                            </div>
                        </div>
                    </template>
                    <template x-if="!metrics.community_priorities || metrics.community_priorities.length === 0">
                        <p class="text-sm text-slate-500 py-4 text-center">No open requests to rank yet.</p>
                    </template>
                </div>

                <template x-if="metrics.ward_category_top && metrics.ward_category_top.length">
                    <div class="pt-4 border-t border-slate-800">
                        <h3 class="text-sm font-semibold text-slate-300 mb-3">Top ward × category combinations</h3>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="row in metrics.ward_category_top" :key="row.ward_name + '-' + row.category">
                                <span class="text-xs bg-slate-950 border border-slate-800 text-slate-300 px-3 py-1.5 rounded-lg"
                                      x-text="row.ward_name + ' · ' + row.category + ' (' + row.count + ')'"></span>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
                    <h2 class="text-lg font-bold text-slate-200">Requests by Ward</h2>
                    <div class="h-72">
                        <canvas id="wardChart"></canvas>
                    </div>
                </div>

                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
                    <h2 class="text-lg font-bold text-slate-200">Category Breakdown</h2>
                    <div class="h-72 flex items-center justify-center">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Ward Severity Table -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
                <div>
                    <h2 class="text-lg font-bold text-slate-200">Ward Severity</h2>
                    <p class="text-sm text-slate-400 mt-1">Color-coded by share of high-urgency reports — 80% of heatmap impact without extra geocoding.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-300">
                        <thead class="text-xs uppercase bg-slate-950 text-slate-400">
                            <tr>
                                <th class="p-3 rounded-l-xl">Ward Name</th>
                                <th class="p-3">Total Requests</th>
                                <th class="p-3">High Urgency</th>
                                <th class="p-3">High Share</th>
                                <th class="p-3 rounded-r-xl">Severity</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800">
                            <template x-for="ward in metrics.ward_distribution" :key="ward.ward_name">
                                <tr>
                                    <td class="p-3 font-semibold text-slate-100" x-text="ward.ward_name || 'Unassigned'"></td>
                                    <td class="p-3" x-text="ward.total_requests"></td>
                                    <td class="p-3" x-text="ward.high_urgency_count ?? 0"></td>
                                    <td class="p-3" x-text="(ward.high_urgency_share ?? 0) + '%'"></td>
                                    <td class="p-3">
                                        <span class="px-2.5 py-0.5 text-xs rounded-full font-bold uppercase tracking-wide"
                                              :class="{
                                                  'bg-red-500/10 text-red-400 border border-red-500/20': ward.severity === 'high',
                                                  'bg-amber-500/10 text-amber-400 border border-amber-500/20': ward.severity === 'medium',
                                                  'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20': ward.severity === 'low' || !ward.severity
                                              }"
                                              x-text="ward.severity || 'low'">
                                        </span>
                                    </td>
                                </tr>
                            </template>
                            <template x-if="!metrics.ward_distribution || metrics.ward_distribution.length === 0">
                                <tr>
                                    <td colspan="5" class="p-4 text-center text-slate-500">No ward metrics recorded yet.</td>
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
                    Chart.defaults.color = '#94a3b8';
                    Chart.defaults.borderColor = '#1e293b';
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
                            if (w.severity === 'high') return '#f87171';
                            if (w.severity === 'medium') return '#fbbf24';
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
                                    borderRadius: 6
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: { legend: { display: false } },
                                scales: { y: { beginAtZero: true } }
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
                                    backgroundColor: ['#34d399', '#60a5fa', '#f59e0b', '#c084fc', '#f87171', '#38bdf8'],
                                    borderWidth: 0
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
