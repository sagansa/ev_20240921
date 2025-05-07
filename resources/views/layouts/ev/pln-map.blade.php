@extends('layouts.main')

@section('title', 'Lokasi Charging Station EV di Indonesia | Peta Stasiun Pengisian Kendaraan Listrik')

@section('additional_head')
    <meta name="description"
        content="Temukan lokasi charging station kendaraan listrik terdekat di Indonesia. Peta interaktif stasiun pengisian EV dengan informasi real-time lokasi anda, tipe charging, kapasitas, dan provider.">
    <meta name="keywords"
        content="charging station EV, SPKLU, stasiun pengisian kendaraan listrik, peta charger EV, lokasi charging station Indonesia, EV charger map">

    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Dataset",
        "name": "Peta Lokasi Charging Station EV di Indonesia",
        "description": "Database lengkap lokasi charging station kendaraan listrik di Indonesia dengan informasi detail provider, tipe charger, dan kategori lokasi.",
        "keywords": ["charging station", "SPKLU", "EV charger", "stasiun pengisian listrik", "kendaraan listrik", "electric vehicle"],
        "url": "{{ url()->current() }}",
        "provider": {
            "@type": "PT Sagansa Engineering Indonesia",
            "name": "EV Charging Network Indonesia"
        }
    }
    </script>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />

    <style>
        .map-container {
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
        }

        .map-controls {
            position: absolute;
            top: 80px;
            right: 30px;
            z-index: 1000;
            background-color: white;
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            opacity: 1;
            transform: translateY(0);
        }

        .map-controls-toggle {
            display: none;
            position: absolute;
            top: 130px;
            right: 20px;
            z-index: 1001;
            background-color: white;
            padding: 10px;
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .map-controls-toggle:hover {
            background-color: #f3f4f6;
            transform: scale(1.05);
        }

        .map-locate-button {
            position: absolute;
            bottom: 120px;
            right: 30px;
            z-index: 1000;
            width: 40px;
            height: 40px;
            background-color: white;
            border: 2px solid #3b82f6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .map-locate-button:hover {
            transform: scale(1.1);
            background-color: #3b82f6;
            color: white;
        }

        .map-locate-button.locating {
            background-color: #3b82f6;
            color: white;
            animation: spin 1s linear infinite;
        }

        .map-marker {
            position: relative;
            width: 40px;
            height: 50px;
            transition: transform 0.3s ease;
        }

        .map-marker:hover {
            transform: scale(1.1);
            z-index: 1002;
        }

        .map-marker-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid #3b82f6;
            overflow: hidden;
            z-index: 2;
            background-color: white;
        }

        .map-marker-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .map-marker-pointer {
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

        .map-user-location .map-marker-image {
            border-color: #3b82f6;
            background-color: #ffffff;
        }

        .map-user-dot {
            width: 16px;
            height: 16px;
            background-color: #3b82f6;
            border-radius: 50%;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                transform: translate(-50%, -50%) scale(1);
                opacity: 1;
            }

            70% {
                transform: translate(-50%, -50%) scale(2);
                opacity: 0;
            }

            100% {
                transform: translate(-50%, -50%) scale(1);
                opacity: 0;
            }
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        .location-error {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #ef4444;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            animation: slideUp 0.3s ease-out;
        }

        @keyframes slideUp {
            from {
                transform: translate(-50%, 100%);
                opacity: 0;
            }

            to {
                transform: translate(-50%, 0);
                opacity: 1;
            }
        }

        .dark .leaflet-tile {
            filter: invert(1) hue-rotate(180deg) brightness(0.9) contrast(0.9);
        }

        .dark .leaflet-container {
            background: #242424;
        }

        .dark .leaflet-popup-content-wrapper {
            background-color: #1f2937;
        }

        .dark .map-controls {
            background-color: #1f2937;
            color: white;
        }

        .dark .map-controls-toggle {
            background-color: #1f2937;
            color: white;
        }

        .dark .map-controls-toggle:hover {
            background-color: #374151;
        }

        @media (max-width: 767px) {
            .map-container {
                padding: 0;
            }

            .map-controls {
                display: flex;
                top: 70px;
                right: 20px;
                flex-direction: column;
                width: 80%;
                max-width: 300px;
                background-color: rgba(255, 255, 255, 0.95);
                opacity: 0;
                transform: translateY(-20px);
                pointer-events: none;
            }

            .dark .map-controls {
                background-color: rgba(31, 41, 55, 0.95);
            }

            .map-controls.show {
                opacity: 1;
                transform: translateY(0);
                pointer-events: auto;
            }

            .map-controls-toggle {
                display: block;
            }

            .map-locate-button {
                bottom: 60px;
            }

            #mapid {
                border-radius: 0;
            }
        }
    </style>
