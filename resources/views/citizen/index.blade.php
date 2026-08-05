<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Constituency Voice') }} - @translate('Connect Representative')</title>

    <!-- Tailwind & Alpine JS -->
    <script src="https://cdn.tailwindcss.com"></script>
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
            margin: auto;
        }

        /* Main Container Sheet with Left-Side Spiral Binding Integration */
        .parchment-sheet {
            position: relative;
            background-color: #f2eee3;
            color: #0d0c0cff;
            box-shadow: inset 0 0 50px rgba(243, 239, 235, 0.08), 
                        0 15px 35px rgba(0, 0, 0, 0.15);
            border: none;
            overflow: visible; /* Allow spiral loops to extend outside on the left */
            padding-left: calc(3rem + 15px); /* Extra spacing on the left for the spiral loops */
        }

        /* Left-Side Spiral Binding Graphic Pseudo-element */
        .parchment-sheet::after {
            content: "";
            position: absolute;
            top: 20px;
            bottom: 20px;
            left: -14px;
            width: 20px;
            /* Repeating gradient simulating white wire spiral binding loops vertically */
            background: repeating-linear-gradient(
                180deg,
                transparent,
                transparent 12px,
                #ffffff 12px,
                #ffffff 16px,
                #d1d5db 16px,
                #d1d5db 18px,
                transparent 18px,
                transparent 28px
            );
            z-index: 20;
            pointer-events: none;
            filter: drop-shadow(-3px 0 2px rgba(0,0,0,0.25));
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
            opacity: 0.5;
            background: linear-gradient(
                to bottom,
                #000000 10%,
                #000000 18%,
                #000000 28%,
                #ffffff 40%,
                #ffffff 40%,
                #bb0000 58%,
                #bb0000 62%,
                #ffffff 68%,
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

    @vite(['resources/js/app.js'])
</head>
<body class="font-ledger min-h-screen flex flex-col justify-between selection:bg-stone-300 selection:text-stone-900 text-[#2c241d]">

    <!-- Header & Language Switcher -->
    <header class="p-5 px-8 border-b-2 border-dashed border-stone-400/40 bg-[#e6dfd1] sticky top-0 z-50 shadow-sm">
        <div class="max-w-4xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
            <div>
                <span class="font-typewriter text-[10px] uppercase tracking-widest text-stone-700 block">Official Gazette · Public Notice</span>
                <h1 class="text-xl sm:text-2xl font-bold text-stone-900 font-typewriter tracking-wide">
                    @translate('Constituency Development Platform')
                </h1>
            </div>

            <!-- Language Selector Form -->
            <form action="{{ route('language.switch') }}" method="POST" class="flex items-center gap-2">
                @csrf
                <label for="language-select" class="sr-only">@translate('Select Language')</label>
                <select id="language-select" 
                        name="language" 
                        onchange="this.form.submit()" 
                        class="bg-[#fffaf0] text-stone-900 border border-stone-400/60 text-xs rounded px-3 py-2 font-typewriter focus:outline-none focus:ring-1 focus:ring-stone-600 cursor-pointer shadow-inner">
                    <option value="en" {{ session('app_locale', 'en') == 'en' ? 'selected' : '' }}>English</option>
                    <option value="sw" {{ session('app_locale') == 'sw' ? 'selected' : '' }}>Kiswahili</option>
                    <option value="sheng" {{ session('app_locale') == 'sheng' ? 'selected' : '' }}>Sheng</option>
                    <option value="kikuyu" {{ session('app_locale') == 'kikuyu' ? 'selected' : '' }}>Gĩkũyũ</option>
                    <option value="luo" {{ session('app_locale') == 'luo' ? 'selected' : '' }}>Dholuo</option>
                    <option value="luhya" {{ session('app_locale') == 'luhya' ? 'selected' : '' }}>Luhya</option>
                    <option value="kalenjin" {{ session('app_locale') == 'kalenjin' ? 'selected' : '' }}>Kalenjin</option>
                    <option value="kamba" {{ session('app_locale') == 'kamba' ? 'selected' : '' }}>Kikamba</option>
                </select>
            </form>
        </div>
        <div class="max-w-4xl mx-auto mt-3 pt-3 border-t border-stone-400/30 text-center">
            <p class="text-xs sm:text-sm text-stone-700 font-typewriter italic">
                @translate('Share your voice and get heard. Connect via Telegram or WhatsApp to send your development requests.')
            </p>
        </div>
    </header>

    <!-- Main Container Sheet with Left Spiral Binding and Swaying Blurry Flag Watermark -->
    <main class="max-w-4xl mx-auto p-6 sm:p-12 my-8 w-full parchment-sheet rounded grid md:grid-cols-2 gap-8 items-start relative">
        
        <!-- MP Display Card -->
        <div id="mp-card" class="torn-card p-8 shadow-md animate__animated animate__fadeInLeft">
            <h3 id="card-header-title" class="text-base font-bold text-stone-900 mb-4 border-b border-stone-300 pb-2 font-typewriter uppercase tracking-wide">
                § @translate('Your Elected Representative')
            </h3>
            
            <!-- Default / Loading State -->
            <div id="mp-container" class="space-y-4">
                <div class="flex items-center space-x-4">
                    <img id="mp-avatar" 
                         src="{{ asset('images/default-avatar.png') }}" 
                         onerror="this.onerror=null;this.src='{{ asset('images/default-avatar.png') }}';"
                         class="w-16 h-16 sm:w-20 sm:h-20 rounded-full object-cover border-2 border-stone-400/50 shadow-sm sepia-[0.3]" 
                         alt="@translate('MP Photo')">
                    <div>
                        <h4 id="mp-name" class="text-base font-bold text-stone-900 font-ledger">@translate('Detecting location...')</h4>
                        <p id="mp-constituency" class="text-xs text-stone-700 font-typewriter">@translate('Locating constituency...')</p>
                        <p id="mp-email" class="text-xs text-stone-600 underline mt-1 font-typewriter"></p>
                    </div>
                </div>
            </div>

            <!-- Multi-Candidate Fallback Container -->
            <div id="candidates-list" class="hidden space-y-3 mt-4"></div>
        </div>

        <!-- Connection & Location Action Box -->
        <div x-data="locationLinker()" class="torn-card p-8 shadow-md animate__animated animate__fadeInRight">
            <h3 class="text-base font-bold text-stone-900 mb-2 font-typewriter uppercase tracking-wide">§ @translate('Connect & Submit Requests')</h3>
            <p class="text-stone-700 text-xs mb-6 font-typewriter">@translate('Physical location coordinates are required to route your issue to the correct ward representative.')</p>

            <!-- Location Capture Action -->
            <template x-if="!locationCaptured">
                <div class="space-y-3">
                    <button @click="captureLocation()" 
                            :disabled="loading"
                            class="w-full bg-[#524940] hover:bg-[#3d362f] text-stone-100 disabled:opacity-50 font-bold py-3 px-4 rounded transition duration-300 shadow-md flex items-center justify-center gap-2 font-typewriter text-xs">
                        <svg class="w-4 h-4 text-stone-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                        <span x-text="loading ? 'Detecting Coordinates...' : 'Share Location & Unlock Submission'"></span>
                    </button>
                    <p class="text-xs text-stone-700 text-center font-typewriter" x-show="locationError" x-text="locationError"></p>
                </div>
            </template>

            <!-- Unlocked State with Ward & Coordinates Displayed -->
            <template x-if="locationCaptured">
                <div class="space-y-4 animate__animated animate__fadeIn">
                    <div class="p-3 bg-stone-200/60 border border-stone-300 text-stone-900 rounded text-xs space-y-1 font-typewriter">
                        <div class="flex items-center justify-between">
                            <span>✓ Ward: <strong class="text-stone-950" x-text="wardName || 'Locating Ward...'"></strong></span>
                            <button @click="captureLocation()" class="underline text-stone-700 hover:text-stone-950 text-[11px] ml-2">Update</button>
                        </div>
                        <div class="text-[11px] text-stone-600">
                            Lat: <span x-text="lat.toFixed(4)"></span>, Lng: <span x-text="lng.toFixed(4)"></span>
                        </div>
                    </div>
                    
                    <!-- Telegram Button -->
                    <a :href="telegramUrl" 
                       target="_blank" 
                       rel="noopener noreferrer"
                       class="w-full inline-flex items-center justify-center gap-2 bg-[#44546a] hover:bg-[#334155] text-white font-bold px-4 py-3 rounded shadow transition font-typewriter text-xs">
                        <span>✈</span> @translate('Submit via Telegram')
                    </a>

                    <!-- WhatsApp Button -->
                    <a :href="whatsappUrl" 
                       target="_blank" 
                       rel="noopener noreferrer"
                       class="w-full inline-flex items-center justify-center gap-2 bg-[#486054] hover:bg-[#3a4e44] text-white font-bold px-4 py-3 rounded shadow transition font-typewriter text-xs">
                        <span>💬</span> @translate('Submit via WhatsApp')
                    </a>
                </div>
            </template>
        </div>

    </main>

    <footer class="p-6 text-center text-xs text-stone-700 font-typewriter">
        @translate('Powered by Civic Tech Platform') &copy; {{ date('Y') }} · Republic of Kenya Record
    </footer>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        const translations = {
            electedRep: @json(app(\App\Services\Gemma4Service::class)->translateContent('Your Elected Representative', app()->getLocale())),
            nearbyReps: @json(app(\App\Services\Gemma4Service::class)->translateContent('Nearby Representatives', app()->getLocale())),
            honorableRep: @json(app(\App\Services\Gemma4Service::class)->translateContent('Honorable Representative', app()->getLocale())),
            constituencyLabel: @json(app(\App\Services\Gemma4Service::class)->translateContent('Constituency', app()->getLocale())),
            localRegion: @json(app(\App\Services\Gemma4Service::class)->translateContent('Local Region', app()->getLocale())),
            noActiveMp: @json(app(\App\Services\Gemma4Service::class)->translateContent('No active MP assigned to this constituency yet.', app()->getLocale())),
            notice: @json(app(\App\Services\Gemma4Service::class)->translateContent('Notice', app()->getLocale())),
            defaultError: @json(app(\App\Services\Gemma4Service::class)->translateContent('Could not match location to a constituency.', app()->getLocale())),
            loadError: @json(app(\App\Services\Gemma4Service::class)->translateContent('Unable to load location details.', app()->getLocale()))
        };

        function locationLinker() {
            return {
                locationCaptured: false,
                loading: false,
                lat: null,
                lng: null,
                wardName: '',
                locationError: '',
                telegramUrl: "{{ config('services.telegram.bot_url', 'https://t.me/constituency_development_bot') }}",
                whatsappUrl: "https://wa.me/{{ config('services.whatsapp.display_number') }}?text=Hello,%20I%20would%20like%20to%20submit%20a%20constituency%20request.",

                captureLocation() {
                    this.loading = true;
                    this.locationError = '';
                    
                    if (navigator.geolocation) {
                        navigator.geolocation.getCurrentPosition(
                            async (pos) => {
                                this.lat = pos.coords.latitude;
                                this.lng = pos.coords.longitude;
                                
                                try {
                                    let response = await fetch('/api/citizen/telegram-location-token', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': csrfToken
                                        },
                                        body: JSON.stringify({ 
                                            latitude: this.lat, 
                                            longitude: this.lng 
                                        })
                                    });
                                    let data = await response.json();
                                    
                                    if (data.status === 'success') {
                                        let baseUrl = "{{ config('services.telegram.bot_url', 'https://t.me/constituency_development_bot') }}";
                                        this.telegramUrl = `${baseUrl}?start=${data.start_payload}`;
                                    }
                                } catch (e) {
                                    console.error('Failed to bind location token for Telegram:', e);
                                }

                                this.whatsappUrl = `https://wa.me/{{ config('services.whatsapp.display_number') }}?text=` + encodeURIComponent(`[SYS_LOC:${this.lat},${this.lng}]\n\n `);
                                this.locationCaptured = true;
                                this.loading = false;
                                
                                fetchMpDetails(this.lat, this.lng, (ward) => {
                                    this.wardName = ward;
                                });
                            }, 
                            (err) => {
                                this.loading = false;
                                this.locationError = 'Location access is strictly required to map your ward. Please enable GPS permissions.';
                                console.error('Browser geolocation error:', err);
                            },
                            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                        );
                    } else {
                        this.loading = false;
                        this.locationError = 'Geolocation is not supported by your browser.';
                    }
                }
            }
        }

        document.addEventListener("DOMContentLoaded", function () {
            if ("geolocation" in navigator) {
                navigator.geolocation.getCurrentPosition(
                    (pos) => {
                        const lat = pos.coords.latitude;
                        const lng = pos.coords.longitude;

                        fetchMpDetails(lat, lng);

                        sessionStorage.setItem('user_lat', lat);
                        sessionStorage.setItem('user_lng', lng);
                        fetch('/api/citizen/telegram-location-token', {
                            method: 'POST',
                            headers: { 
                                'Content-Type': 'application/json', 
                                'X-CSRF-TOKEN': csrfToken 
                            },
                            body: JSON.stringify({ 
                                latitude: lat, 
                                longitude: lng 
                            })
                        }).catch((e) => console.error('Failed to sync initial GPS coordinates:', e));
                    },
                    (err) => {
                        console.warn('Geolocation permission denied or unavailable on load:', err);
                        fetchMpDetails(null, null);
                    },
                    { enableHighAccuracy: true, maximumAge: 0 }
                );
            } else {
                fetchMpDetails(null, null);
            }
        });

        async function fetchMpDetails(lat, lng, callback = null) {
            try {
                const response = await fetch("/api/citizen/detect-mp", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json",
                        "X-CSRF-TOKEN": csrfToken
                    },
                    body: JSON.stringify({ latitude: lat, longitude: lng })
                });

                const data = await response.json();

                if (data.status === "success") {
                    displaySingleMp(data.mp, data.constituency);
                    if (callback && data.ward) {
                        callback(data.ward);
                    }
                } else if (data.status === "multiple_candidates_found") {
                    displayMultipleMps(data.possible_mps, data.message);
                } else {
                    showError(data.message || translations.defaultError);
                }
            } catch (err) {
                console.error("Error fetching MP details:", err);
                showError(translations.loadError);
            }
        }

        function displaySingleMp(mp, constituency) {
            document.getElementById("card-header-title").innerText = "§ " + translations.electedRep;
            document.getElementById("mp-container").classList.remove("hidden");
            document.getElementById("candidates-list").classList.add("hidden");

            const defaultAvatar = "{{ asset('images/default-avatar.png') }}";

            if (mp) {
                document.getElementById("mp-name").innerText = mp.mp_name || translations.honorableRep;
                document.getElementById("mp-constituency").innerText = translations.constituencyLabel + ": " + (constituency?.name || translations.localRegion);
                document.getElementById("mp-email").innerText = mp.email || '';
                document.getElementById("mp-avatar").src = mp.avatar_path ? mp.avatar_path : defaultAvatar;
            } else {
                document.getElementById("mp-name").innerText = (constituency?.name || translations.localRegion);
                document.getElementById("mp-constituency").innerText = translations.noActiveMp;
            }
        }

        function displayMultipleMps(mps, message) {
            document.getElementById("card-header-title").innerText = "§ " + translations.nearbyReps;
            document.getElementById("mp-container").classList.add("hidden");
            
            const listContainer = document.getElementById("candidates-list");
            listContainer.classList.remove("hidden");
            listContainer.innerHTML = `<p class="text-xs text-stone-700 mb-2 font-typewriter">${message}</p>`;

            const defaultAvatar = "{{ asset('images/default-avatar.png') }}";

            mps.forEach(mp => {
                const item = document.createElement("div");
                item.className = "flex items-center space-x-3 p-3 bg-[#fffaf0] rounded border border-stone-300";
                
                const avatar = mp.avatar_path || defaultAvatar;
                
                item.innerHTML = `
                    <img src="${avatar}" onerror="this.src='${defaultAvatar}'" class="w-12 h-12 rounded-full object-cover border border-stone-400 sepia-[0.3]" alt="Avatar">
                    <div>
                        <h5 class="text-xs font-bold text-stone-900 font-ledger">${mp.mp_name}</h5>
                        <p class="text-[11px] text-stone-700 font-typewriter">${mp.constituency_name || ''}</p>
                        <p class="text-[10px] text-stone-600 font-typewriter">${mp.email || ''}</p>
                    </div>
                `;
                listContainer.appendChild(item);
            });
        }

        function showError(msg) {
            document.getElementById("mp-name").innerText = translations.notice;
            document.getElementById("mp-constituency").innerText = msg;
        }
    </script>
</body>
</html>