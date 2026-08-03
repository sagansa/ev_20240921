<x-filament-panels::page>
    <div class="mb-4 flex flex-wrap gap-2 text-sm">
        <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded-full inline-block" style="background:#10B981"></span> Available</span>
        <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded-full inline-block" style="background:#EF4444"></span> Occupied</span>
        <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded-full inline-block" style="background:#6B7280"></span> Offline</span>
        <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded-full inline-block" style="background:#D1D5DB"></span> Unknown</span>
        <span class="ml-auto text-gray-500">Total: <strong>{{ count($stations) }}</strong> stasiun</span>
    </div>

    <div id="cs-map" style="height: 70vh; border-radius: 12px; overflow: hidden; z-index: 0; position: relative;"></div>
</x-filament-panels::page>

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      crossorigin=""/>
<style>
    #cs-map { background: #e5e7eb; }
    .leaflet-pane, .leaflet-top, .leaflet-bottom { z-index: 1 !important; }
    .leaflet-popup-pane { z-index: 2 !important; }
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
// Data stations di-embed sebagai variabel JS (bukan di atribut HTML — hindari parse issue)
window.CS_STATIONS = @json($stations);

(function () {
    var attempts = 0;
    function init() {
        attempts++;
        var container = document.getElementById('cs-map');
        if (!container) {
            if (attempts < 30) setTimeout(init, 200);
            return;
        }
        if (typeof L === 'undefined') {
            if (attempts < 30) setTimeout(init, 200);
            return;
        }
        if (container._csMap) return; // already init
        container._csMap = true;

        var stations = window.CS_STATIONS || [];
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

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    document.addEventListener('livewire:navigated', init);
})();
</script>
@endpush