@endsection

@section('content')
    <div class="relative w-full h-screen bg-white dark:bg-gray-900">
        <div class="map-container">
            <div class="map-controls">
                <select id="providerSelect"
                    class="px-3 py-2 w-full text-sm rounded-lg border dark:bg-gray-700 dark:border-gray-600">
                    <option value="">Semua Provider</option>
                    @foreach ($providers as $provider)
                        <option value="{{ $provider->id }}">{{ $provider->name }}</option>
                    @endforeach
                </select>

                <select id="chargingTypeSelect"
                    class="px-3 py-2 w-full text-sm rounded-lg border dark:bg-gray-700 dark:border-gray-600">
                    <option value="">Semua Tipe Charging</option>
                    @foreach ($chargingTypes as $type)
                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                    @endforeach
                </select>

                <select id="locationCategorySelect"
                    class="px-3 py-2 w-full text-sm rounded-lg border dark:bg-gray-700 dark:border-gray-600">
                    <option value="">Semua Kategori Lokasi</option>
                    @foreach ($locationCategories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <button id="mapControlsToggle" class="map-controls-toggle">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                </svg>
            </button>

            <div id="mapid" class="w-full h-full rounded-lg border-2 border-ev-blue-500 dark:border-ev-blue-400"></div>

            <button id="locateMe" class="map-locate-button">
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

    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const defaultView = [-6.200000, 106.816666];
                const map = L.map('mapid').setView(defaultView, 13);
                let markers = [];
                let userMarker;

                // Toggle mobile controls
                const mapControlsToggle = document.getElementById('mapControlsToggle');
                const mapControls = document.querySelector('.map-controls');

                if (mapControlsToggle && mapControls) {
                    mapControlsToggle.addEventListener('click', () => {
                        mapControls.classList.toggle('show');
                    });
                }

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '© OpenStreetMap'
                }).addTo(map);

                const chargerLocations = @json($plnLocations);

                function createMarkers(selectedProvider = '', selectedChargingType = '', selectedLocationCategory =
                    '') {
                    markers.forEach(marker => map.removeLayer(marker));
                    markers = [];

                    chargerLocations.forEach(location => {
                        if (!location) return;

                        const hasMatchingChargingType = !selectedChargingType ||
                            location.pln_charger_location_details?.some(detail =>
                                detail.charging_type_id?.toString() === selectedChargingType
                            );

                        const hasMatchingProvider = !selectedProvider ||
                            location.provider?.id?.toString() === selectedProvider;

                        const hasMatchingCategory = !selectedLocationCategory ||
                            location.location_category?.id?.toString() === selectedLocationCategory;

                        if (hasMatchingProvider && hasMatchingChargingType && hasMatchingCategory) {
                            const providerImage = location.provider?.image || '/images/placeholder.jpg';
                            const providerName = location.provider?.name || 'Provider Tidak Diketahui';

                            const customIcon = L.divIcon({
                                className: 'map-marker',
                                html: `
                                    <div class="map-marker-pointer"></div>
                                    <div class="map-marker-image">
                                        <img src="/storage/${providerImage}"
                                             alt="${providerName}"
                                             onerror="this.onerror=null; this.src='/images/placeholder.jpg';">
                                    </div>
                                `,
                                iconSize: [40, 50],
                                iconAnchor: [20, 50],
                                popupAnchor: [0, -50]
                            });

                            const marker = L.marker([location.latitude, location.longitude], {
                                icon: customIcon
                            }).bindPopup(`
                                <div class="p-4 max-w-xs bg-white rounded-lg shadow-md dark:bg-gray-800">
                                    <h3 class="mb-2 text-lg font-bold text-blue-800 dark:text-blue-300">
                                        ${location.name || 'Lokasi Tidak Diketahui'}
                                    </h3>

                                    ${location.image ? `
                                                                        <img src="/storage/${location.image}"
                                                                            alt="${location.name || 'Lokasi Tidak Diketahui'}"
                                                                            class="object-cover mb-2 w-full h-32 rounded"
                                                                            onerror="this.onerror=null; this.src='/images/placeholder.jpg';">
                                                                    ` : ''}

                                    <a href="https://www.google.com/maps/search/?api=1&query=${location.latitude},${location.longitude}"
                                        class="inline-block px-4 py-2 mt-2 text-sm text-white bg-green-500 rounded transition duration-300 hover:bg-green-600 dark:bg-green-600 dark:hover:bg-green-700"
                                        target="_blank" rel="noopener noreferrer">
                                        Buka di Google Maps
                                    </a>

                                    <p class="mb-1 text-gray-600 dark:text-gray-300">
                                        Kategori Lokasi: ${location.location_category?.name || 'Tidak Diketahui'}
                                    </p>

                                    ${location.pln_charger_location_details?.length ? `
                                                                        <div class="mt-4">
                                                                            <h4 class="font-semibold text-blue-700 dark:text-blue-400">Detail Charger:</h4>
                                                                            <ul class="pl-4 list-disc">
                                                                                ${location.pln_charger_location_details.map(detail => `
                                                    <li class="text-gray-600 dark:text-gray-300">
                                                        ${detail.merk_charger?.name || 'Tidak Diketahui'} -
                                                        ${detail.power || '0'} -
                                                        (${detail.count_connector_charger || '0'} konektor)
                                                    </li>
                                                `).join('')}
                                                                            </ul>
                                                                        </div>
                                                                    ` : ''}
                                </div>
                            `);
                            markers.push(marker);
                            marker.addTo(map);
                        }
                    });
                }

                // Handle filter updates
                const providerSelect = document.getElementById('providerSelect');
                const chargingTypeSelect = document.getElementById('chargingTypeSelect');
                const locationCategorySelect = document.getElementById('locationCategorySelect');

                function updateMarkers() {
                    createMarkers(
                        providerSelect.value,
                        chargingTypeSelect.value,
                        locationCategorySelect.value
                    );
                }

                providerSelect.addEventListener('change', updateMarkers);
                chargingTypeSelect.addEventListener('change', updateMarkers);
                locationCategorySelect.addEventListener('change', updateMarkers);

                // Handle user location
                const locateButton = document.getElementById('locateMe');

                locateButton.addEventListener('click', function() {
                    if (!navigator.geolocation) {
                        showError('Geolokasi tidak didukung di browser Anda');
                        return;
                    }

                    locateButton.classList.add('locating');

                    navigator.geolocation.getCurrentPosition(
                        function(position) {
                            const userLatLng = [position.coords.latitude, position.coords.longitude];

                            if (userMarker) {
                                map.removeLayer(userMarker);
                            }

                            const userIcon = L.divIcon({
                                className: 'map-marker map-user-location',
                                html: `
                                    <div class="map-marker-image">
                                        <div class="map-user-dot"></div>
                                    </div>
                                `,
                                iconSize: [40, 40],
                                iconAnchor: [20, 20]
                            });

                            userMarker = L.marker(userLatLng, {
                                icon: userIcon
                            }).addTo(map);

                            map.setView(userLatLng, 15);
                            locateButton.classList.remove('locating');
                        },
                        function(error) {
                            locateButton.classList.remove('locating');
                            let errorMsg = 'Terjadi kesalahan saat mencoba mendapatkan lokasi Anda';

                            switch (error.code) {
                                case error.PERMISSION_DENIED:
                                    errorMsg = 'Anda menolak permintaan geolokasi';
                                    break;
                                case error.POSITION_UNAVAILABLE:
                                    errorMsg = 'Informasi lokasi tidak tersedia';
                                    break;
                                case error.TIMEOUT:
                                    errorMsg = 'Permintaan untuk mendapatkan lokasi pengguna habis waktu';
                                    break;
                            }

                            showError(errorMsg);
                        }
                    );
                });

                function showError(message) {
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'location-error';
                    errorDiv.textContent = message;
                    document.body.appendChild(errorDiv);

                    setTimeout(() => {
                        errorDiv.remove();
                    }, 3000);
                }

                // Initialize markers
                createMarkers();

                // Trigger map resize after initialization
                setTimeout(() => {
                    map.invalidateSize();
                }, 100);
            });
        </script>
    @endpush
@endsection
