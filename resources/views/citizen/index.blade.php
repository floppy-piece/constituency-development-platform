@php
    $gemma = app(\App\Services\Gemma4Service::class);
    $locale = app()->getLocale();

    // Server values are handed to the browser as a JSON island so the script block
    // stays valid JavaScript instead of Blade-interpolated JavaScript.
    $pageData = [
        'csrfToken' => csrf_token(),
        'defaultAvatar' => asset('images/default-avatar.png'),
        'telegramBotUrl' => config('services.telegram.bot_url', 'https://t.me/constituency_development_bot'),
        'whatsappNumber' => config('services.whatsapp.display_number'),
        'translations' => [
            'electedRep' => $gemma->translateContent('Your Elected Representative', $locale),
            'nearbyReps' => $gemma->translateContent('Nearby Representatives', $locale),
            'honorableRep' => $gemma->translateContent('Honorable Representative', $locale),
            'constituencyLabel' => $gemma->translateContent('Constituency', $locale),
            'localRegion' => $gemma->translateContent('Local Region', $locale),
            'noActiveMp' => $gemma->translateContent('No active MP assigned to this constituency yet.', $locale),
            'notice' => $gemma->translateContent('Notice', $locale),
            'defaultError' => $gemma->translateContent('Could not match location to a constituency.', $locale),
            'loadError' => $gemma->translateContent('Unable to load location details.', $locale),
        ],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Constituency Voice') }} - @translate('Connect Representative')</title>

    <!-- Tailwind & Alpine JS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <!-- @vite(['resources/js/app.js']) -->
</head>
<body class="bg-slate-900 text-white font-sans min-h-screen flex flex-col justify-between">

    <!-- Header & Language Switcher -->
    <header class="p-4 px-6 border-b border-slate-800 backdrop-blur-md bg-slate-900/50 sticky top-0 z-50 items-center justify-between">
        <h1 class="text-2xl font-bold text-emerald-400 tracking-wide text-center">
        @translate('Constituency Development Platform')
        </h1><br>
        <hr><br>
        <p class="text-center text-sm text-slate-300">@translate('Share your voice and get heard. Connect via Telegram or WhatsApp to send your development requests.')</p>
        <br>
        <!-- Language Selector Form -->
        <form action="{{ route('language.switch') }}" method="POST" class="flex items-center justify-center gap-2">
            @csrf
            <label for="language-select" class="sr-only">@translate('Select Language')</label>
            <select id="language-select" 
                    name="language" 
                    onchange="this.form.submit()" 
                    class="bg-slate-800 text-slate-200 border border-slate-700 text-xs rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500 cursor-pointer">
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
    </header>

    <!-- Main Container -->
    <main class="max-w-4xl mx-auto p-6 grid md:grid-cols-2 gap-8 items-start my-auto w-full">
        
        <!-- MP Display Card -->
        <div id="mp-card" class="bg-slate-800/80 rounded-2xl p-6 border border-slate-700 shadow-2xl animate__animated animate__fadeInLeft">
            <h3 id="card-header-title" class="text-lg font-bold text-emerald-400 mb-4 border-b border-slate-700 pb-2">
                @translate('Your Elected Representative')
            </h3>
            
            <!-- Default / Loading State -->
            <div id="mp-container" class="space-y-4">
                <div class="flex items-center space-x-4">
                    <img id="mp-avatar" 
                         src="{{ asset('images/default-avatar.png') }}" 
                         data-fallback-src="{{ asset('images/default-avatar.png') }}"
                         class="w-20 h-20 rounded-full object-cover border-2 border-emerald-500 shadow-md" 
                         alt="@translate('MP Photo')">
                    <div>
                        <h4 id="mp-name" class="text-lg font-bold text-white">@translate('Detecting location...')</h4>
                        <p id="mp-constituency" class="text-sm text-slate-400">@translate('Locating constituency...')</p>
                        <p id="mp-email" class="text-xs text-sky-400 mt-1"></p>
                    </div>
                </div>
            </div>

            <!-- Multi-Candidate Fallback Container -->
            <div id="candidates-list" class="hidden space-y-3 mt-4"></div>
        </div>

        <div x-data="locationLinker()" class="bg-slate-800/80 rounded-2xl p-6 border border-slate-700 shadow-2xl animate__animated animate__fadeInRight">
            <h3 class="text-lg font-bold text-emerald-400 mb-2">@translate('Connect & Submit Requests')</h3>
            <p class="text-slate-300 text-sm mb-6">@translate('Physical location coordinates are required to route your issue to the correct ward representative.')</p>

            <!-- Location Capture Action -->
            <template x-if="!locationCaptured">
                <div class="space-y-3">
                    <button @click="captureLocation()" 
                            :disabled="loading"
                            class="w-full bg-emerald-500 hover:bg-emerald-600 disabled:opacity-50 font-bold py-3 px-4 rounded-xl transition duration-300 shadow-lg shadow-emerald-500/20 flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                        <span x-text="loading ? 'Detecting Physical Location...' : 'Share Location & Unlock Submission'"></span>
                    </button>
                    <p class="text-xs text-amber-400 text-center" x-show="locationError" x-text="locationError"></p>
                </div>
            </template>

            <!-- Unlocked State with Ward & Coordinates Displayed -->
            <template x-if="locationCaptured">
                <div class="space-y-4 animate__animated animate__fadeIn">
                    <div class="p-3 bg-emerald-950/50 border border-emerald-800/50 text-emerald-400 rounded-xl text-xs space-y-1">
                        <div class="flex items-center justify-between">
                            <span>✓ Ward: <strong class="text-white" x-text="wardName || 'Locating Ward...'"></strong></span>
                            <button @click="captureLocation()" class="underline text-emerald-300 hover:text-white text-[11px] ml-2">Update</button>
                        </div>
                        <div class="text-[11px] text-emerald-300/80">
                            Lat: <span x-text="lat.toFixed(4)"></span>, Lng: <span x-text="lng.toFixed(4)"></span>
                        </div>
                    </div>
                    
                    <!-- Telegram Button passing coordinates token via start deep-link parameter -->
                    <a :href="telegramUrl" 
                    target="_blank" 
                    rel="noopener noreferrer"
                    class="w-full inline-flex items-center justify-center gap-3 bg-sky-500 hover:bg-sky-600 text-white font-bold px-6 py-4 rounded-2xl shadow-lg transition">
                        @translate('Submit via Telegram')
                    </a>

                    <!-- WhatsApp Button passing coordinates in pre-filled message -->
                    <a :href="whatsappUrl" 
                    target="_blank" 
                    rel="noopener noreferrer"
                    class="w-full inline-flex items-center justify-center gap-3 bg-emerald-600 hover:bg-emerald-500 text-white font-bold px-6 py-4 rounded-2xl shadow-lg transition">
                        @translate('Submit via WhatsApp')
                    </a>
                </div>
            </template>
        </div>

    </main>

    <footer class="p-4 text-center text-xs text-slate-500">
        @translate('Powered by Civic Tech Platform') &copy; {{ date('Y') }}
    </footer>

    <script type="application/json" id="page-data">@json($pageData)</script>

    <script>
        const pageData = JSON.parse(document.getElementById('page-data').textContent);
        const csrfToken = pageData.csrfToken;
        const translations = pageData.translations;
        const defaultAvatar = pageData.defaultAvatar;

        document.getElementById('mp-avatar')?.addEventListener('error', function handleAvatarError() {
            this.removeEventListener('error', handleAvatarError);
            this.src = this.dataset.fallbackSrc;
        });

        function locationLinker() {
            return {
                locationCaptured: false,
                loading: false,
                lat: null,
                lng: null,
                wardName: '',
                locationError: '',
                telegramUrl: pageData.telegramBotUrl,
                whatsappUrl: `https://wa.me/${pageData.whatsappNumber}?text=` + encodeURIComponent('Hello, I would like to submit a constituency request.'),

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
                                        this.telegramUrl = `${pageData.telegramBotUrl}?start=${data.start_payload}`;
                                    }
                                } catch (e) {
                                    console.error('Failed to bind location token for Telegram:', e);
                                }

                                this.whatsappUrl = `https://wa.me/${pageData.whatsappNumber}?text=` + encodeURIComponent(`[SYS_LOC:${this.lat},${this.lng}]\n\n `);
                                this.locationCaptured = true;
                                this.loading = false;
                                
                                // Fetch MP details and capture ward name
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

        // Automatically request fresh live coordinates on page load and update database immediately
        document.addEventListener("DOMContentLoaded", function () {
            if ("geolocation" in navigator) {
                navigator.geolocation.getCurrentPosition(
                    (pos) => {
                        const lat = pos.coords.latitude;
                        const lng = pos.coords.longitude;

                        fetchMpDetails(lat, lng);

                        sessionStorage.setItem('user_lat', lat);
                        sessionStorage.setItem('user_lng', lng);
                        // Automatically sync to database and cache on load
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
            document.getElementById("card-header-title").innerText = translations.electedRep;
            document.getElementById("mp-container").classList.remove("hidden");
            document.getElementById("candidates-list").classList.add("hidden");

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
            document.getElementById("card-header-title").innerText = translations.nearbyReps;
            document.getElementById("mp-container").classList.add("hidden");
            
            const listContainer = document.getElementById("candidates-list");
            listContainer.classList.remove("hidden");
            listContainer.innerHTML = `<p class="text-xs text-slate-400 mb-2">${message}</p>`;

            mps.forEach(mp => {
                const item = document.createElement("div");
                item.className = "flex items-center space-x-3 p-3 bg-slate-900/60 rounded-xl border border-slate-700/50";
                
                const avatar = mp.avatar_path || defaultAvatar;
                
                item.innerHTML = `
                    <img src="${avatar}" onerror="this.src='${defaultAvatar}'" class="w-12 h-12 rounded-full object-cover border border-emerald-500" alt="Avatar">
                    <div>
                        <h5 class="text-sm font-bold text-white">${mp.mp_name}</h5>
                        <p class="text-xs text-emerald-400">${mp.constituency_name || ''}</p>
                        <p class="text-xs text-slate-400">${mp.email || ''}</p>
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