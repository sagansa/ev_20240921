@extends('layouts.main')

@section('title', 'Map - EV Charger')

@section('additional_head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <style>
        #mapContainer {
            position: relative;
            width: 100%;
            height: calc(100vh - 64px);
            margin: 0;
            padding: 20px;
        }

        #mapid {
            z-index: 1;
            height: 100%;
            width: 100%;
            transition: all 0.3s ease;
            border: 2px solid #3b82f6;
            border-radius: 8px;
        }

        #mapControls {
            position: absolute;
            top: 80px;
            right: 30px;
            z-index: 1000;
            background-color: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            gap: 10px;
            transition: all 0.3s ease;
            max-width: 300px;
            width: 100%;
        }

        #mapControlsToggle {
            display: none;
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 1001;
            background-color: white;
            padding: 10px;
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            cursor: pointer;
        }

        .map-select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            font-size: 14px;
            color: #374151;
            background-color: white;
            transition: all 0.2s;
        }

        .map-select:focus {
            outline: none;
            border-color: #3b82f6;
            ring: 2px solid #3b82f6;
        }

        #locateMe {
            position: absolute;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
            background-color: white;
            border: none;
            border-radius: 50%;
            width: 44px;
            height: 44px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        #locateMe:hover {
            transform: scale(1.1);
            background-color: #f3f4f6;
        }

        #locateMe.locating {
            animation: spin 1s linear infinite;
        }

        .custom-marker {
            width: 40px;
            height: 50px;
            position: relative;
        }

        .custom-marker-image {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid #3b82f6;
            overflow: hidden;
            background-color: white;
            position: relative;
            z-index: 2;
        }

        .custom-marker-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .custom-marker-pointer {
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 20px;
            height: 20px;
            background-color: #3b82f6;
            clip-path: polygon(50% 100%, 0 0, 100% 0);
            z-index: 1;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        .popup-content {
            padding: 16px;
            max-width: 300px;
        }

        .popup-content img {
            width: 100%;
            height: 160px;
            object-fit: cover;
            border-radius: 6px;
            margin-bottom: 12px;
        }

        .popup-content h3 {
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 8px;
        }

        .popup-content .charger-details {
            margin-top: 12px;
            padding-left: 16px;
            list-style-type: disc;
        }

        .popup-content .charger-details li {
            font-size: 14px;
            color: #4b5563;
            margin-bottom: 4px;
        }

        .popup-content .maps-link {
            display: inline-block;
            margin-top: 12px;
            padding: 8px 16px;
            background-color: #10b981;
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            transition: background-color 0.2s;
        }

        .popup-content .maps-link:hover {
            background-color: #059669;
        }

        .popup-content .usage-info {
            margin-top: 8px;
            padding: 8px;
            background-color: #f3f4f6;
            border-radius: 6px;
            font-size: 14px;
        }

        .dark .popup-content .usage-info {
            background-color: #374151;
        }

        .usage-count {
            font-weight: 600;
            color: #3b82f6;
        }

        .dark .usage-count {
            color: #60a5fa;
        }

        @media (max-width: 767px) {
            #mapContainer {
                padding: 0;
            }

            #mapControls {
                top: auto;
                bottom: 80px;
                right: 10px;
                left: 10px;
                max-width: none;
                background-color: rgba(255, 255, 255, 0.95);
                transform: translateY(120%);
                opacity: 0;
                pointer-events: none;
            }

            #mapControls.show {
                transform: translateY(0);
                opacity: 1;
                pointer-events: auto;
            }

            #mapControlsToggle {
                display: flex;
                align-items: center;
                justify-content: center;
            }

            #mapid {
                border-radius: 0;
                border: none;
            }

            #locateMe {
                bottom: 20px;
                right: 20px;
            }
        }

        .dark #mapControls {
            background-color: #1f2937;
            color: white;
        }

        .dark .map-select {
            background-color: #374151;
            border-color: #4b5563;
            color: white;
        }

        .dark .popup-content {
            background-color: #1f2937;
            color: white;
        }

        .dark .popup-content h3 {
            color: #e5e7eb;
        }

        .dark .popup-content .charger-details li {
            color: #d1d5db;
        }
    </style>
@endsection

