<x-layout title="Edit MP Profile">
    <div x-data="editProfilePage()" x-init="fetchProfile()" class="max-w-xl space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-100">Update Profile</h1>
            <p class="text-sm text-slate-400">Modify your account information.</p>
        </div>

        <!-- Success & Error Alert Banners -->
        <div x-show="successMessage" x-text="successMessage" class="p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm rounded-xl" style="display:none;"></div>
        <div x-show="errorMessage" x-text="errorMessage" class="p-4 bg-red-500/10 border border-red-500/20 text-red-400 text-sm rounded-xl" style="display:none;"></div>

        <form @submit.prevent="updateProfile()" class="bg-slate-900 border border-slate-800 p-6 rounded-2xl shadow-xl space-y-5">
            <div>
                <label class="block text-xs uppercase tracking-wider text-slate-400 font-semibold mb-2">MP Name</label>
                <input type="text" x-model="form.mp_name" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:border-emerald-500 transition">
            </div>

            <div>
                <label class="block text-xs uppercase tracking-wider text-slate-400 font-semibold mb-2">Email Address</label>
                <input type="email" x-model="form.email" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:border-emerald-500 transition">
            </div>

            <div>
                <label class="block text-xs uppercase tracking-wider text-slate-400 font-semibold mb-2">Constituency Name</label>
                <input type="text" x-model="form.constituency_name" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:border-emerald-500 transition">
            </div>

            <div class="flex items-center space-x-3 pt-2">
                <button type="submit" :disabled="saving" class="bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-bold px-5 py-3 rounded-xl transition">
                    <span x-show="!saving">Save Changes</span>
                    <span x-show="saving" style="display:none;">Saving...</span>
                </button>
                <a href="/mp/profile/show" class="text-slate-400 hover:text-slate-200 text-sm px-4 py-3">Cancel</a>
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