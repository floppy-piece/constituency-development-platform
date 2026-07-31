<x-layout title="Demand Hotspots Map">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <div x-data="hotspotsMap()" x-init="initMap()" class="space-y-6">

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-100">Demand Hotspots &amp; Recurring Themes</h1>
                <p class="text-sm text-slate-400">
                    Geographic demand signals — matches are theme evidence, not duplicates to dismiss.
                </p>
            </div>

            <button @click="loadHotspotData()" class="bg-slate-900 border border-slate-800 hover:bg-slate-800 text-slate-300 px-4 py-2 rounded-xl text-sm transition flex items-center gap-2">
                <svg class="w-4 h-4" :class="loading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                <span>Refresh Hotspots</span>
            </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-slate-900 border border-slate-800 p-5 rounded-2xl">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Active Themes</span>
                <p class="text-3xl font-black text-slate-100 mt-1" x-text="metrics.theme_count || 0"></p>
            </div>
            <div class="bg-slate-900 border border-slate-800 p-5 rounded-2xl">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Rising Themes</span>
                <p class="text-3xl font-black text-amber-400 mt-1" x-text="metrics.rising_themes || 0"></p>
            </div>
            <div class="bg-slate-900 border border-slate-800 p-5 rounded-2xl">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Clustered Reports</span>
                <p class="text-3xl font-black text-emerald-400 mt-1" x-text="metrics.total_clustered_reports || 0"></p>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="xl:col-span-2 bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-xl space-y-4">
                <div id="gis-map" class="w-full h-[600px] rounded-xl border border-slate-800 z-10"></div>
            </div>

            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl space-y-4 max-h-[660px] overflow-y-auto">
                <div>
                    <h2 class="text-lg font-bold text-slate-100">Theme Cards</h2>
                    <p class="text-xs text-slate-500 mt-1">Recurring issues by geography, category, and time window.</p>
                </div>

                <div x-show="loading" class="space-y-3">
                    <div class="h-24 bg-slate-800/50 animate-pulse rounded-xl"></div>
                    <div class="h-24 bg-slate-800/50 animate-pulse rounded-xl"></div>
                </div>

                <div x-show="!loading && themes.length === 0" class="text-center py-10 border border-dashed border-slate-800 rounded-xl">
                    <p class="text-slate-400 text-sm">No recurring themes yet. New citizen reports will form themes automatically.</p>
                </div>

                <div x-show="!loading && themes.length > 0" class="space-y-3">
                    <template x-for="theme in themes" :key="theme.cluster_id">
                        <button
                            type="button"
                            @click="focusTheme(theme)"
                            class="w-full text-left bg-slate-950/70 border border-slate-800 hover:border-slate-600 rounded-xl p-4 space-y-3 transition"
                            :class="{ 'border-emerald-500/40': selectedClusterId === theme.cluster_id }"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-bold text-slate-100" x-text="theme.theme_label"></p>
                                    <p class="text-xs text-slate-400 mt-0.5" x-text="(theme.category || 'General') + ' · ' + (theme.ward_name || 'Multiple wards')"></p>
                                </div>
                                <span
                                    class="px-2.5 py-0.5 text-[10px] rounded-full font-bold uppercase tracking-wide border"
                                    :class="{
                                        'bg-amber-500/10 text-amber-400 border-amber-500/30': theme.trend === 'rising',
                                        'bg-sky-500/10 text-sky-400 border-sky-500/30': theme.trend === 'stable',
                                        'bg-slate-500/10 text-slate-300 border-slate-500/30': theme.trend === 'falling'
                                    }"
                                    x-text="theme.trend"
                                ></span>
                            </div>

                            <p class="text-xs text-slate-300 leading-relaxed" x-text="theme.summary"></p>

                            <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500">
                                <span x-text="theme.report_count + ' report' + (theme.report_count === 1 ? '' : 's')"></span>
                                <template x-if="theme.trend_change_pct != null">
                                    <span
                                        :class="theme.trend_change_pct > 0 ? 'text-amber-400' : (theme.trend_change_pct < 0 ? 'text-slate-400' : 'text-slate-500')"
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
                    this.map = L.map('gis-map').setView([-1.286389, 36.817223], 7);

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
                                    const urgencyColor = feature.properties.urgency === 'high' ? '#ef4444'
                                        : (feature.properties.urgency === 'medium' ? '#f59e0b' : '#10b981');

                                    return L.circleMarker(latlng, {
                                        radius: 5 + Math.min(10, feature.properties.heatmap_intensity || 1),
                                        fillColor: urgencyColor,
                                        color: '#ffffff',
                                        weight: 1,
                                        opacity: 0.85,
                                        fillOpacity: 0.65
                                    });
                                },
                                onEachFeature: (feature, layer) => {
                                    const props = feature.properties;
                                    layer.bindPopup(`
                                        <div class="text-slate-900 font-sans p-1">
                                            <div class="font-bold text-sm">${props.theme_label || props.category}</div>
                                            <div class="text-xs my-1 text-slate-600">${props.summary || ''}</div>
                                            <div class="text-xs font-semibold mt-1">
                                                Urgency: <span class="uppercase">${props.urgency || 'n/a'}</span>
                                                | Reports in theme: ${props.reports_count || 1}
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
                                        fillColor: '#38bdf8',
                                        color: '#e0f2fe',
                                        weight: 2,
                                        opacity: 1,
                                        fillOpacity: 0.35
                                    });
                                },
                                onEachFeature: (feature, layer) => {
                                    const props = feature.properties;
                                    layer.bindPopup(`
                                        <div class="text-slate-900 font-sans p-1">
                                            <div class="font-bold text-sm">${props.theme_label}</div>
                                            <div class="text-xs my-1">${props.summary || ''}</div>
                                            <div class="text-xs font-semibold">Theme reports: ${props.reports_count} · ${props.trend || 'stable'}</div>
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