@section('content')
    <div class="relative">
        <div id="mapContainer">
            <div id="mapid"></div>

            <div id="mapControls">
                <select id="providerSelect" class="map-select">
                    <option value="">Semua Provider</option>
                    @foreach ($providers as $provider)
                        <option value="{{ $provider->id }}">{{ $provider->name }}</option>
                    @endforeach
                </select>

                <select id="restAreaSelect" class="map-select">
                    <option value="">Rest Area & Non-Rest Area</option>
                    <option value="1">Rest Area</option>
                    <option value="0">Non-Rest Area</option>
                </select>

                <select id="currentChargerSelect" class="map-select">
                    <option value="">Semua Tipe Arus</option>
                    @foreach ($currentChargers as $currentCharger)
                        <option value="{{ $currentCharger->id }}">{{ $currentCharger->name }}</option>
                    @endforeach
                </select>
            </div>

            <button id="mapControlsToggle" class="hover:bg-gray-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                </svg>
            </button>

            <button id="locateMe" title="Temukan lokasi saya">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </button>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const defaultView = [-6.200000, 106.816666];
            const map = L.map('mapid').setView(defaultView, 13);
            let markers = [];
            let userMarker;

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '© OpenStreetMap'
            }).addTo(map);

            const chargerLocations = @json($chargerLocations);

            function createMarkers(selectedProvider = '', isRestArea = '', selectedCurrentCharger = '') {
                markers.forEach(marker => map.removeLayer(marker));
                markers = [];

                const bounds = map.getBounds();

                chargerLocations.forEach(location => {
                    if (!location || location.status === 3) return;

                    const latlng = L.latLng(location.latitude, location.longitude);
                    if (!bounds.contains(latlng)) return;

                    if (
                        (!selectedProvider || location.provider?.id?.toString() === selectedProvider) &&
                        (isRestArea === '' || location.is_rest_area?.toString() === isRestArea) &&
                        (!selectedCurrentCharger || location.chargers?.some(charger =>
                            charger.current_charger?.id?.toString() === selectedCurrentCharger
                        ))
                    ) {
                        const providerImage = location.provider?.image ?
                            `/storage/${location.provider.image}` :
                            '/images/placeholder.jpg';
                        const providerName = location.provider?.name || 'Provider Tidak Diketahui';

                        const customIcon = L.divIcon({
                            className: 'custom-marker',
                            html: `
                                <div class="custom-marker-pointer"></div>
                                <div class="custom-marker-image">
                                    <img src="${providerImage}"
                                         alt="${providerName}"
                                         onerror="(async function(img) {
                                            const fallbacks = [
                                                '/images/ev-charging.png',
                                                '/images/ev-default.png',
                                                '/images/placeholder.jpg',
                                                '/images/no-image.png'
                                            ];
                                            for (const url of fallbacks) {
                                                try {
                                                    await fetch(url, { method: 'HEAD' });
                                                    img.src = url;
                                                    return;
                                                } catch (e) {
                                                    continue;
                                                }
                                            }
                                         })(this)"
                                         loading="lazy">
                                </div>
                            `,
                            iconSize: [40, 50],
                            iconAnchor: [20, 50],
                            popupAnchor: [0, -50]
                        });

                        let chargersHtml = '';
                        if (location.chargers?.length > 0) {
                            chargersHtml = `
                                <ul class="charger-details">
                                    ${location.chargers.map(charger => {
                                        if (!selectedCurrentCharger || charger.current_charger?.id?.toString() === selectedCurrentCharger) {
                                            return `
                                                                <li>
                                                                    ${charger.power_charger?.name || 'N/A'} -
                                                                    ${charger.current_charger?.name || 'N/A'} -
                                                                    ${charger.type_charger?.name || 'N/A'}
                                                                    (${charger.unit || 1} unit)
                                                                </li>
                                                            `;
                                        }
                                        return '';
                                    }).join('')}
                                </ul>
                            `;
                        }

                        const marker = L.marker([location.latitude, location.longitude], {
                            icon: customIcon
                        }).bindPopup(`
                            <div class="popup-content">
                                <h3>${location.name || 'Lokasi Tidak Diketahui'}</h3>

                                ${location.image ? `
                                            <img src="/storage/${location.image}"
                                                alt="${location.name || 'Lokasi Tidak Diketahui'}"
                                                onerror="(async function(img) {
                                                    const fallbacks = [
                                                        '/images/ev-station.png',
                                                        '/images/charging-station.png',
                                                        '/images/placeholder.jpg',
                                                        '/images/no-image.png'
                                                    ];
                                                    for (const url of fallbacks) {
                                                        try {
                                                            await fetch(url, { method: 'HEAD' });
                                                            img.src = url;
                                                            return;
                                                        } catch (e) {
                                                            continue;
                                                        }
                                                    }
                                                })(this)"
                                                loading="lazy">
                                        ` : `
                                            <img src="/images/ev-station.png"
                                                alt="Default EV Station"
                                                onerror="(async function(img) {
                                                    const fallbacks = [
                                                        '/images/charging-station.png',
                                                        '/images/placeholder.jpg',
                                                        '/images/no-image.png'
                                                    ];
                                                    for (const url of fallbacks) {
                                                        try {
                                                            await fetch(url, { method: 'HEAD' });
                                                            img.src = url;
                                                            return;
                                                        } catch (e) {
                                                            continue;
                                                        }
                                                    }
                                                })(this)"
                                                loading="lazy">
                                        `}

                                ${chargersHtml}

                                <div class="usage-info">
                                    <p>Telah digunakan <span class="usage-count">${location.charges_count || 0}</span> kali</p>
                                </div>

                                <a href="https://www.google.com/maps/search/?api=1&query=${location.latitude},${location.longitude}"
                                   class="maps-link"
                                   target="_blank"
                                   rel="noopener noreferrer">
                                    Buka di Google Maps
                                </a>
                            </div>
                        `);
                        markers.push(marker);
                        marker.addTo(map);
                    }
                });
            }

            // Handle filter updates
            const providerSelect = document.getElementById('providerSelect');
            const restAreaSelect = document.getElementById('restAreaSelect');
            const currentChargerSelect = document.getElementById('currentChargerSelect');

            function updateMarkers() {
                createMarkers(
                    providerSelect.value,
                    restAreaSelect.value,
                    currentChargerSelect.value
                );
            }

            providerSelect.addEventListener('change', updateMarkers);
            restAreaSelect.addEventListener('change', updateMarkers);
            currentChargerSelect.addEventListener('change', updateMarkers);

            // Event listeners untuk pergerakan peta
            map.on('moveend', updateMarkers);
            map.on('zoomend', updateMarkers);

            // Handle user location
            const locateButton = document.getElementById('locateMe');

            locateButton.addEventListener('click', function() {
                if (!navigator.geolocation) {
                    alert('Geolokasi tidak didukung oleh browser Anda');
                    return;
                }

                locateButton.classList.add('locating');

                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const userLatLng = [position.coords.latitude, position.coords.longitude];

                        if (userMarker) {
                            map.removeLayer(userMarker);
                        }

                        userMarker = L.marker(userLatLng, {
                            icon: L.divIcon({
                                className: 'custom-marker',
                                html: `
                                    <div class="custom-marker-image" style="background-color: #ef4444; border-color: #ef4444;">
                                        <div style="width: 12px; height: 12px; background-color: white; border-radius: 50%; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);"></div>
                                    </div>
                                `,
                                iconSize: [40, 40],
                                iconAnchor: [20, 20]
                            })
                        }).addTo(map);

                        map.setView(userLatLng, 15);
                        locateButton.classList.remove('locating');
                        updateMarkers();
                    },
                    function(error) {
                        locateButton.classList.remove('locating');
                        let message = 'Terjadi kesalahan saat mencoba mendapatkan lokasi Anda';

                        switch (error.code) {
                            case error.PERMISSION_DENIED:
                                message = 'Anda menolak permintaan geolokasi';
                                break;
                            case error.POSITION_UNAVAILABLE:
                                message = 'Informasi lokasi tidak tersedia';
                                break;
                            case error.TIMEOUT:
                                message = 'Permintaan untuk mendapatkan lokasi pengguna habis waktu';
                                break;
                        }

                        alert(message);
                    }
                );
            });

            // Toggle controls di mobile
            const mapControlsToggle = document.getElementById('mapControlsToggle');
            const mapControls = document.getElementById('mapControls');

            mapControlsToggle.addEventListener('click', function() {
                mapControls.classList.toggle('show');
            });

            // Initialize markers
            createMarkers();

            // Trigger map resize after initialization
            setTimeout(() => {
                map.invalidateSize();
            }, 100);

            // Resize handler
            window.addEventListener('resize', function() {
                map.invalidateSize();
            });
        });
    </script>
@endpush
