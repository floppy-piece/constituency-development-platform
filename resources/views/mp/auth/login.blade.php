<x-layout title="MP Login">
    <div class="min-h-[80vh] flex items-center justify-center p-4" x-data="loginForm()">
        <div class="w-full max-w-md torn-card p-8 shadow-xl animate__animated animate__fadeIn">
            <div class="text-center mb-6 border-b border-stone-300 pb-4">
                <span class="font-typewriter text-[9px] uppercase tracking-widest text-stone-700 block mb-1">Official Authentication Record</span>
                <h2 class="text-xl font-bold text-stone-900 font-typewriter uppercase tracking-wide">§ MP Portal Login</h2>
            </div>

            <!-- Error Banner -->
            <div x-show="errorMessage" x-text="errorMessage" class="mb-4 p-3 bg-red-100 border border-red-300 text-red-800 text-xs rounded font-typewriter" style="display: none;"></div>

            <form @submit.prevent="submitLogin()" class="space-y-5 font-typewriter text-xs">
                <div>
                    <label class="block uppercase tracking-wider text-stone-700 mb-2 font-semibold">Email Address</label>
                    <input type="email" x-model="email" required class="w-full bg-[#fffaf0] border border-stone-400/60 rounded px-4 py-3 text-stone-900 focus:outline-none focus:ring-1 focus:ring-stone-600 transition shadow-inner" placeholder="mp@constituency.gov">
                </div>

                <div>
                    <label class="block uppercase tracking-wider text-stone-700 mb-2 font-semibold">Password</label>
                    <input type="password" x-model="password" required class="w-full bg-[#fffaf0] border border-stone-400/60 rounded px-4 py-3 text-stone-900 focus:outline-none focus:ring-1 focus:ring-stone-600 transition shadow-inner" placeholder="••••••••">
                </div>

                <button type="submit" :disabled="loading" class="w-full bg-[#524940] hover:bg-[#3d362f] text-stone-100 font-bold py-3 rounded transition flex justify-center items-center shadow-md disabled:opacity-50">
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