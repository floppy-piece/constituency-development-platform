<x-layout title="Demand Hotspots Map">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <div x-data="hotspotsMap()" x-init="initMap()" class="space-y-6">

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-stone-400/40 pb-4">
            <div>
                <h1 class="text-2xl font-bold text-stone-900 font-typewriter tracking-wide uppercase">Demand Hotspots &amp; Recurring Themes</h1>
                <p class="text-xs text-stone-700 font-typewriter">
                    Geographic demand signals — matches are theme evidence, not duplicates to dismiss.
                </p>
            </div>

            <button @click="loadHotspotData()" class="bg-[#e6dfd1] border border-stone-400/60 hover:bg-[#dcd5c1] text-stone-900 px-4 py-2 rounded font-typewriter text-xs transition flex items-center gap-2 shadow-sm">
                <svg class="w-4 h-4" :class="loading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                <span>Refresh Hotspots</span>
            </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="torn-card p-5 rounded-none border border-stone-300">
                <span class="text-[10px] font-semibold uppercase tracking-wider text-stone-700 font-typewriter">Active Themes</span>
                <p class="text-2xl font-black text-stone-900 font-typewriter mt-1" x-text="metrics.theme_count || 0"></p>
            </div>
            <div class="torn-card p-5 rounded-none border border-stone-300">
                <span class="text-[10px] font-semibold uppercase tracking-wider text-stone-700 font-typewriter">Rising Themes</span>
                <p class="text-2xl font-black text-amber-800 font-typewriter mt-1" x-text="metrics.rising_themes || 0"></p>
            </div>
            <div class="torn-card p-5 rounded-none border border-stone-300">
                <span class="text-[10px] font-semibold uppercase tracking-wider text-stone-700 font-typewriter">Clustered Reports</span>
                <p class="text-2xl font-black text-emerald-800 font-typewriter mt-1" x-text="metrics.total_clustered_reports || 0"></p>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="xl:col-span-2 torn-card rounded-none p-4 shadow-sm border border-stone-300 space-y-4">
                <div id="gis-map" class="w-full h-[600px] rounded border border-stone-300 z-10"></div>
            </div>

            <div class="torn-card rounded-none p-5 shadow-sm border border-stone-300 space-y-4 max-h-[660px] overflow-y-auto">
                <div class="border-b border-stone-300 pb-3">
                    <h2 class="text-base font-bold text-stone-900 font-typewriter uppercase">Theme Cards</h2>
                    <p class="text-xs text-stone-700 font-typewriter mt-1">Recurring issues by geography, category, and time window.</p>
                </div>

                <div x-show="loading" class="space-y-3">
                    <div class="h-24 bg-stone-200 animate-pulse rounded"></div>
                    <div class="h-24 bg-stone-200 animate-pulse rounded"></div>
                </div>

                <div x-show="!loading && themes.length === 0" class="text-center py-10 border border-dashed border-stone-400 rounded">
                    <p class="text-stone-700 font-typewriter text-xs">No recurring themes yet. New citizen reports will form themes automatically.</p>
                </div>

                <div x-show="!loading && themes.length > 0" class="space-y-3">
                    <template x-for="theme in themes" :key="theme.cluster_id">
                        <button
                            type="button"
                            @click="focusTheme(theme)"
                            class="w-full text-left bg-white border border-stone-300 hover:border-stone-500 rounded p-4 space-y-3 transition shadow-xs"
                            :class="{ 'border-emerald-600 bg-emerald-50/30': selectedClusterId === theme.cluster_id }"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-bold text-stone-900 font-typewriter uppercase" x-text="theme.theme_label"></p>
                                    <p class="text-[11px] text-stone-700 font-typewriter mt-0.5" x-text="(theme.category || 'General') + ' · ' + (theme.ward_name || 'Multiple wards')"></p>
                                </div>
                                <span
                                    class="px-2.5 py-0.5 text-[10px] rounded font-bold uppercase tracking-wide border font-typewriter"
                                    :class="{
                                        'bg-amber-100 text-amber-900 border-amber-300': theme.trend === 'rising',
                                        'bg-sky-100 text-sky-900 border-sky-300': theme.trend === 'stable',
                                        'bg-stone-200 text-stone-800 border-stone-300': theme.trend === 'falling'
                                    }"
                                    x-text="theme.trend"
                                ></span>
                            </div>

                            <p class="text-xs text-stone-900 font-ledger leading-relaxed" x-text="theme.summary"></p>

                            <div class="flex flex-wrap items-center gap-3 text-[10px] font-typewriter text-stone-700 border-t border-stone-200 pt-2">
                                <span x-text="theme.report_count + ' report' + (theme.report_count === 1 ? '' : 's')"></span>
                                <template x-if="theme.trend_change_pct != null">
                                    <span
                                        :class="theme.trend_change_pct > 0 ? 'text-amber-800' : (theme.trend_change_pct < 0 ? 'text-stone-700' : 'text-stone-600')"
                                        x-text="(theme.trend_change_pct > 0 ? '+' : '') + theme.trend_change_pct + '% vs last week'"
                                    ></span>
                                </template>
                            </div>
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <script>
        function hotspotsMap() {
            return {
                map: null,
                geoJsonLayer: null,
                clusterLayer: null,
                loading: false,
                themes: [],
                metrics: {},
                selectedClusterId: null,

                initMap() {
                    // Initialize map centered around Kenya / typical bounds
                    this.map = L.map('gis-map').setView([-1.286389, 36.817223], 7);

                    // Custom vintage sepia/parchment styled Carto tile layer to match aesthetic
                    L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
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

                        this.themes = data.themes || [];
                        this.metrics = data.metrics || {};

                        if (this.geoJsonLayer) {
                            this.map.removeLayer(this.geoJsonLayer);
                        }
                        if (this.clusterLayer) {
                            this.map.removeLayer(this.clusterLayer);
                        }

                        const bounds = [];

                        if (data.type === 'FeatureCollection') {
                            this.geoJsonLayer = L.geoJSON({
                                type: 'FeatureCollection',
                                features: data.features || []
                            }, {
                                pointToLayer: (feature, latlng) => {
                                    bounds.push(latlng);
                                    const urgencyColor = feature.properties.urgency === 'high' ? '#b91c1c'
                                        : (feature.properties.urgency === 'medium' ? '#b45309' : '#047857');

                                    return L.circleMarker(latlng, {
                                        radius: 5 + Math.min(10, feature.properties.heatmap_intensity || 1),
                                        fillColor: urgencyColor,
                                        color: '#ffffff',
                                        weight: 1,
                                        opacity: 0.85,
                                        fillOpacity: 0.75
                                    });
                                },
                                onEachFeature: (feature, layer) => {
                                    const props = feature.properties;
                                    layer.bindPopup(`
                                        <div class="text-stone-900 font-mono p-1">
                                            <div class="font-bold text-xs uppercase">${props.theme_label || props.category}</div>
                                            <div class="text-xs my-1 text-stone-700 font-serif">${props.summary || ''}</div>
                                            <div class="text-[10px] font-semibold mt-1">
                                                Urgency: <span class="uppercase">${props.urgency || 'n/a'}</span>
                                                | Reports: ${props.reports_count || 1}
                                            </div>
                                        </div>
                                    `);
                                }
                            }).addTo(this.map);
                        }

                        if ((data.cluster_features || []).length > 0) {
                            this.clusterLayer = L.geoJSON({
                                type: 'FeatureCollection',
                                features: data.cluster_features
                            }, {
                                pointToLayer: (feature, latlng) => {
                                    bounds.push(latlng);
                                    return L.circleMarker(latlng, {
                                        radius: 10 + Math.min(14, feature.properties.reports_count || 1),
                                        fillColor: '#0369a1',
                                        color: '#e0f2fe',
                                        weight: 2,
                                        opacity: 1,
                                        fillOpacity: 0.45
                                    });
                                },
                                onEachFeature: (feature, layer) => {
                                    const props = feature.properties;
                                    layer.bindPopup(`
                                        <div class="text-stone-900 font-mono p-1">
                                            <div class="font-bold text-xs uppercase">${props.theme_label}</div>
                                            <div class="text-xs my-1 font-serif">${props.summary || ''}</div>
                                            <div class="text-[10px] font-semibold">Theme reports: ${props.reports_count} · ${props.trend || 'stable'}</div>
                                        </div>
                                    `);
                                }
                            }).addTo(this.map);
                        }

                        if (bounds.length > 0) {
                            this.map.fitBounds(L.latLngBounds(bounds), { padding: [50, 50] });
                        }
                    } catch (e) {
                        console.error('Failed to load map points:', e);
                    } finally {
                        this.loading = false;
                    }
                },

                focusTheme(theme) {
                    this.selectedClusterId = theme.cluster_id;
                    if (theme.centroid_lat != null && theme.centroid_lng != null && this.map) {
                        this.map.setView([theme.centroid_lat, theme.centroid_lng], 13, { animate: true });
                    }
                }
            }
        }
    </script>
</x-layout>