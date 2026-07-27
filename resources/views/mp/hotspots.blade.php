<x-layout title="Demand Hotspots Map">
    <!-- Leaflet + heatmap plugin -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>

    <div x-data="hotspotsMap()" x-init="initMap()" class="space-y-6">
        
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-100">Demand Hotspots Map</h1>
                <p class="text-sm text-slate-400">Geographic heatmap of open constituent reports — weighted by urgency and cluster size.</p>
            </div>
            
            <div class="flex flex-wrap items-center gap-2">
                <div class="inline-flex rounded-xl border border-slate-800 bg-slate-900 p-1 text-xs">
                    <button type="button"
                        @click="setViewMode('heatmap')"
                        class="px-3 py-1.5 rounded-lg font-semibold transition"
                        :class="viewMode === 'heatmap' ? 'bg-emerald-500/20 text-emerald-300' : 'text-slate-400 hover:text-slate-200'">
                        Heatmap
                    </button>
                    <button type="button"
                        @click="setViewMode('markers')"
                        class="px-3 py-1.5 rounded-lg font-semibold transition"
                        :class="viewMode === 'markers' ? 'bg-emerald-500/20 text-emerald-300' : 'text-slate-400 hover:text-slate-200'">
                        Markers
                    </button>
                    <button type="button"
                        @click="setViewMode('both')"
                        class="px-3 py-1.5 rounded-lg font-semibold transition"
                        :class="viewMode === 'both' ? 'bg-emerald-500/20 text-emerald-300' : 'text-slate-400 hover:text-slate-200'">
                        Both
                    </button>
                </div>

                <button @click="loadHotspotData()" class="bg-slate-900 border border-slate-800 hover:bg-slate-800 text-slate-300 px-4 py-2 rounded-xl text-sm transition flex items-center gap-2">
                    <svg class="w-4 h-4" :class="loading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    <span>Refresh</span>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Mapped reports</span>
                <p class="text-2xl font-black text-slate-100 mt-1" x-text="meta.mapped_requests || 0"></p>
            </div>
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">High urgency</span>
                <p class="text-2xl font-black text-red-400 mt-1" x-text="meta.high_urgency_mapped || 0"></p>
            </div>
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Ward clusters</span>
                <p class="text-2xl font-black text-sky-400 mt-1" x-text="meta.ward_clusters || 0"></p>
            </div>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-xl space-y-3">
            <div id="gis-map" class="w-full h-[600px] rounded-xl border border-slate-800 z-10"></div>
            <div class="flex flex-wrap items-center gap-4 text-xs text-slate-400 px-1">
                <span class="inline-flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-blue-500/40"></span> Cooler demand
                </span>
                <span class="inline-flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-yellow-400"></span> Elevated
                </span>
                <span class="inline-flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-red-500"></span> Critical concentration
                </span>
                <span class="text-slate-500">Heat uses GPS when available; otherwise ward centroid.</span>
            </div>
        </div>

    </div>

    <script>
        function hotspotsMap() {
            return {
                map: null,
                geoJsonLayer: null,
                heatLayer: null,
                loading: false,
                viewMode: 'both',
                lastPayload: null,
                meta: {
                    mapped_requests: 0,
                    high_urgency_mapped: 0,
                    ward_clusters: 0,
                },

                initMap() {
                    this.map = L.map('gis-map').setView([-1.286389, 36.817223], 7);

                    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
                        attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
                        maxZoom: 19
                    }).addTo(this.map);

                    this.loadHotspotData();
                },

                setViewMode(mode) {
                    this.viewMode = mode;
                    this.renderLayers();
                },

                clearLayers() {
                    if (this.geoJsonLayer) {
                        this.map.removeLayer(this.geoJsonLayer);
                        this.geoJsonLayer = null;
                    }
                    if (this.heatLayer) {
                        this.map.removeLayer(this.heatLayer);
                        this.heatLayer = null;
                    }
                },

                buildHeatPoints(data) {
                    const points = [];

                    (data.heat_points || []).forEach((p) => {
                        if (Array.isArray(p) && p.length >= 2) {
                            points.push([p[0], p[1], p[2] ?? 0.5]);
                        }
                    });

                    // Blend ward centroids so sparse GPS still shows constituency pressure
                    (data.ward_heat || []).forEach((ward) => {
                        if (ward.heat && Array.isArray(ward.heat)) {
                            points.push(ward.heat);
                        }
                    });

                    return points;
                },

                renderLayers() {
                    if (!this.map || !this.lastPayload) return;

                    this.clearLayers();
                    const data = this.lastPayload;
                    const bounds = [];

                    if (this.viewMode === 'heatmap' || this.viewMode === 'both') {
                        const heatPoints = this.buildHeatPoints(data);
                        if (heatPoints.length && typeof L.heatLayer === 'function') {
                            this.heatLayer = L.heatLayer(heatPoints, {
                                radius: 28,
                                blur: 22,
                                maxZoom: 17,
                                minOpacity: 0.35,
                                gradient: {
                                    0.2: '#1d4ed8',
                                    0.45: '#22d3ee',
                                    0.65: '#facc15',
                                    0.85: '#f97316',
                                    1.0: '#ef4444',
                                },
                            }).addTo(this.map);

                            heatPoints.forEach((p) => bounds.push([p[0], p[1]]));
                        }
                    }

                    if (this.viewMode === 'markers' || this.viewMode === 'both') {
                        this.geoJsonLayer = L.geoJSON(data, {
                            pointToLayer: (feature, latlng) => {
                                bounds.push(latlng);

                                const urgencyColor = feature.properties.urgency === 'high' ? '#ef4444'
                                    : (feature.properties.urgency === 'medium' ? '#f59e0b' : '#10b981');

                                return L.circleMarker(latlng, {
                                    radius: 5 + Math.min(10, feature.properties.heatmap_intensity || 1),
                                    fillColor: urgencyColor,
                                    color: '#ffffff',
                                    weight: 1,
                                    opacity: 0.9,
                                    fillOpacity: this.viewMode === 'both' ? 0.55 : 0.75,
                                });
                            },
                            onEachFeature: (feature, layer) => {
                                const props = feature.properties;
                                const wardLine = props.ward_name ? `<div class="text-xs text-slate-500">Ward: ${props.ward_name}</div>` : '';
                                const sourceLine = props.coord_source === 'ward_centroid'
                                    ? `<div class="text-[10px] text-amber-600 mt-1">Located via ward centroid</div>`
                                    : '';

                                layer.bindPopup(`
                                    <div class="text-slate-900 font-sans p-1 max-w-xs">
                                        <div class="font-bold text-sm text-slate-800">${props.category || 'General'}</div>
                                        <div class="text-xs my-1 text-slate-600">${props.summary || ''}</div>
                                        ${wardLine}
                                        <div class="text-xs font-semibold mt-1">
                                            Urgency: <span class="uppercase">${props.urgency || 'low'}</span>
                                            · Reports: ${props.reports_count || 1}
                                        </div>
                                        ${sourceLine}
                                    </div>
                                `);
                            }
                        }).addTo(this.map);
                    }

                    if (bounds.length > 0) {
                        this.map.fitBounds(L.latLngBounds(bounds), { padding: [50, 50], maxZoom: 14 });
                    }
                },

                async loadHotspotData() {
                    this.loading = true;
                    try {
                        const res = await fetch('/api/mp/hotspots');
                        const data = await res.json();

                        if (data.type === 'FeatureCollection') {
                            this.lastPayload = data;
                            this.meta = data.meta || {
                                mapped_requests: (data.features || []).length,
                                high_urgency_mapped: 0,
                                ward_clusters: (data.ward_heat || []).length,
                            };
                            this.renderLayers();
                        }
                    } catch (e) {
                        console.error('Failed to load map points:', e);
                    } finally {
                        this.loading = false;
                    }
                }
            }
        }
    </script>
</x-layout>
