<x-layout title="MP Dashboard">
    <div x-data="dashboardPage()" x-init="initDashboard()" class="space-y-6">
        
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-100">Constituency Requests Dashboard</h1>
                <p class="text-sm text-slate-400">Real-time issues submitted by citizens in your constituency.</p>
            </div>
            
            <button @click="fetchDashboard()" class="inline-flex items-center space-x-2 bg-slate-900 border border-slate-800 hover:bg-slate-800 text-slate-300 px-4 py-2 rounded-xl text-sm transition">
                <svg class="w-4 h-4" x-bind:class="loading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                <span>Refresh Data</span>
            </button>
        </div>

        <!-- Metrics Overview Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl shadow-lg flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Requests</span>
                    <p class="text-3xl font-black text-slate-100 mt-1" x-text="metrics.total_requests || 0"></p>
                </div>
                <div class="p-3 bg-emerald-500/10 text-emerald-400 rounded-xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                </div>
            </div>

            <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl shadow-lg flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">High Urgency</span>
                    <p class="text-3xl font-black text-red-400 mt-1" x-text="metrics.high_urgency_requests || 0"></p>
                </div>
                <div class="p-3 bg-red-500/10 text-red-400 rounded-xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
            </div>

            <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl shadow-lg flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Assigned Constituency</span>
                    <p class="text-xl font-bold text-emerald-400 mt-1" x-text="mpInfo.constituency || 'N/A'"></p>
                </div>
                <div class="p-3 bg-blue-500/10 text-blue-400 rounded-xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                </div>
            </div>
        </div>

        <!-- Requests Feed Table / Cards -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
            <h2 class="text-lg font-bold text-slate-100">Recent Constituent Issues</h2>

            <!-- Loading Skeleton -->
            <div x-show="loading" class="space-y-3 py-4">
                <div class="h-24 bg-slate-800/50 animate-pulse rounded-xl"></div>
                <div class="h-24 bg-slate-800/50 animate-pulse rounded-xl"></div>
                <div class="h-24 bg-slate-800/50 animate-pulse rounded-xl"></div>
            </div>

            <!-- Empty State -->
            <div x-show="!loading && requests.length === 0" class="text-center py-12 border border-dashed border-slate-800 rounded-xl">
                <p class="text-slate-400">No active issues submitted for your constituency yet.</p>
            </div>

            <!-- Detailed Requests Feed -->
            <div x-show="!loading && requests.length > 0" class="space-y-4">
                <!-- Updated x-for loop with index fallback -->
                <template x-for="(req, index) in requests" :key="req.request_id || req.id || index">
                    <div class="bg-slate-950/60 border border-slate-800 rounded-xl p-5 space-y-4 hover:border-slate-700 transition">
                        
                        <!-- Top Metadata Row -->
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-800/80 pb-3">
                            <div class="flex items-center space-x-2">
                                <span class="px-2.5 py-0.5 text-xs rounded-full font-bold uppercase tracking-wide"
                                    :class="{
                                        'bg-red-500/10 text-red-400 border border-red-500/20': req.urgency === 'high',
                                        'bg-yellow-500/10 text-yellow-400 border border-yellow-500/20': req.urgency === 'medium',
                                        'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20': req.urgency === 'low'
                                    }"
                                    x-text="(req.urgency || 'low') + ' urgency'">
                                </span>
                                
                                <span class="text-xs bg-slate-800 text-slate-300 px-2.5 py-0.5 rounded-full font-medium" 
                                      x-text="req.category || 'General'">
                                </span>

                                <template x-if="req.upload_file_path">
                                    <span class="text-xs bg-blue-500/10 text-blue-400 border border-blue-500/20 px-2.5 py-0.5 rounded-full font-medium"
                                          x-text="'📎 ' + (req.file_type || 'Attachment')">
                                    </span>
                                </template>
                            </div>

                            <span class="text-xs text-slate-500" x-text="formatDate(req.created_at)"></span>
                        </div>

                        <!-- Content Preview -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                            <!-- AI Summary -->
                            <div class="space-y-1">
                                <span class="text-xs font-semibold text-emerald-400 uppercase tracking-wider">AI Summary / Content</span>
                                <p class="text-slate-200 text-sm font-medium leading-relaxed" x-text="req.content || 'No translated content.'"></p>
                            </div>

                            <!-- Raw Original Text -->
                            <div class="space-y-1 bg-slate-900/50 p-3 rounded-lg border border-slate-800/50">
                                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Raw Message</span>
                                <p class="text-slate-400 text-xs italic leading-relaxed" x-text="req.raw_message || 'N/A'"></p>
                            </div>
                        </div>

                        <!-- Details & Action Bar -->
                        <div class="flex flex-wrap items-center justify-between pt-2 text-xs text-slate-400 gap-2 border-t border-slate-800/50">
                            <div class="flex items-center space-x-4">
                                <span x-text="'📱 Phone: ' + (req.user?.phone_number || 'N/A')"></span>
                                <span>•</span>
                                <span x-text="'🔁 Reports Count: ' + (req.similarity_hash || 1)"></span>
                            </div>

                            <div class="flex items-center space-x-2">
                                <button @click="openModal(req)" class="px-3 py-1.5 text-xs bg-slate-800 hover:bg-slate-700 text-slate-200 font-semibold rounded-lg transition">
                                    View Details
                                </button>
                                <button @click="resolveRequest(req.request_id || req.id)" class="px-3 py-1.5 text-xs bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 border border-emerald-500/20 font-semibold rounded-lg transition">
                                    Mark Resolved
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Request Detail Modal -->
        <div x-show="selectedReq" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm" x-cloak>
            <div @click.away="selectedReq = null" class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-2xl p-6 space-y-5 shadow-2xl overflow-y-auto max-h-[90vh]">
                
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-lg font-bold text-slate-100">Request #<span x-text="selectedReq?.request_id || selectedReq?.id"></span></h3>
                    <button @click="selectedReq = null" class="text-slate-400 hover:text-slate-200">&times;</button>
                </div>

                <div class="space-y-4 text-sm text-slate-300">
                    <div>
                        <span class="text-xs uppercase text-slate-500 font-bold">AI Translated Summary</span>
                        <p class="text-slate-100 font-medium mt-1 bg-slate-950 p-3 rounded-xl border border-slate-800" x-text="selectedReq?.content"></p>
                    </div>

                    <div>
                        <span class="text-xs uppercase text-slate-500 font-bold">Original Message</span>
                        <p class="text-slate-400 italic mt-1 bg-slate-950 p-3 rounded-xl border border-slate-800" x-text="selectedReq?.raw_message"></p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="text-xs uppercase text-slate-500 font-bold">Category</span>
                            <p class="text-slate-200 mt-0.5" x-text="selectedReq?.category || 'General'"></p>
                        </div>
                        <div>
                            <span class="text-xs uppercase text-slate-500 font-bold">Urgency</span>
                            <p class="text-slate-200 mt-0.5 capitalize" x-text="selectedReq?.urgency"></p>
                        </div>
                        <div>
                            <span class="text-xs uppercase text-slate-500 font-bold">Phone Number</span>
                            <p class="text-slate-200 mt-0.5" x-text="selectedReq?.user?.phone_number || 'N/A'"></p>
                        </div>
                        <div>
                            <span class="text-xs uppercase text-slate-500 font-bold">Submitted Date</span>
                            <p class="text-slate-200 mt-0.5" x-text="formatDate(selectedReq?.created_at)"></p>
                        </div>
                    </div>

                    <template x-if="selectedReq?.upload_file_path">
                        <div>
                            <span class="text-xs uppercase text-slate-500 font-bold">Attachment</span>
                            <div class="mt-2">
                                <a :href="'/' + selectedReq.upload_file_path" target="_blank" class="inline-flex items-center space-x-2 bg-blue-500/10 hover:bg-blue-500/20 text-blue-400 border border-blue-500/20 px-4 py-2 rounded-xl text-xs transition">
                                    <span>View Attached File</span>
                                </a>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="flex justify-end pt-3 border-t border-slate-800">
                    <button @click="selectedReq = null" class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl text-xs font-semibold hover:bg-slate-700">Close</button>
                </div>
            </div>
        </div>

    </div>

    <script>
        function dashboardPage() {
            return {
                loading: true,
                mpInfo: {},
                metrics: {},
                requests: [],
                selectedReq: null,

                async initDashboard() {
                    await this.fetchDashboard();
                },

                async fetchDashboard() {
                    this.loading = true;
                    try {
                        const response = await fetch('/api/mp/dashboard');
                        const data = await response.json();

                        if (data.status === 'success') {
                            this.mpInfo = data.mp_info || {};
                            this.metrics = data.metrics || {};
                            this.requests = data.recent_requests || [];
                        }
                    } catch (err) {
                        console.error('Failed to load dashboard:', err);
                    } finally {
                        this.loading = false;
                    }
                },

                openModal(req) {
                    this.selectedReq = req;
                },

                formatDate(dateString) {
                    if (!dateString) return 'N/A';
                    return new Date(dateString).toLocaleString();
                },

                async resolveRequest(id) {
                    try {
                        const response = await fetch(`/api/mp/requests/${id}/resolve`, {
                            method: 'POST'
                        });
                        const data = await response.json();
                        if (data.status === 'success') {
                            this.requests = this.requests.filter(r => (r.request_id || r.id) !== id);
                            if (this.metrics.total_requests > 0) {
                                this.metrics.total_requests--;
                            }
                        }
                    } catch (err) {
                        console.error('Failed to resolve request:', err);
                    }
                }
            }
        }
    </script>
</x-layout>