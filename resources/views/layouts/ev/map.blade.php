@extends('layouts.main')

@section('body_class', 'map-page')
@section('content_classes', 'flex-grow pt-0 md:pt-0 overflow-hidden')

@section('title', 'Map - EV Charger')

@section('additional_head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        #mapContainer {
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

        #mapControlsToggle:hover {
            background-color: #f3f4f6;
            transform: scale(1.05);
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

        #mapControls .map-search {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        #mapControls .map-search-input {
            display: flex;
            gap: 8px;
        }

        #mapControls .map-search-input input {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        #mapControls .map-search-input input:focus {
            border-color: #3b82f6;
            outline: none;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
        }

        #mapControls .map-search-input button {
            padding: 8px 12px;
            background-color: #3b82f6;
            color: white;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            transition: background-color 0.2s ease;
        }

        #mapControls .map-search-input button:hover {
            background-color: #2563eb;
        }

        #mapControls .map-search-results {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            background-color: white;
            margin-top: 4px;
            display: none;
            box-shadow: 0 8px 12px -8px rgba(15, 23, 42, 0.15);
        }

        #mapControls .map-search-results ul {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        #mapControls .map-search-results li {
            padding: 10px 12px;
            cursor: pointer;
            transition: background-color 0.15s ease;
        }

        #mapControls .map-search-results li:hover {
            background-color: #f3f4f6;
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

        .usage-count {
            font-weight: 600;
            color: #3b82f6;
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

        #mapid .leaflet-top {
            top: 20px;
        }
    </style>
@endsection

