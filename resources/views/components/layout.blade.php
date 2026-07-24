@props(['title' => 'MP Portal'])
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Global JWT Interceptor -->
    <script>
        (function() {
            const JWT_KEY = 'jwt_token';
            const currentPath = window.location.pathname;
            const token = localStorage.getItem(JWT_KEY);

            // Safely check for valid token existence
            const isValidToken = token && token !== 'undefined' && token !== 'null';
            const isLoginPage = currentPath.includes('/login');

            if (!isValidToken && !isLoginPage) {
                window.location.href = '/mp/auth/login';
                return;
            }

            // Global Fetch Interceptor
            const originalFetch = window.fetch;
            window.fetch = async function (...args) {
                let [resource, config] = args;
                config = config || {};
                
                const headers = new Headers(config.headers || {});
                if (isValidToken) {
                    headers.set('Authorization', `Bearer ${token}`);
                }
                headers.set('Accept', 'application/json');

                config.headers = headers;

                const response = await originalFetch(resource, config);

                // If API returns 401 Unauthorized, token expired/invalid -> Redirect to login
                if (response.status === 401 && !window.location.pathname.includes('/login')) {
                    localStorage.removeItem(JWT_KEY);
                    window.location.href = '/mp/auth/login';
                }

                return response;
            };
        })();
    </script>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen">

    @php
        // Matches /mp/auth/login, /mp/login, or any login route
        $isLoginPage = request()->is('mp/auth/login') || request()->is('mp/login') || request()->is('*login*');
    @endphp

    <div class="min-h-screen {{ !$isLoginPage ? 'flex' : '' }}" 
         x-data="{ 
             sidebarOpen: true, 
             logout() { 
                 localStorage.removeItem('jwt_token'); 
                 window.location.href = '/mp/auth/login'; 
             } 
         }">
        
        @unless($isLoginPage)
            <!-- Sidebar Navigation (Hidden on Login) -->
            <aside 
                class="w-64 bg-slate-900 h-screen p-6 border-r border-slate-800 flex flex-col justify-between fixed left-0 top-0 z-20 transition-transform duration-300 ease-in-out"
                :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            >
                <div>
                    <!-- Header -->
                    <div class="flex items-center justify-between mb-8">
                        <h1 class="text-lg font-black text-emerald-400 tracking-wider uppercase">Constituency Portal</h1>
                        <!-- Close button inside sidebar -->
                        <button @click="sidebarOpen = false" class="text-slate-400 hover:text-slate-200 transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Navigation Links with SVGs -->
                    <nav class="space-y-2">
                        <!-- Dashboard -->
                        <a href="/mp/dashboard" 
                           class="flex items-center space-x-3 px-3.5 py-3 rounded-xl transition text-sm font-medium {{ request()->is('mp/dashboard') ? 'bg-emerald-500/10 text-emerald-400 font-bold border border-emerald-500/20' : 'text-slate-400 hover:bg-slate-800/80 hover:text-slate-200' }}">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                            </svg>
                            <span>Dashboard</span>
                        </a>

                        <!-- Hotspots (GIS Map) -->
                        <a href="/mp/hotspots" 
                           class="flex items-center space-x-3 px-3.5 py-3 rounded-xl transition text-sm font-medium {{ request()->is('mp/hotspots*') ? 'bg-emerald-500/10 text-emerald-400 font-bold border border-emerald-500/20' : 'text-slate-400 hover:bg-slate-800/80 hover:text-slate-200' }}">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <span>Demand Hotspots</span>
                        </a>

                        <!-- Feasibility Matrix -->
                        <a href="{{ route('mp.matrix') }}" 
                           class="flex items-center space-x-3 px-3.5 py-3 rounded-xl transition text-sm font-medium {{ request()->routeIs('mp.matrix') || request()->is('mp/matrix*') ? 'bg-emerald-500/10 text-emerald-400 font-bold border border-emerald-500/20' : 'text-slate-400 hover:bg-slate-800/80 hover:text-slate-200' }}">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 002-2h2a2 2 0 002 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 002-2h2a2 2 0 002 2v14a2 2 0 002 2h2a2 2 0 002-2z"></path>
                            </svg>
                            <span>Feasibility Matrix</span>
                        </a>

                        <!-- Analytics -->
                        <a href="/mp/analytics" 
                           class="flex items-center space-x-3 px-3.5 py-3 rounded-xl transition text-sm font-medium {{ request()->is('mp/analytics') ? 'bg-emerald-500/10 text-emerald-400 font-bold border border-emerald-500/20' : 'text-slate-400 hover:bg-slate-800/80 hover:text-slate-200' }}">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                            </svg>
                            <span>Analytics</span>
                        </a>

                        <!-- Profile -->
                        <a href="/mp/profile/show" 
                           class="flex items-center space-x-3 px-3.5 py-3 rounded-xl transition text-sm font-medium {{ request()->is('mp/profile*') ? 'bg-emerald-500/10 text-emerald-400 font-bold border border-emerald-500/20' : 'text-slate-400 hover:bg-slate-800/80 hover:text-slate-200' }}">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            <span>Profile</span>
                        </a>
                    </nav>
                </div>
                
                <!-- Logout Button -->
                <button @click="logout()" class="w-full text-left p-3 text-red-400 hover:bg-red-500/10 rounded-xl transition flex items-center space-x-3 text-sm font-semibold">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                    <span>Logout</span>
                </button>
            </aside>
        @endunless

        <!-- Main Page Content -->
        <main 
            class="flex-1 p-8 transition-all duration-300"
            :class="{ 'ml-64': sidebarOpen && {{ !$isLoginPage ? 'true' : 'false' }}, 'ml-0': !sidebarOpen || {{ $isLoginPage ? 'true' : 'false' }} }"
        >
            @unless($isLoginPage)
                <!-- Toggle Button to show sidebar when hidden -->
                <div class="mb-6">
                    <button 
                        @click="sidebarOpen = !sidebarOpen" 
                        class="p-2.5 bg-slate-900 border border-slate-800 hover:bg-slate-800 rounded-xl text-slate-300 transition flex items-center gap-2"
                        title="Toggle Sidebar"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <span class="text-sm font-medium" x-text="sidebarOpen ? 'Hide Menu' : 'Show Menu'"></span>
                    </button>
                </div>
            @endunless

            {{ $slot }}
        </main>
    </div>

</body>
</html>