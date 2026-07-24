<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Constituency Voice - Connect Representative</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
</head>
<body class="bg-slate-900 text-white font-sans min-h-screen flex flex-col justify-between">

    <!-- Header -->
    <header class="p-6 text-center border-b border-slate-800 backdrop-blur-md bg-slate-900/50 sticky top-0 z-50">
        <h1 class="text-2xl font-bold text-emerald-400 tracking-wide">Constituency Connect</h1>
    </header>

    <!-- Main Container -->
    <main class="max-w-4xl mx-auto p-6 grid md:grid-cols-2 gap-8 items-start my-auto w-full">
        
        <!-- MP Display Card -->
        <div id="mp-card" class="bg-slate-800/80 rounded-2xl p-6 border border-slate-700 shadow-2xl animate__animated animate__fadeInLeft">
            <h3 id="card-header-title" class="text-lg font-bold text-emerald-400 mb-4 border-b border-slate-700 pb-2">Your Elected Representative</h3>
            
            <!-- Default / Loading State -->
            <div id="mp-container" class="space-y-4">
                <div class="flex items-center space-x-4">
                    <img id="mp-avatar" src="/default-avatar.png" class="w-20 h-20 rounded-full object-cover border-2 border-emerald-500 shadow-md" alt="MP Photo">
                    <div>
                        <h4 id="mp-name" class="text-lg font-bold text-white">Detecting location...</h4>
                        <p id="mp-constituency" class="text-sm text-slate-400">Locating constituency...</p>
                        <p id="mp-email" class="text-xs text-sky-400 mt-1"></p>
                    </div>
                </div>
            </div>

            <!-- Multi-Candidate Fallback Container (Hidden by default) -->
            <div id="candidates-list" class="hidden space-y-3 mt-4"></div>
        </div>

        <!-- Telegram / Action Card -->
        <div x-data="locationLinker()" class="bg-slate-800/80 rounded-2xl p-6 border border-slate-700 shadow-2xl animate__animated animate__fadeInRight">
            <h3 class="text-lg font-bold text-emerald-400 mb-2">Connect & Submit Requests</h3>
            <p class="text-slate-300 text-sm mb-6">Send community development issues directly to your representative via Telegram.</p>

            <template x-if="!locationCaptured">
                <button @click="captureLocation()" class="w-full bg-emerald-500 hover:bg-emerald-600 font-bold py-3 px-4 rounded-xl transition duration-300 shadow-lg shadow-emerald-500/20 flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                    Verify Location & Continue
                </button>
            </template>

            <template x-if="locationCaptured">
                <div class="space-y-4 animate__animated animate__fadeIn">
                    <div class="p-3 bg-emerald-950/50 border border-emerald-800/50 text-emerald-400 rounded-xl text-xs flex items-center gap-2">
                        ✓ Location verified: Priority tagging active.
                    </div>
                    <a href="https://t.me/constituency_development_bot" 
                    target="_blank" 
                    class="w-full inline-flex items-center justify-center gap-3 bg-sky-500 hover:bg-sky-600 text-white font-bold px-6 py-4 rounded-2xl shadow-lg transition">
                        <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24"><path d="M12 0C5.37 0 0 5.37 0 12s5.37 12 12 12 12-5.37 12-12S18.63 0 12 0zm5.56 8.16l-2.02 9.53c-.15.68-.55.84-1.12.52l-3.1-2.28-1.5 1.44c-.17.17-.31.31-.63.31l.22-3.17 5.77-5.21c.25-.22-.05-.35-.39-.12l-7.14 4.5-3.07-.96c-.67-.21-.68-.67.14-.99l12.01-4.63c.56-.2 1.05.14.83.96z"/></svg>
                        Submit Request via Telegram
                    </a>
                </div>
            </template>
        </div>

    </main>

    <footer class="p-4 text-center text-xs text-slate-500">
        Powered by Gemma 4 Intelligence Engine
    </footer>

    <script>
        function locationLinker() {
            return {
                locationCaptured: false,
                captureLocation() {
                    if (navigator.geolocation) {
                        navigator.geolocation.getCurrentPosition((pos) => {
                            this.locationCaptured = true;
                            fetchMpDetails(pos.coords.latitude, pos.coords.longitude);
                        }, () => {
                            fetchMpDetails(null, null);
                        });
                    } else {
                        fetchMpDetails(null, null);
                    }
                }
            }
        }

        document.addEventListener("DOMContentLoaded", function () {
            if ("geolocation" in navigator) {
                navigator.geolocation.getCurrentPosition(
                    (pos) => fetchMpDetails(pos.coords.latitude, pos.coords.longitude),
                    () => fetchMpDetails(null, null)
                );
            } else {
                fetchMpDetails(null, null);
            }
        });

        async function fetchMpDetails(lat, lng) {
            try {
                const response = await fetch("/api/citizen/detect-mp", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json",
                    },
                    body: JSON.stringify({ latitude: lat, longitude: lng })
                });

                const data = await response.json();

                if (data.status === "success") {
                    displaySingleMp(data.mp, data.constituency);
                } else if (data.status === "multiple_candidates_found") {
                    displayMultipleMps(data.possible_mps, data.message);
                } else {
                    showError(data.message);
                }
            } catch (err) {
                console.error("Error fetching MP details:", err);
                showError("Unable to load location details.");
            }
        }

        function displaySingleMp(mp, constituency) {
            document.getElementById("card-header-title").innerText = "Your Elected Representative";
            document.getElementById("mp-container").classList.remove("hidden");
            document.getElementById("candidates-list").classList.add("hidden");

            if (mp) {
                document.getElementById("mp-name").innerText = mp.mp_name;
                document.getElementById("mp-constituency").innerText = "Constituency: " + constituency.name;
                document.getElementById("mp-email").innerText = mp.email || '';
                document.getElementById("mp-avatar").src = mp.avatar_path || '/default-avatar.png';
            } else {
                document.getElementById("mp-name").innerText = constituency.name + " Area";
                document.getElementById("mp-constituency").innerText = "No active MP assigned to this constituency yet.";
            }
        }

        function displayMultipleMps(mps, message) {
            document.getElementById("card-header-title").innerText = "Nearby Representatives";
            document.getElementById("mp-container").classList.add("hidden");
            
            const listContainer = document.getElementById("candidates-list");
            listContainer.classList.remove("hidden");
            listContainer.innerHTML = `<p class="text-xs text-slate-400 mb-2">${message}</p>`;

            mps.forEach(mp => {
                const item = document.createElement("div");
                item.className = "flex items-center space-x-3 p-3 bg-slate-900/60 rounded-xl border border-slate-700/50";
                item.innerHTML = `
                    <img src="${mp.avatar_path}" class="w-12 h-12 rounded-full object-cover border border-emerald-500" alt="Avatar">
                    <div>
                        <h5 class="text-sm font-bold text-white">${mp.mp_name}</h5>
                        <p class="text-xs text-emerald-400">${mp.constituency_name}</p>
                        <p class="text-xs text-slate-400">${mp.email || ''}</p>
                    </div>
                `;
                listContainer.appendChild(item);
            });
        }

        function showError(msg) {
            document.getElementById("mp-name").innerText = "Notice";
            document.getElementById("mp-constituency").innerText = msg;
        }
    </script>
</body>
</html>