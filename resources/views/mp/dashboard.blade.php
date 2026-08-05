<x-layout title="MP Dashboard">
    <div x-data="dashboardPage()" x-init="initDashboard()" class="space-y-6">
        
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-stone-400/40 pb-4">
            <div>
                <h1 class="text-2xl font-bold text-stone-900 font-typewriter tracking-wide uppercase" >Constituency Requests Dashboard</h1>
                <p class="text-xs text-stone-700 font-typewriter">Real-time issues submitted by citizens in your constituency.</p>
            </div>
            
            <button @click="fetchDashboard()" class="inline-flex items-center space-x-2 bg-[#e6dfd1] border border-stone-400/60 hover:bg-[#dcd5c1] text-stone-900 px-4 py-2 rounded font-typewriter text-xs transition shadow-sm">
                <svg class="w-4 h-4" x-bind:class="loading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                <span>Refresh Data</span>
            </button>
        </div>

        <!-- Metrics Overview Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-5">
            <div class="torn-card p-5 rounded-none flex items-center justify-between border border-stone-300">
                <div>
                    <span class="text-[10px] font-semibold uppercase tracking-wider text-stone-700 font-typewriter">Open Requests</span>
                    <p class="text-2xl font-black text-stone-900 font-typewriter mt-1" x-text="metrics.total_requests || 0"></p>
                </div>
                <div class="p-2.5 bg-emerald-500/10 text-emerald-800 rounded border border-emerald-500/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                </div>
            </div>

            <div class="torn-card p-5 rounded-none flex items-center justify-between border border-stone-300">
                <div>
                    <span class="text-[10px] font-semibold uppercase tracking-wider text-stone-700 font-typewriter">Needs Review</span>
                    <p class="text-2xl font-black text-amber-800 font-typewriter mt-1" x-text="metrics.needs_review_count || 0"></p>
                </div>
                <div class="p-2.5 bg-amber-500/10 text-amber-800 rounded border border-amber-500/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
            </div>

            <div class="torn-card p-5 rounded-none flex items-center justify-between border border-stone-300">
                <div>
                    <span class="text-[10px] font-semibold uppercase tracking-wider text-stone-700 font-typewriter">Awaiting Citizen Confirm</span>
                    <p class="text-2xl font-black text-sky-800 font-typewriter mt-1" x-text="metrics.awaiting_verification_count || 0"></p>
                </div>
                <div class="p-2.5 bg-sky-500/10 text-sky-800 rounded border border-sky-500/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
                </div>
            </div>

            <div class="torn-card p-5 rounded-none flex items-center justify-between border border-stone-300">
                <div>
                    <span class="text-[10px] font-semibold uppercase tracking-wider text-stone-700 font-typewriter">Equity Flagged</span>
                    <p class="text-2xl font-black text-rose-800 font-typewriter mt-1" x-text="metrics.equity_flagged_count || 0"></p>
                </div>
                <div class="p-2.5 bg-rose-500/10 text-rose-800 rounded border border-rose-500/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"></path></svg>
                </div>
            </div>

            <div class="torn-card p-5 rounded-none flex items-center justify-between border border-stone-300">
                <div>
                    <span class="text-[10px] font-semibold uppercase tracking-wider text-stone-700 font-typewriter">High Urgency</span>
                    <p class="text-2xl font-black text-red-700 font-typewriter mt-1" x-text="metrics.high_urgency_requests || 0"></p>
                </div>
                <div class="p-2.5 bg-red-500/10 text-red-700 rounded border border-red-500/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
            </div>

            <div class="torn-card p-5 rounded-none flex items-center justify-between border border-stone-300">
                <div>
                    <span class="text-[10px] font-semibold uppercase tracking-wider text-stone-700 font-typewriter">Assigned Constituency</span>
                    <p class="text-base font-bold text-stone-900 font-typewriter mt-1 truncate max-w-[120px]" x-text="mpInfo.constituency || 'N/A'"></p>
                </div>
                <div class="p-2.5 bg-stone-500/10 text-stone-800 rounded border border-stone-500/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                </div>
            </div>
        </div>

        <!-- Sector overview -->
        <div class="torn-card p-6 space-y-4 border border-stone-300">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-stone-300 pb-3">
                <div>
                    <h2 class="text-lg font-bold text-stone-900 font-typewriter tracking-wide uppercase">Issues by Sector</h2>
                    <p class="text-xs text-stone-700 font-typewriter mt-0.5">Grouped citizen complaints (roads, water, drainage, fire, etc.). Open a sector to browse every request.</p>
                </div>
                <a href="/mp/requests" class="inline-flex items-center justify-center px-4 py-2 rounded text-xs font-typewriter font-semibold bg-[#e6dfd1] text-stone-900 border border-stone-400/60 hover:bg-[#dcd5c1] transition shadow-sm">
                    Browse all requests
                </a>
            </div>

            <div x-show="loading" class="grid grid-cols-1 md:grid-cols-2 gap-4 py-2">
                <div class="h-36 bg-stone-200 animate-pulse rounded"></div>
                <div class="h-36 bg-stone-200 animate-pulse rounded"></div>
            </div>

            <div x-show="!loading && sectors.length === 0" class="text-center py-12 border border-dashed border-stone-400 rounded">
                <p class="text-stone-700 font-typewriter text-xs">No active issues submitted for your constituency yet.</p>
                <a href="/mp/requests" class="inline-block mt-3 text-xs font-typewriter text-stone-900 hover:underline">Search historical requests →</a>
            </div>

            <div x-show="!loading && sectors.length > 0" class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <template x-for="sector in sectors" :key="sector.sector">
                    <div class="bg-[#fcfbfa] border border-stone-300 p-5 space-y-4 shadow-sm relative">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-base font-bold text-stone-900 font-typewriter uppercase" x-text="sector.sector"></h3>
                                <p class="text-xs text-stone-700 font-typewriter mt-1">
                                    <span x-text="sector.count"></span> open request<span x-text="sector.count === 1 ? '' : 's'"></span>
                                    · avg score <span x-text="(sector.avg_priority ?? 0).toFixed(1)"></span>
                                </p>
                            </div>
                            <a :href="'/mp/requests?sector=' + encodeURIComponent(sector.sector)"
                               class="shrink-0 px-3 py-1.5 text-xs font-typewriter rounded bg-[#e6dfd1] text-stone-900 border border-stone-400/60 hover:bg-[#dcd5c1] shadow-sm">
                                View all
                            </a>
                        </div>

                        <div class="flex flex-wrap gap-2 text-[11px] font-typewriter">
                            <span class="px-2 py-0.5 rounded bg-red-100 text-red-800 border border-red-300"
                                  x-show="sector.high_urgency > 0"
                                  x-text="sector.high_urgency + ' high urgency'"></span>
                            <span class="px-2 py-0.5 rounded bg-amber-100 text-amber-800 border border-amber-300"
                                  x-show="sector.needs_review > 0"
                                  x-text="sector.needs_review + ' need review'"></span>
                            <span class="px-2 py-0.5 rounded bg-stone-200 text-stone-700"
                                  x-show="sector.latest_at"
                                  x-text="'Latest ' + formatDate(sector.latest_at)"></span>
                        </div>

                        <div class="space-y-2 border-t border-dashed border-stone-300 pt-3">
                            <p class="text-[10px] uppercase tracking-wider text-stone-600 font-typewriter">Top in this sector</p>
                            <template x-for="sample in (sector.samples || [])" :key="sample.request_id">
                                <button type="button" @click="openModal(sample)"
                                        class="w-full text-left p-3 rounded bg-white border border-stone-300 hover:border-stone-500 transition shadow-xs">
                                    <p class="text-xs font-ledger text-stone-900 line-clamp-2" x-text="sample.content"></p>
                                    <div class="flex flex-wrap gap-2 mt-2 text-[10px] text-stone-600 font-typewriter">
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
        <div x-show="selectedReq" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-stone-900/60 backdrop-blur-xs" x-cloak>
            <div @click.away="selectedReq = null" class="bg-[#f2eee3] border border-stone-400 rounded-lg w-full max-w-2xl p-6 space-y-5 shadow-2xl overflow-y-auto max-h-[90vh]">
                
                <div class="flex items-center justify-between border-b border-stone-400 pb-3">
                    <h3 class="text-base font-bold text-stone-900 font-typewriter uppercase">Request #<span x-text="selectedReq?.request_id || selectedReq?.id"></span></h3>
                    <button @click="selectedReq = null" class="text-stone-700 hover:text-stone-950 font-bold text-lg">&times;</button>
                </div>

                <div class="space-y-4 text-xs font-ledger text-stone-900">
                    <div>
                        <span class="text-[10px] uppercase text-stone-700 font-bold font-typewriter block">AI Translated Summary</span>
                        <p class="text-stone-900 font-medium mt-1 bg-white p-3 rounded border border-stone-300" x-text="selectedReq?.content"></p>
                    </div>

                    <div>
                        <span class="text-[10px] uppercase text-stone-700 font-bold font-typewriter block">Original Message</span>
                        <p class="text-stone-700 italic mt-1 bg-white p-3 rounded border border-stone-300 font-typewriter" x-text="selectedReq?.raw_message"></p>
                    </div>

                    <template x-if="selectedReq?.evaluation_thoughts">
                        <div>
                            <span class="text-[10px] uppercase text-stone-700 font-bold font-typewriter block">Why this ranking (AI)</span>
                            <p class="text-stone-900 mt-1 bg-amber-50 p-3 rounded border border-amber-300" x-text="selectedReq.evaluation_thoughts"></p>
                            <p class="text-[10px] text-stone-600 font-typewriter mt-1">AI recommendation — not an allocation decision until you confirm or change status.</p>
                        </div>
                    </template>

                    <template x-if="selectedReq?.priority_factors">
                        <div>
                            <span class="text-[10px] uppercase text-stone-700 font-bold font-typewriter block">Priority score breakdown</span>
                            <p class="text-stone-900 mt-1 text-xs" x-text="selectedReq.priority_factors?.reason"></p>
                            <p class="text-[10px] text-stone-600 font-typewriter mt-1">
                                Score: <span x-text="(selectedReq.priority_score ?? 0).toFixed(1)"></span>
                                · Confidence excluded from ranking
                            </p>
                        </div>
                    </template>

                    <template x-if="selectedReq?.suggested_fix">
                        <div>
                            <span class="text-[10px] uppercase text-stone-700 font-bold font-typewriter block">Suggested Fix</span>
                            <p class="text-stone-900 mt-1 bg-emerald-50 p-3 rounded border border-emerald-300" x-text="selectedReq.suggested_fix"></p>
                        </div>
                    </template>

                    <div class="grid grid-cols-2 gap-4 font-typewriter text-xs">
                        <div>
                            <span class="text-[10px] uppercase text-stone-700 font-bold block">Category</span>
                            <p class="text-stone-900 mt-0.5" x-text="selectedReq?.category || 'General'"></p>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase text-stone-700 font-bold block">Urgency</span>
                            <p class="text-stone-900 mt-0.5 capitalize">
                                <span x-text="selectedReq?.urgency"></span>
                                <template x-if="selectedReq?.urgency_score != null">
                                    <span class="text-stone-600" x-text="' (' + selectedReq.urgency_score + '/10)'"></span>
                                </template>
                            </p>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase text-stone-700 font-bold block">Status</span>
                            <p class="text-stone-900 mt-0.5 capitalize" x-text="(selectedReq?.status || 'pending').replace('_', ' ')"></p>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase text-stone-700 font-bold block">Citizen Verification</span>
                            <p class="text-stone-900 mt-0.5 capitalize" x-text="selectedReq?.verification_status || 'not requested'"></p>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase text-stone-700 font-bold block">AI Confidence</span>
                            <p class="text-stone-900 mt-0.5" x-text="selectedReq?.confidence != null ? Math.round(selectedReq.confidence * 100) + '%' : 'N/A'"></p>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase text-stone-700 font-bold block">Channel</span>
                            <p class="text-stone-900 mt-0.5 capitalize" x-text="selectedReq?.source_channel || 'N/A'"></p>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase text-stone-700 font-bold block">Detected Language</span>
                            <p class="text-stone-900 mt-0.5" x-text="selectedReq?.detected_language || 'N/A'"></p>
                        </div>
                        <div class="col-span-2" x-show="selectedReq?.equity_flag">
                            <span class="text-[10px] uppercase text-stone-700 font-bold block">Equity / Bias Check</span>
                            <p class="text-rose-900 mt-0.5 text-xs" x-text="(selectedReq?.equity_reasons || []).join(' ')"></p>
                            <p class="text-[10px] text-stone-600 mt-1" x-show="selectedReq?.equity_boost">
                                Fairness boost: +<span x-text="selectedReq.equity_boost"></span> (confidence not used in priority score)
                            </p>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase text-stone-700 font-bold block">Phone Number</span>
                            <p class="text-stone-900 mt-0.5" x-text="selectedReq?.user?.phone_number || 'N/A'"></p>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase text-stone-700 font-bold block">Submitted Date</span>
                            <p class="text-stone-900 mt-0.5" x-text="formatDate(selectedReq?.created_at)"></p>
                        </div>
                    </div>

                    <template x-if="selectedReq?.cluster_summary">
                        <div>
                            <span class="text-[10px] uppercase text-stone-700 font-bold font-typewriter block">Issue Cluster</span>
                            <p class="text-stone-900 mt-0.5" x-text="selectedReq.cluster_summary"></p>
                        </div>
                    </template>

                    <template x-if="selectedReq?.verification_note">
                        <div>
                            <span class="text-[10px] uppercase text-stone-700 font-bold font-typewriter block">Citizen Verification Reply</span>
                            <p class="text-stone-900 mt-0.5" x-text="selectedReq.verification_note"></p>
                        </div>
                    </template>

                    <template x-if="selectedReq?.verification_file_path">
                        <div>
                            <span class="text-[10px] uppercase text-stone-700 font-bold font-typewriter block">Verification Photo</span>
                            <div class="mt-2">
                                <a :href="'/' + selectedReq.verification_file_path" target="_blank" class="inline-flex items-center space-x-2 bg-white hover:bg-stone-100 text-stone-900 border border-stone-300 px-4 py-2 rounded text-xs transition font-typewriter shadow-xs">
                                    <span>View confirmation media</span>
                                </a>
                            </div>
                        </div>
                    </template>

                    <template x-if="selectedReq?.upload_file_path">
                        <div>
                            <span class="text-[10px] uppercase text-stone-700 font-bold font-typewriter block">Attachment</span>
                            <div class="mt-2">
                                <a :href="'/' + selectedReq.upload_file_path" target="_blank" class="inline-flex items-center space-x-2 bg-white hover:bg-stone-100 text-stone-900 border border-stone-300 px-4 py-2 rounded text-xs transition font-typewriter shadow-xs">
                                    <span>View Attached File</span>
                                </a>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="flex flex-wrap justify-end gap-2 pt-3 border-t border-stone-400 font-typewriter">
                    <template x-if="selectedReq?.status === 'pending_review'">
                        <button @click="updateStatus(selectedReq.request_id || selectedReq.id, 'pending')" class="px-3 py-2 text-xs bg-amber-100 text-amber-900 border border-amber-300 rounded font-semibold shadow-xs">Confirm</button>
                    </template>
                    <template x-if="selectedReq && selectedReq.status !== 'resolved' && selectedReq.status !== 'in_progress'">
                        <button @click="updateStatus(selectedReq.request_id || selectedReq.id, 'in_progress')" class="px-3 py-2 text-xs bg-sky-100 text-sky-900 border border-sky-300 rounded font-semibold shadow-xs">Start work</button>
                    </template>
                    <template x-if="selectedReq && selectedReq.status !== 'resolved'">
                        <button @click="updateStatus(selectedReq.request_id || selectedReq.id, 'resolved')" class="px-3 py-2 text-xs bg-emerald-100 text-emerald-900 border border-emerald-300 rounded font-semibold shadow-xs">Mark resolved</button>
                    </template>
                    <button @click="selectedReq = null" class="px-4 py-2 bg-stone-300 text-stone-900 rounded text-xs font-semibold hover:bg-stone-400 border border-stone-400 shadow-xs">Close</button>
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