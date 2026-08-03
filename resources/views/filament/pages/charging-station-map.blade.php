<x-filament-panels::page>
    <div class="mb-4 flex flex-wrap gap-2 text-sm">
        <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded-full inline-block" style="background:#10B981"></span> Available</span>
        <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded-full inline-block" style="background:#EF4444"></span> Occupied</span>
        <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded-full inline-block" style="background:#6B7280"></span> Offline</span>
        <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded-full inline-block" style="background:#D1D5DB"></span> Unknown</span>
        <span class="ml-auto text-gray-500">Total: <strong>{{ $totalCount }}</strong> stasiun</span>
    </div>

    <div id="cs-map-loading" class="flex items-center justify-center text-gray-500" style="height: 70vh; border-radius: 12px; background: #e5e7eb;">
        <div class="text-center">
            <div class="animate-spin inline-block w-8 h-8 border-4 border-gray-300 border-t-blue-500 rounded-full mb-3"></div>
            <div>Memuat {{ $totalCount }} stasiun...</div>
        </div>
    </div>

    <div id="cs-map" style="height: 70vh; border-radius: 12px; overflow: hidden; z-index: 0; position: relative; display: none;"></div>
</x-filament-panels::page>

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
<style>
    .leaflet-pane, .leaflet-top, .leaflet-bottom { z-index: 1 !important; }
    .leaflet-popup-pane { z-index: 2 !important; }
</style>
@endpush

@push('scripts')
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
        var colors = {
            'available': '#10B981',
            'occupied': '#EF4444',
            'offline': '#6B7280',
            'unknown': '#D1D5DB'
        };

        var map = L.map(container).setView([-2.5, 118], 5);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map);

        var markers = [];
        for (var i = 0; i < stations.length; i++) {
            var s = stations[i];
            var color = colors[s.level] || '#D1D5DB';
            var m = L.circleMarker([s.lat, s.lng], {
                radius: 6,
                fillColor: color,
                color: '#fff',
                weight: 1,
                opacity: 1,
                fillOpacity: 0.85
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
            var group = L.featureGroup(markers);
            map.fitBounds(group.getBounds(), { padding: [20, 20] });
        }

        function esc(str) {
            if (!str) return '';
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }
    }

    // Poll for Leaflet load
    function checkLeaflet() {
        if (typeof L !== 'undefined') {
            leafletReady = true;
            tryRender();
        } else {
            setTimeout(checkLeaflet, 200);
        }
    }

    // Fetch data via public API (reliable, no Livewire dependency)
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
                if (loading) loading.innerHTML = '<div class="text-red-500">Gagal memuat data: ' + err.message + '</div>';
            });
    }

    // Init
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { checkLeaflet(); fetchData(); });
    } else {
        checkLeaflet();
        fetchData();
    }
    document.addEventListener('livewire:navigated', function () { checkLeaflet(); fetchData(); });
})();
</script>
@endpush
