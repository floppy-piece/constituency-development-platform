<x-layout title="Change Password">
    <div x-data="passwordPage()" class="max-w-xl space-y-6">
        <div class="border-b border-stone-400/40 pb-4">
            <h1 class="text-2xl font-bold text-stone-900 font-typewriter tracking-wide uppercase" >Change Password</h1>
            <p class="text-xs text-stone-700 font-typewriter mt-1" >Ensure your account uses a strong, secure password.</p>
        </div>

        <div x-show="successMessage" x-text="successMessage" class="p-4 bg-emerald-100 border border-emerald-300 text-emerald-900 text-xs rounded font-typewriter shadow-xs" style="display:none;"></div>
        <div x-show="errorMessage" x-text="errorMessage" class="p-4 bg-red-100 border border-red-300 text-red-900 text-xs rounded font-typewriter shadow-xs" style="display:none;"></div>

        <form @submit.prevent="changePassword()" class="torn-card p-6 space-y-5 border border-stone-300 font-typewriter">
            <div>
                <label class="block text-[10px] uppercase tracking-wider text-stone-700 font-bold mb-2">Current Password</label>
                <input type="password" x-model="form.current_password" required class="w-full bg-white border border-stone-300 rounded px-3 py-2 text-xs text-stone-900 shadow-xs focus:outline-none focus:border-stone-500 font-ledger">
            </div>

            <div>
                <label class="block text-[10px] uppercase tracking-wider text-stone-700 font-bold mb-2">New Password</label>
                <input type="password" x-model="form.new_password" required minlength="8" class="w-full bg-white border border-stone-300 rounded px-3 py-2 text-xs text-stone-900 shadow-xs focus:outline-none focus:border-stone-500 font-ledger">
            </div>

            <div>
                <label class="block text-[10px] uppercase tracking-wider text-stone-700 font-bold mb-2">Confirm New Password</label>
                <input type="password" x-model="form.new_password_confirmation" required minlength="8" class="w-full bg-white border border-stone-300 rounded px-3 py-2 text-xs text-stone-900 shadow-xs focus:outline-none focus:border-stone-500 font-ledger">
            </div>

            <div class="flex items-center space-x-3 pt-2 border-t border-stone-300">
                <button type="submit" :disabled="submitting" class="bg-emerald-100 hover:bg-emerald-200 disabled:opacity-40 text-emerald-900 border border-emerald-300 font-semibold px-4 py-2 rounded text-xs transition shadow-xs">
                    <span x-show="!submitting">Update Password</span>
                    <span x-show="submitting" style="display:none;">Updating...</span>
                </button>
                <a href="/mp/profile/show" class="text-stone-700 hover:text-stone-900 text-xs px-3 py-2 underline">Back to Profile</a>
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