@section('content')
    <div class="relative">
        <div id="mapContainer">
            <div id="mapid"></div>

            <div id="mapControls">
                <div class="map-search">
                    <label for="mapSearchInput" class="text-xs font-medium tracking-wide text-gray-500 uppercase">Cari Lokasi</label>
                    <div class="map-search-input">
                        <input id="mapSearchInput" type="text" placeholder="Masukkan alamat atau nama tempat">
                        <button id="mapSearchButton">Cari</button>
                    </div>
                    <div id="mapSearchResults" class="map-search-results">
                        <ul></ul>
                    </div>
                </div>

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
            let userMarker = null;

            const mapControls = document.getElementById('mapControls');
            const mapControlsToggle = document.getElementById('mapControlsToggle');
            const locateButton = document.getElementById('locateMe');

            const mobileBreakpoint = window.matchMedia('(max-width: 767px)');
            let controlsVisible = !mobileBreakpoint.matches;

            function setControlsVisibility(visible) {
                controlsVisible = visible;
                if (controlsVisible) {
                    mapControls.classList.add('show');
                } else {
                    mapControls.classList.remove('show');
                }
            }

            setControlsVisibility(controlsVisible);

            mapControlsToggle.addEventListener('click', (event) => {
                event.stopPropagation();
                setControlsVisibility(!controlsVisible);
            });

            const handleBreakpointChange = (event) => {
                setControlsVisibility(!event.matches);
            };

            if (typeof mobileBreakpoint.addEventListener === 'function') {
                mobileBreakpoint.addEventListener('change', handleBreakpointChange);
            } else if (typeof mobileBreakpoint.addListener === 'function') {
                mobileBreakpoint.addListener(handleBreakpointChange);
            }

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '© OpenStreetMap'
            }).addTo(map);

            const chargerLocations = @json($chargerLocations);

            function normalizeImagePath(path) {
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

            function createMarkers(selectedProvider = '', isRestArea = '', selectedCurrentCharger = '') {
                markers.forEach(marker => map.removeLayer(marker));
                markers = [];

                chargerLocations.forEach(location => {
                    if (!location || location.status === 3) return;

                    if (
                        (!selectedProvider || location.provider?.id?.toString() === selectedProvider) &&
                        (isRestArea === '' || location.is_rest_area?.toString() === isRestArea) &&
                        (!selectedCurrentCharger || location.chargers?.some(charger =>
                            charger.current_charger?.id?.toString() === selectedCurrentCharger
                        ))
                    ) {
                        const providerImagePath = normalizeImagePath(location.provider?.image);
                        const locationImagePath = normalizeImagePath(location.image);
                        const providerFallbacks = [
                            providerImagePath,
                            locationImagePath,
                            '/images/ev-charging.png',
                            '/images/ev-default.png',
                            '/images/placeholder.jpg'
                        ].filter(Boolean);

                        getFirstValidImage(providerFallbacks).then(providerImage => {
                            const providerName = location.provider?.name || 'Provider Tidak Diketahui';

                            const customIcon = L.divIcon({
                                className: 'custom-marker',
                                html: `
                                    <div class="custom-marker-pointer"></div>
                                    <div class="custom-marker-image">
                                        <img src="${providerImage}"
                                             alt="${providerName}"
                                             loading="lazy">
                                    </div>
                                `,
                                iconSize: [40, 50],
                                iconAnchor: [20, 50],
                                popupAnchor: [0, -50]
                            });

                            let chargersHtml = '';
                            if (location.chargers?.length > 0) {
                                const chargerItems = location.chargers
                                    .filter(charger => !selectedCurrentCharger || charger.current_charger?.id?.toString() === selectedCurrentCharger)
                                    .map(charger => `
                                        <li>
                                            ${charger.power_charger?.name || 'N/A'} -
                                            ${charger.current_charger?.name || 'N/A'} -
                                            ${charger.type_charger?.name || 'N/A'} (${charger.unit || 1} unit)
                                        </li>
                                    `)
                                    .join('');

                                if (chargerItems.length) {
                                    chargersHtml = `
                                        <ul class="charger-details">
                                            ${chargerItems}
                                        </ul>
                                    `;
                                }
                            }

                            const popupImage = locationImagePath || '/images/ev-station.png';

                            const marker = L.marker([location.latitude, location.longitude], {
                                icon: customIcon
                            }).bindPopup(`
                                <div class="popup-content">
                                    <h3>${location.name || 'Lokasi Tidak Diketahui'}</h3>
                                    <img src="${popupImage}"
                                         alt="${location.name || 'Lokasi Tidak Diketahui'}"
                                         loading="lazy">
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
                        });
                    }
                });
            }

            const providerSelect = document.getElementById('providerSelect');
            const restAreaSelect = document.getElementById('restAreaSelect');
            const currentChargerSelect = document.getElementById('currentChargerSelect');
            const searchInput = document.getElementById('mapSearchInput');
            const searchButton = document.getElementById('mapSearchButton');
            const searchResultsContainer = document.getElementById('mapSearchResults');
            const searchResultsList = searchResultsContainer.querySelector('ul');

            function clearSearchResults() {
                searchResultsList.innerHTML = '';
                searchResultsContainer.style.display = 'none';
            }

            async function performSearch(query) {
                const trimmedQuery = query.trim();
                if (!trimmedQuery) {
                    clearSearchResults();
                    return;
                }

                try {
                    const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(trimmedQuery)}`);
                    if (!response.ok) {
                        throw new Error('Gagal mengambil data pencarian');
                    }

                    const results = await response.json();
                    renderSearchResults(results);
                } catch (error) {
                    console.error('Kesalahan saat melakukan pencarian lokasi:', error);
                }
            }

            function renderSearchResults(results) {
                searchResultsList.innerHTML = '';

                if (!Array.isArray(results) || results.length === 0) {
                    const listItem = document.createElement('li');
                    listItem.textContent = 'Lokasi tidak ditemukan. Coba kata kunci lain.';
                    listItem.style.cursor = 'default';
                    searchResultsList.appendChild(listItem);
                    searchResultsContainer.style.display = 'block';
                    return;
                }

                results.slice(0, 10).forEach(result => {
                    const listItem = document.createElement('li');
                    listItem.textContent = result.display_name;
                    listItem.addEventListener('click', () => {
                        if (!result.lat || !result.lon) {
                            return;
                        }

                        const lat = parseFloat(result.lat);
                        const lon = parseFloat(result.lon);

                        if (!Number.isNaN(lat) && !Number.isNaN(lon)) {
                            map.setView([lat, lon], 16);
                        }

                        clearSearchResults();
                    });
                    searchResultsList.appendChild(listItem);
                });

                searchResultsContainer.style.display = 'block';
            }

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
                        createMarkers(
                            providerSelect.value,
                            restAreaSelect.value,
                            currentChargerSelect.value
                        );
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

            providerSelect.addEventListener('change', () => createMarkers(
                providerSelect.value,
                restAreaSelect.value,
                currentChargerSelect.value
            ));

            restAreaSelect.addEventListener('change', () => createMarkers(
                providerSelect.value,
                restAreaSelect.value,
                currentChargerSelect.value
            ));

            currentChargerSelect.addEventListener('change', () => createMarkers(
                providerSelect.value,
                restAreaSelect.value,
                currentChargerSelect.value
            ));

            searchButton.addEventListener('click', () => performSearch(searchInput.value));
            searchInput.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    performSearch(searchInput.value);
                }
            });

            createMarkers();

            setTimeout(() => {
                map.invalidateSize();
            }, 100);

            window.addEventListener('resize', () => {
                map.invalidateSize();
            });
        });
    </script>
@endpush
