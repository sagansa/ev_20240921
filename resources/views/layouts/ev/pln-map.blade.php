@extends('layouts.main')

@section('body_class', 'map-page')
@section('content_classes', 'flex-grow pt-0 md:pt-0 overflow-hidden')

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

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .map-container {
            position: relative;
            width: 100%;
            height: calc(100vh - 64px);
            margin: 64px 0 0 0;
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

        .map-controls {
            background-color: white;
            color: #1f2937;
        }

        .map-controls select,
        .map-controls option {
            background-color: white;
            color: #1f2937;
            border-color: #e5e7eb;
        }

        .map-controls select:focus {
            border-color: #3b82f6;
            outline: none;
        }

        .map-controls-toggle {
            background-color: white;
            color: #1f2937;
        }

        .map-controls-toggle:hover {
            background-color: #f3f4f6;
        }

        #mapid .leaflet-top {
            top: 20px;
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
    <div class="relative w-full h-screen bg-white">
        <x-map.container>
            <x-map.controls :providers="$providers" :charging-types="$chargingTypes" :location-categories="$locationCategories" />

            <div id="mapid" class="w-full h-full rounded-lg border-2 border-ev-blue-500"></div>

            <x-map.locate-button />
        </x-map.container>
    </div>

    <x-mobile.youtube-section :ev-youtube-video-id="$evYoutubeVideoId ?? null" />
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

            function resolveImagePath(path) {
                if (!path || typeof path !== 'string') {
                    return null;
                }

                const trimmed = path.trim();
                if (!trimmed) {
                    return null;
                }

                if (/^https?:\/\//i.test(trimmed)) {
                    return trimmed;
                }

                if (trimmed.startsWith('/')) {
                    return trimmed;
                }

                if (/^storage\//i.test(trimmed)) {
                    return `/storage/${trimmed.replace(/^storage\/+/, '')}`;
                }

                if (/^(images|img|svg|icons)\//i.test(trimmed)) {
                    return `/${trimmed.replace(/^\/+/, '')}`;
                }

                return `/storage/${trimmed.replace(/^\/+/, '')}`;
            }

            function checkImage(url) {
                return new Promise((resolve) => {
                    const img = new Image();
                    img.onload = () => resolve(url);
                    img.onerror = () => resolve(null);
                    img.src = url;
                });
            }

            async function getFirstValidImage(fallbacks) {
                for (const url of fallbacks) {
                    const validImage = await checkImage(url);
                    if (validImage) return validImage;
                }
                return '/images/no-image.png';
            }

            function createMarkers(selectedProvider = '', selectedChargingType = '', selectedLocationCategory = '') {
                markers.forEach(marker => map.removeLayer(marker));
                markers = [];

                const bounds = map.getBounds();

                chargerLocations.forEach(location => {
                    if (!location) return;

                    const latlng = L.latLng(location.latitude, location.longitude);
                    if (!bounds.contains(latlng)) return;

                    const hasMatchingChargingType = !selectedChargingType ||
                        location.pln_charger_location_details?.some(detail =>
                            detail.charging_type_id?.toString() === selectedChargingType
                        );

                    const hasMatchingProvider = !selectedProvider ||
                        location.provider?.id?.toString() === selectedProvider;

                    const hasMatchingCategory = !selectedLocationCategory ||
                        location.location_category?.id?.toString() === selectedLocationCategory;

                    if (hasMatchingProvider && hasMatchingChargingType && hasMatchingCategory) {
                        const providerImagePath = resolveImagePath(location.provider?.image);
                        const locationImagePath = resolveImagePath(location.image);
                        const providerFallbacks = [
                            providerImagePath,
                            locationImagePath,
                            '/images/ev-charging.png',
                            '/images/ev-default.png',
                            '/images/placeholder.jpg'
                        ].filter(Boolean);

                        const providerName = location.provider?.name || 'Provider Tidak Diketahui';

                        getFirstValidImage(providerFallbacks).then(providerImage => {
                            const customIcon = L.divIcon({
                                className: 'map-marker',
                                html: `
                                    <div class="map-marker-pointer"></div>
                                    <div class="map-marker-image">
                                        <img src="${providerImage}"
                                             alt="${providerName}"
                                             loading="lazy">
                                    </div>
                                `,
                                iconSize: [40, 50],
                                iconAnchor: [20, 50],
                                popupAnchor: [0, -50]
                            });

                            const marker = L.marker([location.latitude, location.longitude], {
                                icon: customIcon
                            }).bindPopup(`
                                <div class="p-4 max-w-xs bg-white rounded-lg shadow-md">
                                    <h3 class="mb-2 text-lg font-bold text-blue-800">
                                        ${location.name || 'Lokasi Tidak Diketahui'}
                                    </h3>
                                    <p class="text-sm text-gray-500 mb-1">
                                        ${location.address || 'Alamat tidak tersedia'}
                                    </p>
                                    <p class="text-sm text-gray-500 mb-2">
                                        Provider: ${providerName}
                                    </p>
                                    <p class="text-sm text-gray-500 mb-2">
                                        Kategori Lokasi: ${location.location_category?.name || 'Tidak Diketahui'}
                                    </p>
                                    ${location.pln_charger_location_details?.length ? `
                                        <div class="mt-3">
                                            <h4 class="font-semibold text-blue-700 mb-2">Detail Charger:</h4>
                                            <ul class="space-y-2">
                                                ${location.pln_charger_location_details.map(detail => `
                                                    <li class="text-gray-600">
                                                        <div class="flex flex-col space-y-1">
                                                            <span><strong>Merk:</strong> ${detail.merk_charger?.name || 'Tidak Diketahui'}</span>
                                                            <span><strong>Daya:</strong> ${detail.power || '0'}</span>
                                                            <span><strong>Jumlah Konektor:</strong> ${detail.count_connector_charger || '0'}</span>
                                                        </div>
                                                    </li>
                                                `).join('')}
                                            </ul>
                                        </div>
                                    ` : '<p class="mt-2 text-gray-500">Tidak ada detail charger</p>'}
                                    <a href="https://www.google.com/maps/search/?api=1&query=${location.latitude},${location.longitude}"
                                        class="inline-block px-4 py-2 mt-4 text-sm text-white bg-green-500 rounded transition duration-300 hover:bg-green-600"
                                        target="_blank" rel="noopener noreferrer">
                                        Buka di Google Maps
                                    </a>
                                </div>
                            `);

                            markers.push(marker);
                            marker.addTo(map);
                        });
                    }
                });
            }

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

            map.on('moveend', updateMarkers);
            map.on('zoomend', updateMarkers);

            window.addEventListener('userLocation', function(event) {
                const userLatLng = [event.detail.latitude, event.detail.longitude];

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
                updateMarkers();
            });

            createMarkers();

            setTimeout(() => {
                map.invalidateSize();
            }, 100);
        });
    </script>
@endpush
