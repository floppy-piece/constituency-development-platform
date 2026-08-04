@props(['title' => 'MP Portal'])
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    
    <!-- Google Fonts for Classic Ledger/Book Typography -->
    <link href="https://fonts.googleapis.com/css2?family=Special+Elite&family=Newsreader:ital,opsz,wght@0,6..72,400..700;1,6..72,400..700&family=Courier+Prime&display=swap" rel="stylesheet">

    <style>
        .font-ledger { font-family: 'Newsreader', Georgia, serif; }
        .font-typewriter { font-family: 'Courier Prime', Courier, monospace; }
        .font-stamp { font-family: 'Special Elite', cursive; }

        /* Warm Cream / Antique Ivory Desk Background */
        body {
            background-color: #f2eee3;
            background-image: radial-gradient(#dcd5c1 1px, transparent 1px), radial-gradient(#dcd5c1 1px, #f2eee3 1px);
            background-size: 40px 40px;
            background-position: 0 0, 20px 20px;
        }

        /* Main Container Sheet with Swaying Blurry Kenyan Flag Watermark / Background */
        .parchment-sheet {
            position: relative;
            background-color: rgba(215, 210, 200, 0.88);
            color: #2c241d;
            box-shadow: inset 0 0 50px rgba(139, 115, 85, 0.08), 
                        0 15px 35px rgba(0, 0, 0, 0.15);
            border: 1px solid #c3c3bfff;
            overflow: hidden;
            opacity: 0.9;
        }

        /* Swaying / Waving Wind Animation Keyframes */
        @keyframes flagSway {
            0% {
                transform: scale(1.4) skewX(0deg) translateY(0px);
                filter: blur(45px) hue-rotate(0deg);
            }
            25% {
                transform: scale(1.45) skewX(-4deg) translateY(-3px);
                filter: blur(48px) hue-rotate(-2deg);
            }
            50% {
                transform: scale(1.4) skewX(2deg) translateY(2px);
                filter: blur(42px) hue-rotate(2deg);
            }
            75% {
                transform: scale(1.42) skewX(-2deg) translateY(-1px);
                filter: blur(46px) hue-rotate(-1deg);
            }
            100% {
                transform: scale(1.4) skewX(0deg) translateY(0px);
                filter: blur(45px) hue-rotate(0deg);
            }
        }

        /* Blurry Kenyan Flag Abstract Layers with Wind Effect */
        .parchment-sheet::before {
            content: "";
            position: absolute;
            inset: -20px;
            z-index: -1;
            opacity: 1;
            background: linear-gradient(
                to bottom,
                #000000 0%,
                #000000 28%,
                #000000 28%,
                #ffffff 34%,
                #ffffff 34%,
                #bb0000 38%,
                #bb0000 38%,
                #bb0000 44%,
                #ffffff 44%,
                #ffffff 72%,
                #006600 72%,
                #006600 100%
            );
            animation: flagSway 7s ease-in-out infinite;
            transform-origin: center;
        }

        /* Ragged Wavy Ripped Paper Cards */
        .torn-card {
            background-color: #ffffff;
            color: #2c241d;
            box-shadow: 3px 5px 15px rgba(0,0,0,0.06);
            position: relative;
            clip-path: polygon(
                0% 0%, 
                100% 1%, 
                99% 10%, 
                100% 20%, 
                98% 30%, 
                100% 40%, 
                99% 50%, 
                100% 60%, 
                98% 70%, 
                100% 80%, 
                99% 90%, 
                100% 100%, 
                1% 99%, 
                0% 90%, 
                2% 80%, 
                0% 70%, 
                1% 60%, 
                0% 50%, 
                2% 40%, 
                0% 30%, 
                1% 20%, 
                0% 10%
            );
        }
    </style>

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
<body class="font-ledger min-h-screen flex flex-col justify-between selection:bg-stone-300 selection:text-stone-900 text-[#2c241d]">

    @php
        // Matches /mp/auth/login, /mp/login, or any login route
        $isLoginPage = request()->is('mp/auth/login') || request()->is('mp/login') || request()->is('*login*');
    @endphp

    <div class="min-h-screen flex flex-col justify-between" 
         x-data="{ 
             sidebarOpen: true, 
             logout() { 
                 localStorage.removeItem('jwt_token'); 
                 window.location.href = '/mp/auth/login'; 
             } 
         }">
        
        <div class="{{ !$isLoginPage ? 'flex' : '' }} flex-1">
            @unless($isLoginPage)
                <!-- Sidebar Navigation (Hidden on Login) -->
                <aside 
                    class="w-64 bg-[#e6dfd1] h-screen p-6 border-r border-dashed border-stone-400/40 flex flex-col justify-between fixed left-0 top-0 z-20 transition-transform duration-300 ease-in-out shadow-sm"
                    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
                >
                    <div>
                        <!-- Header -->
                        <div class="flex items-center justify-between mb-8 pb-3 border-b border-stone-400/30">
                            <div>
                                <span class="font-typewriter text-[9px] uppercase tracking-widest text-stone-700 block">Official Gazette</span>
                                <h1 class="text-sm font-bold text-stone-900 font-typewriter tracking-wide uppercase">Constituency Portal</h1>
                            </div>
                            <!-- Close button inside sidebar -->
                            <button @click="sidebarOpen = false" class="text-stone-700 hover:text-stone-950 transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Navigation Links with SVGs -->
                        <nav class="space-y-2 font-typewriter text-xs">
                            <!-- Dashboard -->
                            <a href="/mp/dashboard" 
                               class="flex items-center space-x-3 px-3.5 py-2.5 rounded transition {{ request()->is('mp/dashboard') ? 'bg-[#524940] text-stone-100 font-bold shadow' : 'text-stone-800 hover:bg-[#dcd5c1]/60 hover:text-stone-950' }}">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                </svg>
                                <span>Dashboard</span>
                            </a>

                            <!-- Demand Hotspots -->
                            <a href="/mp/hotspots" 
                               class="flex items-center space-x-3 px-3.5 py-2.5 rounded transition {{ request()->is('mp/hotspots*') ? 'bg-[#524940] text-stone-100 font-bold shadow' : 'text-stone-800 hover:bg-[#dcd5c1]/60 hover:text-stone-950' }}">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                <span>Demand Hotspots</span>
                            </a>

                            <!-- Priorities -->
                            <a href="/mp/priorities"
                               class="flex items-center space-x-3 px-3.5 py-2.5 rounded transition {{ request()->is('mp/priorities*') ? 'bg-[#524940] text-stone-100 font-bold shadow' : 'text-stone-800 hover:bg-[#dcd5c1]/60 hover:text-stone-950' }}">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"></path>
                                </svg>
                                <span>Priorities</span>
                            </a>

                            <!-- All Requests -->
                            <a href="/mp/requests"
                               class="flex items-center space-x-3 px-3.5 py-2.5 rounded transition {{ request()->is('mp/requests*') ? 'bg-[#524940] text-stone-100 font-bold shadow' : 'text-stone-800 hover:bg-[#dcd5c1]/60 hover:text-stone-950' }}">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                                <span>All Requests</span>
                            </a>

                            <!-- Feasibility Matrix -->
                            <a href="{{ route('mp.matrix') }}" 
                               class="flex items-center space-x-3 px-3.5 py-2.5 rounded transition {{ request()->routeIs('mp.matrix') || request()->is('mp/matrix*') ? 'bg-[#524940] text-stone-100 font-bold shadow' : 'text-stone-800 hover:bg-[#dcd5c1]/60 hover:text-stone-950' }}">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 002-2h2a2 2 0 002 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 002-2h2a2 2 0 002 2v14a2 2 0 002 2h2a2 2 0 002-2z"></path>
                                </svg>
                                <span>Feasibility Matrix</span>
                            </a>

                            <!-- Analytics -->
                            <a href="/mp/analytics" 
                               class="flex items-center space-x-3 px-3.5 py-2.5 rounded transition {{ request()->is('mp/analytics') ? 'bg-[#524940] text-stone-100 font-bold shadow' : 'text-stone-800 hover:bg-[#dcd5c1]/60 hover:text-stone-950' }}">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                                </svg>
                                <span>Analytics</span>
                            </a>

                            <!-- Profile -->
                            <a href="/mp/profile/show" 
                               class="flex items-center space-x-3 px-3.5 py-2.5 rounded transition {{ request()->is('mp/profile*') ? 'bg-[#524940] text-stone-100 font-bold shadow' : 'text-stone-800 hover:bg-[#dcd5c1]/60 hover:text-stone-950' }}">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                <span>Profile</span>
                            </a>
                        </nav>
                    </div>
                    
                    <!-- Logout Button -->
                    <button @click="logout()" class="w-full text-left p-2.5 text-stone-800 hover:bg-stone-300/60 rounded transition flex items-center space-x-3 text-xs font-typewriter">
                        <svg class="w-4 h-4 shrink-0 text-red-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        <span class="font-bold">Logout</span>
                    </button>
                </aside>
            @endunless

            <!-- Main Page Content wrapped in parchment-sheet container -->
            <main 
                class="flex-1 p-6 sm:p-12 my-8 mx-6 sm:mx-auto max-w-6xl w-full parchment-sheet rounded transition-all duration-300"
                :class="{ 'md:ml-72': sidebarOpen && {{ !$isLoginPage ? 'true' : 'false' }}, 'ml-0': !sidebarOpen || {{ $isLoginPage ? 'true' : 'false' }} }"
            >
                @unless($isLoginPage)
                    <!-- Toggle Button to show sidebar when hidden -->
                    <div class="mb-6">
                        <button 
                            @click="sidebarOpen = !sidebarOpen" 
                            class="py-2 px-3 bg-[#e6dfd1] border border-stone-400/60 hover:bg-[#dcd5c1] rounded text-stone-800 transition flex items-center gap-2 font-typewriter text-xs shadow-sm"
                            title="Toggle Sidebar"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                            <span x-text="sidebarOpen ? 'Hide Menu' : 'Show Menu'"></span>
                        </button>
                    </div>
                @endunless

                {{ $slot }}
            </main>
        </div>

        <footer class="p-6 text-center text-xs text-stone-700 font-typewriter border-t border-dashed border-stone-400/40 bg-[#e6dfd1]/50 mt-auto">
            Powered by Civic Tech Platform &copy; {{ date('Y') }} · Republic of Kenya Record
        </footer>
    </div>

</body>
</html>