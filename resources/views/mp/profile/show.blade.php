<x-layout title="MP Profile Overview">
    <div x-data="profilePage()" x-init="fetchProfile()" class="max-w-3xl space-y-6">
        
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-stone-400/40 pb-4">
            <div>
                <h1 class="text-2xl font-bold text-stone-900 font-typewriter tracking-wide uppercase" >MP Profile Details</h1>
                <p class="text-xs text-stone-700 font-typewriter mt-1" >View and manage your registered MP details.</p>
            </div>
            
            <a href="/mp/profile/edit" class="bg-emerald-100 hover:bg-emerald-200 text-emerald-900 border border-emerald-300 font-typewriter text-xs font-semibold px-4 py-2 rounded transition shadow-xs">
                Edit Profile
            </a>
        </div>

        <!-- Notification Banner -->
        <div x-show="message" x-text="message" class="p-4 bg-emerald-100 border border-emerald-300 text-emerald-900 text-xs rounded font-typewriter shadow-xs" style="display:none;"></div>

        <!-- Loading Shell -->
        <div x-show="loading" class="torn-card p-6 space-y-4 animate-pulse border border-stone-300">
            <div class="h-6 w-1/3 bg-stone-200 rounded"></div>
            <div class="h-6 w-1/2 bg-stone-200 rounded"></div>
            <div class="h-6 w-1/4 bg-stone-200 rounded"></div>
        </div>

        <!-- Profile Card -->
        <div x-show="!loading" class="torn-card p-6 space-y-6 border border-stone-300 shadow-sm font-typewriter">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="text-[10px] uppercase tracking-wider font-bold text-stone-600">Full Name</label>
                    <p class="text-base font-semibold text-stone-900 font-ledger mt-1" x-text="mp.mp_name || mp.name || 'N/A'"></p>
                </div>

                <div>
                    <label class="text-[10px] uppercase tracking-wider font-bold text-stone-600">Email Address</label>
                    <p class="text-base font-semibold text-stone-900 font-ledger mt-1" x-text="mp.email || 'N/A'"></p>
                </div>

                <div>
                    <label class="text-[10px] uppercase tracking-wider font-bold text-stone-600">Constituency</label>
                    <p class="text-base font-bold text-emerald-900 font-ledger mt-1" x-text="mp.constituency_name || mp.constituency || 'N/A'"></p>
                </div>

                <div>
                    <label class="text-[10px] uppercase tracking-wider font-bold text-stone-600">Account ID</label>
                    <p class="text-base font-mono text-stone-800 font-ledger mt-1" x-text="'#' + (mp.mp_id || mp.id || 'N/A')"></p>
                </div>
            </div>

            <hr class="border-stone-300">

            <div class="flex items-center justify-between pt-2">
                <span class="text-xs text-stone-700">Security Options</span>
                <a href="/mp/profile/password" class="text-xs text-emerald-900 font-semibold hover:underline">Change Password &rarr;</a>
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