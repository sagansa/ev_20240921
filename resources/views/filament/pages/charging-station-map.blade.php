<x-filament-panels::page>
    {{-- Leaflet CSS inline (tidak pakai @push yg bisa di-strip view:cache) --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>

    <style>
        #cs-map { background: #e5e7eb; }
        .leaflet-pane, .leaflet-top, .leaflet-bottom { z-index: 1 !important; }
        .leaflet-popup-pane { z-index: 2 !important; }
        .cs-spinner {
            display: inline-block;
            width: 32px; height: 32px;
            border: 4px solid #d1d5db;
            border-top-color: #3b82f6;
            border-radius: 50%;
            animation: cs-spin 0.8s linear infinite;
        }
        @keyframes cs-spin { to { transform: rotate(360deg); } }
    </style>

    <div class="mb-4 flex flex-wrap gap-2 text-sm">
        <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded-full inline-block" style="background:#10B981"></span> Available</span>
        <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded-full inline-block" style="background:#EF4444"></span> Occupied</span>
        <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded-full inline-block" style="background:#6B7280"></span> Offline</span>
        <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded-full inline-block" style="background:#D1D5DB"></span> Unknown</span>
        <span class="ml-auto text-gray-500">Total: <strong>{{ $totalCount }}</strong> stasiun</span>
    </div>

    <div id="cs-map-loading" style="height: 70vh; border-radius: 12px; background: #e5e7eb; display: flex; align-items: center; justify-content: center;">
        <div style="text-align: center; color: #6b7280;">
            <div class="cs-spinner" style="margin-bottom: 12px;"></div>
            <div>Memuat {{ $totalCount }} stasiun...</div>
        </div>
    </div>

    <div id="cs-map" style="height: 70vh; border-radius: 12px; overflow: hidden; z-index: 0; position: relative; display: none;"></div>

    {{-- Semua JS inline di slot page (bukan @push) — guaranteed ter-render --}}
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script>
    (function () {
        var leafletReady = false;
        var dataReady = false;
        var stationData = null;

        function tryRender() {
            if (!leafletReady || !dataReady) return;
            var loading = document.getElementById('cs-map-loading');
            var container = document.getElementById('cs-map');
            if (!container || container._csMap) return;
            container._csMap = true;
            container.style.display = 'block';
            if (loading) loading.style.display = 'none';

            var stations = stationData || [];
            var colors = { 'available': '#10B981', 'occupied': '#EF4444', 'offline': '#6B7280', 'unknown': '#D1D5DB' };

            var map = L.map(container).setView([-2.5, 118], 5);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors', maxZoom: 19
            }).addTo(map);

            var markers = [];
            for (var i = 0; i < stations.length; i++) {
                var s = stations[i];
                var color = colors[s.level] || '#D1D5DB';
                var m = L.circleMarker([s.lat, s.lng], {
                    radius: 6, fillColor: color, color: '#fff', weight: 1, opacity: 1, fillOpacity: 0.85
                });
                m.bindPopup(
                    '<div style="min-width:200px">' +
                    '<strong>' + esc(s.nama) + '</strong><br>' +
                    '<small>' + esc(s.provinsi) + '</small>' +
                    '<hr style="margin:4px 0">' +
                    'Status: <strong style="color:' + color + '">' + (s.level || 'unknown') + '</strong><br>' +
                    'Slot: ' + s.avail + '/' + s.total + '<br>' +
                    'Type: ' + esc(s.type) + '<br>' +
                    '<a href="/admin/charging-stations/' + s.id + '" style="display:block;margin-top:4px;font-weight:600">Lihat detail &rarr;</a>' +
                    '</div>'
                );
                markers.push(m);
                m.addTo(map);
            }
            if (markers.length > 0) {
                map.fitBounds(L.featureGroup(markers).getBounds(), { padding: [20, 20] });
            }

            function esc(str) {
                if (!str) return '';
                return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
            }
        }

        function checkLeaflet() {
            if (typeof L !== 'undefined') { leafletReady = true; tryRender(); }
            else setTimeout(checkLeaflet, 200);
        }

        function fetchData() {
            fetch('/api/v1/spklu?per_page=5000')
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    stationData = (d.data || []).map(function (s) {
                        return {
                            id: s.id, nama: s.nama_lokasi,
                            lat: s.latitude, lng: s.longitude,
                            level: s.availability_level || 'unknown',
                            avail: s.available_count || 0,
                            total: s.total_konektor || 0,
                            type: s.type_charge || '—',
                            provinsi: s.provinsi || '—'
                        };
                    });
                    dataReady = true;
                    tryRender();
                })
                .catch(function (err) {
                    console.error('Fetch error:', err);
                    var loading = document.getElementById('cs-map-loading');
                    if (loading) loading.innerHTML = '<div style="color:#ef4444;text-align:center">Gagal memuat data. Cek console.</div>';
                });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function () { checkLeaflet(); fetchData(); });
        } else {
            checkLeaflet(); fetchData();
        }
    })();
    </script>
</x-filament-panels::page>
