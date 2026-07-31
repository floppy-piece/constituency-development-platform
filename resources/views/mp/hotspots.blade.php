<x-layout title="Demand Hotspots Map">
    <!-- Leaflet.js CSS and JS CDN -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <div x-data="hotspotsMap()" x-init="initMap()" class="space-y-6">
        
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-100">Demand Hotspots Map (GIS)</h1>
                <p class="text-sm text-slate-400">Geographic visualization of incoming citizen reports and infrastructure requests.</p>
            </div>
            
            <button @click="loadHotspotData()" class="bg-slate-900 border border-slate-800 hover:bg-slate-800 text-slate-300 px-4 py-2 rounded-xl text-sm transition flex items-center gap-2">
                <svg class="w-4 h-4" :class="loading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                <span>Refresh Map Points</span>
            </button>
        </div>

        <!-- Map Container Card -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-xl space-y-4">
            <div id="gis-map" class="w-full h-[600px] rounded-xl border border-slate-800 z-10"></div>
        </div>

    </div>

    <script>
        function hotspotsMap() {
            return {
                map: null,
                geoJsonLayer: null,
                loading: false,

                initMap() {
                    // Initialize Leaflet Map centered over Kenya by default
                    this.map = L.map('gis-map').setView([-1.286389, 36.817223], 7);

                    // Dark theme map tiles
                    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
                        attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
                        maxZoom: 19
                    }).addTo(this.map);

                    this.loadHotspotData();
                },

                async loadHotspotData() {
                    this.loading = true;
                    try {
                        const res = await fetch('/api/mp/hotspots');
                        const data = await res.json();

                        if (data.type === 'FeatureCollection') {
                            if (this.geoJsonLayer) {
                                this.map.removeLayer(this.geoJsonLayer);
                            }

                            const bounds = [];

                            this.geoJsonLayer = L.geoJSON(data, {
                                pointToLayer: (feature, latlng) => {
                                    bounds.push(latlng);
                                    
                                    // Scale circle size based on similarity count/weight
                                    const urgencyColor = feature.properties.urgency === 'high' ? '#ef4444' : 
                                                         (feature.properties.urgency === 'medium' ? '#f59e0b' : '#10b981');

                                    return L.circleMarker(latlng, {
                                        radius: 6 + Math.min(12, feature.properties.heatmap_intensity),
                                        fillColor: urgencyColor,
                                        color: '#ffffff',
                                        weight: 1,
                                        opacity: 0.9,
                                        fillOpacity: 0.7
                                    });
                                },
                                onEachFeature: (feature, layer) => {
                                    const props = feature.properties;
                                    const popupContent = `
                                        <div class="text-slate-900 font-sans p-1">
                                            <div class="font-bold text-sm text-slate-800">Category: ${props.category}</div>
                                            <div class="text-xs my-1 text-slate-600">${props.summary}</div>
                                            <div class="text-xs font-semibold mt-1">
                                                Urgency: <span class="uppercase">${props.urgency}</span> | Reports: ${props.reports_count}
                                            </div>
                                        </div>
                                    `;
                                    layer.bindPopup(popupContent);
                                }
                            }).addTo(this.map);

                            // Auto-fit map bounds around existing hotspots
                            if (bounds.length > 0) {
                                this.map.fitBounds(L.latLngBounds(bounds), { padding: [50, 50] });
                            }
                        }
                    } catch (e) {
                        console.error('Failed to load map points:', e);
                    } finally {
                        this.loading = false;
                    }
                }
            }
        }
        
        document.addEventListener('DOMContentLoaded', function () {
            const mapLat = {{ config('services.map_defaults.lat') }};
            const mapLng = {{ config('services.map_defaults.lng') }};
            const mapZoom = {{ config('services.map_defaults.zoom') }};
            const mapboxToken = "{{ config('services.mapbox.token') }}";

            const map = L.map('hotspots-map').setView([mapLat, mapLng], mapZoom);

            // Option A: Custom Mapbox Dark Tiles
            if (mapboxToken) {
                L.tileLayer(`https://api.mapbox.com/styles/v1/mapbox/dark-v11/tiles/{z}/{x}/{y}?access_token=${mapboxToken}`, {
                    tileSize: 512,
                    zoomOffset: -1,
                    maxZoom: 19,
                    attribution: '&copy; Mapbox &copy; OpenStreetMap'
                }).addTo(map);
            } else {
                // Option B: Free CARTO Dark Tiles (Fallback if no API key is provided)
                L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
                    attribution: '&copy; OpenStreetMap &copy; CARTO'
                }).addTo(map);
            }
        });

    </script>
</x-layout>