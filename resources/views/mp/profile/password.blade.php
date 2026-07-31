<x-layout title="Change Password">
    <div x-data="passwordPage()" class="max-w-xl space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-100">Change Password</h1>
            <p class="text-sm text-slate-400">Ensure your account uses a strong, secure password.</p>
        </div>

        <div x-show="successMessage" x-text="successMessage" class="p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm rounded-xl" style="display:none;"></div>
        <div x-show="errorMessage" x-text="errorMessage" class="p-4 bg-red-500/10 border border-red-500/20 text-red-400 text-sm rounded-xl" style="display:none;"></div>

        <form @submit.prevent="changePassword()" class="bg-slate-900 border border-slate-800 p-6 rounded-2xl shadow-xl space-y-5">
            <div>
                <label class="block text-xs uppercase tracking-wider text-slate-400 font-semibold mb-2">Current Password</label>
                <input type="password" x-model="form.current_password" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:border-emerald-500 transition">
            </div>

            <div>
                <label class="block text-xs uppercase tracking-wider text-slate-400 font-semibold mb-2">New Password</label>
                <input type="password" x-model="form.new_password" required minlength="8" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:border-emerald-500 transition">
            </div>

            <div>
                <label class="block text-xs uppercase tracking-wider text-slate-400 font-semibold mb-2">Confirm New Password</label>
                <input type="password" x-model="form.new_password_confirmation" required minlength="8" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:border-emerald-500 transition">
            </div>

            <div class="flex items-center space-x-3 pt-2">
                <button type="submit" :disabled="submitting" class="bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-bold px-5 py-3 rounded-xl transition">
                    <span x-show="!submitting">Update Password</span>
                    <span x-show="submitting" style="display:none;">Updating...</span>
                </button>
                <a href="/mp/profile/show" class="text-slate-400 hover:text-slate-200 text-sm px-4 py-3">Back to Profile</a>
            </div>
        </form>
    </div>

    <script>
        function passwordPage() {
            return {
                submitting: false,
                successMessage: '',
                errorMessage: '',
                form: {
                    current_password: '',
                    new_password: '',
                    new_password_confirmation: ''
                },

                async changePassword() {
                    this.submitting = true;
                    this.successMessage = '';
                    this.errorMessage = '';

                    try {
                        const response = await fetch('/api/mp/profile/change-password', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(this.form)
                        });

                        const data = await response.json();

                        if (response.ok && data.status === 'success') {
                            this.successMessage = 'Password updated successfully!';
                            this.form.current_password = '';
                            this.form.new_password = '';
                            this.form.new_password_confirmation = '';
                        } else {
                            this.errorMessage = data.message || 'Failed to update password.';
                        }
                    } catch (err) {
                        this.errorMessage = 'Network error. Please try again.';
                    } finally {
                        this.submitting = false;
                    }
                }
            }
        }
    </script>
</x-layout>