<x-layout title="MP Profile Overview">
    <div x-data="profilePage()" x-init="fetchProfile()" class="max-w-3xl space-y-6">
        
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-100">MP Profile Details</h1>
                <p class="text-sm text-slate-400">View and manage your registered MP details.</p>
            </div>
            
            <a href="/mp/profile/edit" class="bg-emerald-500 hover:bg-emerald-600 text-slate-950 text-sm font-bold px-4 py-2 rounded-xl transition">
                Edit Profile
            </a>
        </div>

        <!-- Notification Banner -->
        <div x-show="message" x-text="message" class="p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm rounded-xl" style="display:none;"></div>

        <!-- Loading Shell -->
        <div x-show="loading" class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-4 animate-pulse">
            <div class="h-6 w-1/3 bg-slate-800 rounded"></div>
            <div class="h-6 w-1/2 bg-slate-800 rounded"></div>
            <div class="h-6 w-1/4 bg-slate-800 rounded"></div>
        </div>

        <!-- Profile Card -->
        <div x-show="!loading" class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="text-xs uppercase tracking-wider font-semibold text-slate-400">Full Name</label>
                    <p class="text-lg font-semibold text-slate-100 mt-1" x-text="mp.mp_name || mp.name || 'N/A'"></p>
                </div>

                <div>
                    <label class="text-xs uppercase tracking-wider font-semibold text-slate-400">Email Address</label>
                    <p class="text-lg font-semibold text-slate-100 mt-1" x-text="mp.email || 'N/A'"></p>
                </div>

                <div>
                    <label class="text-xs uppercase tracking-wider font-semibold text-slate-400">Constituency</label>
                    <p class="text-lg font-bold text-emerald-400 mt-1" x-text="mp.constituency_name || mp.constituency || 'N/A'"></p>
                </div>

                <div>
                    <label class="text-xs uppercase tracking-wider font-semibold text-slate-400">Account ID</label>
                    <p class="text-lg font-mono text-slate-300 mt-1" x-text="'#' + (mp.mp_id || mp.id || 'N/A')"></p>
                </div>
            </div>

            <hr class="border-slate-800">

            <div class="flex items-center justify-between pt-2">
                <span class="text-sm text-slate-400">Security Options</span>
                <a href="/mp/profile/password" class="text-sm text-emerald-400 hover:underline">Change Password &rarr;</a>
            </div>
        </div>
    </div>

    <script>
        function profilePage() {
            return {
                loading: true,
                mp: {},
                message: '',

                async fetchProfile() {
                    try {
                        const response = await fetch('/api/mp/profile');
                        const data = await response.json();

                        if (data.status === 'success') {
                            this.mp = data.mp_info || {};
                        }
                    } catch (err) {
                        console.error('Failed to load profile:', err);
                    } finally {
                        this.loading = false;
                    }
                }
            }
        }
    </script>
</x-layout>