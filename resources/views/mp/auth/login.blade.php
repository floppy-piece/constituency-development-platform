<x-layout title="MP Login">
    <div class="min-h-screen flex items-center justify-center bg-slate-950 p-4" x-data="loginForm()">
        <div class="w-full max-w-md bg-slate-900 border border-slate-800 p-8 rounded-2xl shadow-2xl">
            <h2 class="text-2xl font-bold text-slate-100 text-center mb-6">MP Portal Login</h2>

            <!-- Error Banner -->
            <div x-show="errorMessage" x-text="errorMessage" class="mb-4 p-3 bg-red-500/10 border border-red-500/20 text-red-400 text-sm rounded-xl" style="display: none;"></div>

            <form @submit.prevent="submitLogin()" class="space-y-5">
                <div>
                    <label class="block text-xs uppercase tracking-wider text-slate-400 mb-2 font-semibold">Email Address</label>
                    <input type="email" x-model="email" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:border-emerald-500 transition" placeholder="mp@constituency.gov">
                </div>

                <div>
                    <label class="block text-xs uppercase tracking-wider text-slate-400 mb-2 font-semibold">Password</label>
                    <input type="password" x-model="password" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:border-emerald-500 transition" placeholder="••••••••">
                </div>

                <button type="submit" :disabled="loading" class="w-full bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-bold py-3 rounded-xl transition flex justify-center items-center">
                    <span x-show="!loading">Sign In</span>
                    <span x-show="loading" style="display: none;">Authenticating...</span>
                </button>
            </form>
        </div>
    </div>

    <script>
        function loginForm() {
            return {
                email: '',
                password: '',
                loading: false,
                errorMessage: '',

                async submitLogin() {
                    this.loading = true;
                    this.errorMessage = '';

                    try {
                        const response = await fetch('/api/mp/auth/login', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                email: this.email,
                                password: this.password
                            })
                        });

                        const data = await response.json();
                        console.log('Login Response:', data);

                        if (response.ok && (data.access_token || data.token || data.jwt_token)) {
                            // Extract JWT token safely regardless of key name used in controller
                            const token = data.access_token || data.token || data.jwt_token;
                            
                            // Save token to localStorage
                            localStorage.setItem('jwt_token', token);
                            
                            // Redirect to dashboard
                            window.location.href = '/mp/dashboard';
                        } else {
                            this.errorMessage = data.message || data.error || 'Invalid email or password.';
                        }
                    } catch (err) {
                        console.error('Login Error:', err);
                        this.errorMessage = 'Network error. Please try again.';
                    } finally {
                        this.loading = false;
                    }
                }
            }
        }
    </script>
</x-layout>