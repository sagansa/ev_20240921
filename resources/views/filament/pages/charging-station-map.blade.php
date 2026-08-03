<x-filament-panels::page>
    <div class="mb-4 flex flex-wrap gap-2 text-sm">
        <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded-full" style="background:#10B981"></span> Available</span>
        <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded-full" style="background:#EF4444"></span> Occupied</span>
        <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded-full" style="background:#6B7280"></span> Offline</span>
        <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded-full" style="background:#D1D5DB"></span> Unknown</span>
        <span class="ml-auto text-gray-500">Total: <strong>{{ count($stations) }}</strong> stasiun</span>
    </div>

    <div id="charging-station-map" style="height: 70vh; border-radius: 12px; overflow: hidden; z-index: 0;"></div>
</x-filament-panels::page>

@push('scripts')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
      crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
        crossorigin=""></script>

<script>
// Init map setelah DOM ready (Livewire-compatible)
function initChargingStationMap() {
    if (typeof L === 'undefined') {
        setTimeout(initChargingStationMap, 200);
        return;
    }
    if (window.__csMapInitialized) return;
    window.__csMapInitialized = true;

    const stations = @json($stations);

    // Warna marker sesuai availability_level
    const colors = {
        'available': '#10B981',
        'occupied': '#EF4444',
        'offline': '#6B7280',
        'unknown': '#D1D5DB',
        null: '#D1D5DB',
    };

    // Inisialisasi peta — center Indonesia
    const map = L.map('charging-station-map').setView([-2.5, 118], 5);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(map);

    // Marker cluster-like: gunakan CircleMarker (ringan utk ribuan titik)
    const markers = [];
    stations.forEach(function (s) {
        const color = colors[s.level] || '#D1D5DB';
        const marker = L.circleMarker([s.lat, s.lng], {
            radius: 6,
            fillColor: color,
            color: '#fff',
            weight: 1,
            opacity: 1,
            fillOpacity: 0.8,
        });

        marker.bindPopup(
            '<div style="min-width:200px">' +
            '<strong>' + escapeHtml(s.nama) + '</strong><br>' +
            '<small>' + escapeHtml(s.provinsi) + '</small><br>' +
            '<hr style="margin:4px 0">' +
            'Status: <strong style="color:' + color + '">' + (s.level || 'unknown') + '</strong><br>' +
            'Slot: ' + s.avail + '/' + s.total + '<br>' +
            'Type: ' + escapeHtml(s.type) + '<br>' +
            '<a href="/admin/charging-stations/' + s.id + '" style="display:block;margin-top:4px;font-weight:600">Lihat detail →</a>' +
            '</div>'
        );

        markers.push(marker);
        marker.addTo(map);
    });

    // Auto-fit ke semua marker bila ada
    if (markers.length > 0) {
        const group = L.featureGroup(markers);
        map.fitBounds(group.getBounds(), { padding: [20, 20] });
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }
}

// Livewire navigation hook + fallback DOMContentLoaded
if (typeof Livewire !== 'undefined') {
    document.addEventListener('livewire:navigated', initChargingStationMap);
}
document.addEventListener('DOMContentLoaded', initChargingStationMap);
</script>
@endpush
