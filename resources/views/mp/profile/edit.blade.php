<x-layout title="Edit MP Profile">
    <div x-data="editProfilePage()" x-init="fetchProfile()" class="max-w-xl space-y-6">
        <div class="border-b border-stone-400/40 pb-4">
            <h1 class="text-2xl font-bold text-stone-900 font-typewriter tracking-wide uppercase" style="color:white">Update Profile</h1>
            <p class="text-xs text-stone-700 font-typewriter mt-1" style="color:white">Modify your account information.</p>
        </div>

        <!-- Success & Error Alert Banners -->
        <div x-show="successMessage" x-text="successMessage" class="p-4 bg-emerald-100 border border-emerald-300 text-emerald-900 text-xs rounded font-typewriter shadow-xs" style="display:none;"></div>
        <div x-show="errorMessage" x-text="errorMessage" class="p-4 bg-red-100 border border-red-300 text-red-900 text-xs rounded font-typewriter shadow-xs" style="display:none;"></div>

        <form @submit.prevent="updateProfile()" class="torn-card p-6 space-y-5 border border-stone-300 font-typewriter">
            <div>
                <label class="block text-[10px] uppercase tracking-wider text-stone-700 font-bold mb-2">MP Name</label>
                <input type="text" x-model="form.mp_name" required class="w-full bg-white border border-stone-300 rounded px-3 py-2 text-xs text-stone-900 shadow-xs focus:outline-none focus:border-stone-500 font-ledger">
            </div>

            <div>
                <label class="block text-[10px] uppercase tracking-wider text-stone-700 font-bold mb-2">Email Address</label>
                <input type="email" x-model="form.email" required class="w-full bg-white border border-stone-300 rounded px-3 py-2 text-xs text-stone-900 shadow-xs focus:outline-none focus:border-stone-500 font-ledger">
            </div>

            <div>
                <label class="block text-[10px] uppercase tracking-wider text-stone-700 font-bold mb-2">Constituency Name</label>
                <input type="text" x-model="form.constituency_name" class="w-full bg-white border border-stone-300 rounded px-3 py-2 text-xs text-stone-900 shadow-xs focus:outline-none focus:border-stone-500 font-ledger">
            </div>

            <div class="flex items-center space-x-3 pt-2 border-t border-stone-300">
                <button type="submit" :disabled="saving" class="bg-emerald-100 hover:bg-emerald-200 disabled:opacity-40 text-emerald-900 border border-emerald-300 font-semibold px-4 py-2 rounded text-xs transition shadow-xs">
                    <span x-show="!saving">Save Changes</span>
                    <span x-show="saving" style="display:none;">Saving...</span>
                </button>
                <a href="/mp/profile/show" class="text-stone-700 hover:text-stone-900 text-xs px-3 py-2 underline">Cancel</a>
            </div>
        </form>
    </div>

    <script>
        function editProfilePage() {
            return {
                saving: false,
                successMessage: '',
                errorMessage: '',
                form: {
                    mp_name: '',
                    email: '',
                    constituency_name: ''
                },

                async fetchProfile() {
                    try {
                        const response = await fetch('/api/mp/profile');
                        const data = await response.json();
                        if (data.status === 'success' && data.mp_info) {
                            this.form.mp_name = data.mp_info.mp_name || data.mp_info.name || '';
                            this.form.email = data.mp_info.email || '';
                            this.form.constituency_name = data.mp_info.constituency_name || data.mp_info.constituency || '';
                        }
                    } catch (err) {
                        console.error('Failed to load profile:', err);
                    }
                },

                async updateProfile() {
                    this.saving = true;
                    this.successMessage = '';
                    this.errorMessage = '';

                    try {
                        const response = await fetch('/api/mp/profile/update', {
                            method: 'PUT',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(this.form)
                        });

                        const data = await response.json();

                        if (response.ok && data.status === 'success') {
                            this.successMessage = 'Profile updated successfully!';
                        } else {
                            this.errorMessage = data.message || 'Failed to update profile.';
                        }
                    } catch (err) {
                        this.errorMessage = 'Network error. Please try again.';
                    } finally {
                        this.saving = false;
                    }
                }
            }
        }
    </script>
</x-layout>