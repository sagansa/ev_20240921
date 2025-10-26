<html>

<head>
</head>
<x-filament-panels::page>
    <!-- map.blade.php -->

    <div id="map" style="width: 800px; height: 600px; border: 1px solid #ccc;"></div>

    <script>
        const chargerLocation = [{
                lat: 37.7749,
                lng: -122.4194
            },
            {
                lat: 37.7858,
                lng: -122.4364
            },
            {
                lat: 37.7963,
                lng: -122.4574
            },
            // add more locations here
        ];

        const map = L.map('map').setView([37.7749, -122.4194], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a>',
            subdomains: ['a', 'b', 'c']
        }).addTo(map);

        chargerLocation.forEach((location) => {
            const marker = L.marker([location.lat, location.lng]).addTo(map);
            marker.bindPopup(`Charger Location: ${location.lat}, ${location.lng}`);
        });

        // Add some interactivity to the map
        map.on('zoomend', () => {
            console.log(`Zoom level: ${map.getZoom()}`);
        });

        map.on('moveend', () => {
            console.log(`Center: ${map.getCenter().lat}, ${map.getCenter().lng}`);
        });
    </script>
</x-filament-panels::page>

</html>
