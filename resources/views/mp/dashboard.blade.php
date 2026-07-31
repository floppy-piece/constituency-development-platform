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
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-5">
            <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl shadow-lg flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Open Requests</span>
                    <p class="text-3xl font-black text-slate-100 mt-1" x-text="metrics.total_requests || 0"></p>
                </div>
                <div class="p-3 bg-emerald-500/10 text-emerald-400 rounded-xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                </div>
            </div>

            <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl shadow-lg flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Needs Review</span>
                    <p class="text-3xl font-black text-amber-400 mt-1" x-text="metrics.needs_review_count || 0"></p>
                </div>
                <div class="p-3 bg-amber-500/10 text-amber-400 rounded-xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
            </div>

            <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl shadow-lg flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Awaiting Citizen Confirm</span>
                    <p class="text-3xl font-black text-sky-400 mt-1" x-text="metrics.awaiting_verification_count || 0"></p>
                </div>
                <div class="p-3 bg-sky-500/10 text-sky-400 rounded-xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
                </div>
            </div>

            <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl shadow-lg flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Equity Flagged</span>
                    <p class="text-3xl font-black text-rose-300 mt-1" x-text="metrics.equity_flagged_count || 0"></p>
                </div>
                <div class="p-3 bg-rose-500/10 text-rose-300 rounded-xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"></path></svg>
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

        <!-- Sector overview -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-slate-100">Issues by sector</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Grouped citizen complaints (roads, water, drainage, fire, etc.). Open a sector to browse every request.</p>
                </div>
                <a href="/mp/requests" class="inline-flex items-center justify-center px-4 py-2 rounded-xl text-sm font-semibold bg-emerald-500/15 text-emerald-300 border border-emerald-500/30 hover:bg-emerald-500/25 transition">
                    Browse all requests
                </a>
            </div>

            <div x-show="loading" class="grid grid-cols-1 md:grid-cols-2 gap-4 py-2">
                <div class="h-36 bg-slate-800/50 animate-pulse rounded-xl"></div>
                <div class="h-36 bg-slate-800/50 animate-pulse rounded-xl"></div>
            </div>

            <div x-show="!loading && sectors.length === 0" class="text-center py-12 border border-dashed border-slate-800 rounded-xl">
                <p class="text-slate-400">No active issues submitted for your constituency yet.</p>
                <a href="/mp/requests" class="inline-block mt-3 text-sm text-emerald-400 hover:text-emerald-300">Search historical requests →</a>
            </div>

            <div x-show="!loading && sectors.length > 0" class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <template x-for="sector in sectors" :key="sector.sector">
                    <div class="bg-slate-950/70 border border-slate-800 rounded-2xl p-5 space-y-4 hover:border-slate-700 transition">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-lg font-semibold text-slate-100" x-text="sector.sector"></h3>
                                <p class="text-xs text-slate-500 mt-1">
                                    <span x-text="sector.count"></span> open request<span x-text="sector.count === 1 ? '' : 's'"></span>
                                    · avg score <span x-text="(sector.avg_priority ?? 0).toFixed(1)"></span>
                                </p>
                            </div>
                            <a :href="'/mp/requests?sector=' + encodeURIComponent(sector.sector)"
                               class="shrink-0 px-3 py-1.5 text-xs rounded-lg bg-emerald-500/10 text-emerald-300 border border-emerald-500/20 hover:bg-emerald-500/20">
                                View all
                            </a>
                        </div>

                        <div class="flex flex-wrap gap-2 text-[11px]">
                            <span class="px-2 py-0.5 rounded-full bg-red-500/10 text-red-300 border border-red-500/20"
                                  x-show="sector.high_urgency > 0"
                                  x-text="sector.high_urgency + ' high urgency'"></span>
                            <span class="px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-300 border border-amber-500/20"
                                  x-show="sector.needs_review > 0"
                                  x-text="sector.needs_review + ' need review'"></span>
                            <span class="px-2 py-0.5 rounded-full bg-slate-800 text-slate-400"
                                  x-show="sector.latest_at"
                                  x-text="'Latest ' + formatDate(sector.latest_at)"></span>
                        </div>

                        <div class="space-y-2 border-t border-slate-800 pt-3">
                            <p class="text-[10px] uppercase tracking-wider text-slate-500">Top in this sector</p>
                            <template x-for="sample in (sector.samples || [])" :key="sample.request_id">
                                <button type="button" @click="openModal(sample)"
                                        class="w-full text-left p-3 rounded-xl bg-slate-900/80 border border-slate-800/80 hover:border-slate-700 transition">
                                    <p class="text-sm text-slate-200 line-clamp-2" x-text="sample.content"></p>
                                    <div class="flex flex-wrap gap-2 mt-2 text-[10px] text-slate-500">
                                        <span class="capitalize" x-text="sample.urgency + ' urgency'"></span>
                                        <span x-text="'Score ' + (sample.priority_score ?? 0).toFixed(1)"></span>
                                        <span class="capitalize" x-text="(sample.status || '').replace('_', ' ')"></span>
                                    </div>
                                </button>
                            </template>
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

                    <template x-if="selectedReq?.evaluation_thoughts">
                        <div>
                            <span class="text-xs uppercase text-slate-500 font-bold">Why this ranking (AI)</span>
                            <p class="text-amber-100/90 mt-1 bg-amber-500/5 p-3 rounded-xl border border-amber-500/20" x-text="selectedReq.evaluation_thoughts"></p>
                            <p class="text-[11px] text-slate-500 mt-1">AI recommendation — not an allocation decision until you confirm or change status.</p>
                        </div>
                    </template>

                    <template x-if="selectedReq?.priority_factors">
                        <div>
                            <span class="text-xs uppercase text-slate-500 font-bold">Priority score breakdown</span>
                            <p class="text-slate-300 mt-1 text-sm" x-text="selectedReq.priority_factors?.reason"></p>
                            <p class="text-[11px] text-slate-500 mt-1">
                                Score: <span x-text="(selectedReq.priority_score ?? 0).toFixed(1)"></span>
                                · Confidence excluded from ranking
                            </p>
                        </div>
                    </template>

                    <template x-if="selectedReq?.suggested_fix">
                        <div>
                            <span class="text-xs uppercase text-slate-500 font-bold">Suggested Fix</span>
                            <p class="text-emerald-100/90 mt-1 bg-emerald-500/5 p-3 rounded-xl border border-emerald-500/20" x-text="selectedReq.suggested_fix"></p>
                        </div>
                    </template>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="text-xs uppercase text-slate-500 font-bold">Category</span>
                            <p class="text-slate-200 mt-0.5" x-text="selectedReq?.category || 'General'"></p>
                        </div>
                        <div>
                            <span class="text-xs uppercase text-slate-500 font-bold">Urgency</span>
                            <p class="text-slate-200 mt-0.5 capitalize">
                                <span x-text="selectedReq?.urgency"></span>
                                <template x-if="selectedReq?.urgency_score != null">
                                    <span class="text-slate-500" x-text="' (' + selectedReq.urgency_score + '/10)'"></span>
                                </template>
                            </p>
                        </div>
                        <div>
                            <span class="text-xs uppercase text-slate-500 font-bold">Status</span>
                            <p class="text-slate-200 mt-0.5 capitalize" x-text="(selectedReq?.status || 'pending').replace('_', ' ')"></p>
                        </div>
                        <div>
                            <span class="text-xs uppercase text-slate-500 font-bold">Citizen Verification</span>
                            <p class="text-slate-200 mt-0.5 capitalize" x-text="selectedReq?.verification_status || 'not requested'"></p>
                        </div>
                        <div>
                            <span class="text-xs uppercase text-slate-500 font-bold">AI Confidence</span>
                            <p class="text-slate-200 mt-0.5" x-text="selectedReq?.confidence != null ? Math.round(selectedReq.confidence * 100) + '%' : 'N/A'"></p>
                        </div>
                        <div>
                            <span class="text-xs uppercase text-slate-500 font-bold">Channel</span>
                            <p class="text-slate-200 mt-0.5 capitalize" x-text="selectedReq?.source_channel || 'N/A'"></p>
                        </div>
                        <div>
                            <span class="text-xs uppercase text-slate-500 font-bold">Detected Language</span>
                            <p class="text-slate-200 mt-0.5" x-text="selectedReq?.detected_language || 'N/A'"></p>
                        </div>
                        <div class="col-span-2" x-show="selectedReq?.equity_flag">
                            <span class="text-xs uppercase text-slate-500 font-bold">Equity / Bias Check</span>
                            <p class="text-rose-200 mt-0.5 text-xs" x-text="(selectedReq?.equity_reasons || []).join(' ')"></p>
                            <p class="text-[11px] text-slate-500 mt-1" x-show="selectedReq?.equity_boost">
                                Fairness boost: +<span x-text="selectedReq.equity_boost"></span> (confidence not used in priority score)
                            </p>
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

                    <template x-if="selectedReq?.cluster_summary">
                        <div>
                            <span class="text-xs uppercase text-slate-500 font-bold">Issue Cluster</span>
                            <p class="text-sky-300 mt-0.5" x-text="selectedReq.cluster_summary"></p>
                        </div>
                    </template>

                    <template x-if="selectedReq?.verification_note">
                        <div>
                            <span class="text-xs uppercase text-slate-500 font-bold">Citizen Verification Reply</span>
                            <p class="text-sky-200 mt-0.5" x-text="selectedReq.verification_note"></p>
                        </div>
                    </template>

                    <template x-if="selectedReq?.verification_file_path">
                        <div>
                            <span class="text-xs uppercase text-slate-500 font-bold">Verification Photo</span>
                            <div class="mt-2">
                                <a :href="'/' + selectedReq.verification_file_path" target="_blank" class="inline-flex items-center space-x-2 bg-sky-500/10 hover:bg-sky-500/20 text-sky-300 border border-sky-500/20 px-4 py-2 rounded-xl text-xs transition">
                                    <span>View confirmation media</span>
                                </a>
                            </div>
                        </div>
                    </template>

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

                <div class="flex flex-wrap justify-end gap-2 pt-3 border-t border-slate-800">
                    <template x-if="selectedReq?.status === 'pending_review'">
                        <button @click="updateStatus(selectedReq.request_id || selectedReq.id, 'pending')" class="px-3 py-2 text-xs bg-amber-500/10 text-amber-300 border border-amber-500/20 rounded-xl font-semibold">Confirm</button>
                    </template>
                    <template x-if="selectedReq && selectedReq.status !== 'resolved' && selectedReq.status !== 'in_progress'">
                        <button @click="updateStatus(selectedReq.request_id || selectedReq.id, 'in_progress')" class="px-3 py-2 text-xs bg-sky-500/10 text-sky-300 border border-sky-500/20 rounded-xl font-semibold">Start work</button>
                    </template>
                    <template x-if="selectedReq && selectedReq.status !== 'resolved'">
                        <button @click="updateStatus(selectedReq.request_id || selectedReq.id, 'resolved')" class="px-3 py-2 text-xs bg-emerald-500/10 text-emerald-300 border border-emerald-500/20 rounded-xl font-semibold">Mark resolved</button>
                    </template>
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
                sectors: [],
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
                            this.sectors = data.sectors || [];
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

                async updateStatus(id, status) {
                    try {
                        const response = await fetch(`/api/mp/requests/${id}/status`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ status }),
                        });
                        const data = await response.json();
                        if (data.status === 'success') {
                            this.selectedReq = null;
                            await this.fetchDashboard();
                        }
                    } catch (err) {
                        console.error('Failed to update request status:', err);
                    }
                }
            }
        }
    </script>
</x-layout>
