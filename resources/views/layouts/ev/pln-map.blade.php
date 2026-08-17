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
        "@@context": "https://schema.org",
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
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --ev-bg: #f8fafc;
            --ev-surface: #ffffff;
            --ev-surface-soft: #f1f5f9;
            --ev-text: #0f172a;
            --ev-text-muted: #64748b;
            --ev-primary: #2563eb;
            --ev-primary-hover: #1d4ed8;
            --ev-accent: #14b8a6;
            --ev-accent-hover: #0f766e;
            --ev-border: #e2e8f0;
            --ev-danger: #ef4444;
        }

        body.map-page {
            background: var(--ev-bg);
        }

        #mapContainer {
            position: relative;
            width: 100%;
            height: calc(100dvh - 64px);
            height: calc(100vh - 64px);
            margin: 64px 0 0 0;
            padding: 20px;
            background: var(--ev-bg);
        }

        #mapid {
            z-index: 1;
            height: 100%;
            width: 100%;
            transition: all 0.3s ease;
            border: 1px solid var(--ev-border);
            border-radius: 8px;
            box-shadow: 0 24px 60px -32px rgba(15, 23, 42, 0.45);
        }

        #mapControls {
            position: absolute;
            top: 80px;
            right: 30px;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.96);
            padding: 16px;
            border: 1px solid rgba(148, 163, 184, 0.28);
            border-radius: 8px;
            box-shadow: 0 18px 48px -24px rgba(15, 23, 42, 0.45);
            display: flex;
            flex-direction: column;
            gap: 14px;
            transition: all 0.3s ease;
            max-width: 360px;
            width: 100%;
            backdrop-filter: blur(12px);
            transform: translateX(calc(100% + 48px));
            opacity: 0;
            pointer-events: none;
        }

        #mapControls.show {
            transform: translateX(0);
            opacity: 1;
            pointer-events: auto;
        }

        .map-controls-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--ev-border);
        }

        .map-controls-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--ev-text);
            line-height: 1.25;
        }

        .map-controls-count {
            margin-top: 3px;
            font-size: 12px;
            color: var(--ev-text-muted);
        }

        .map-reset-button {
            flex: 0 0 auto;
            padding: 7px 10px;
            border: 1px solid var(--ev-border);
            border-radius: 6px;
            color: #334155;
            font-size: 12px;
            font-weight: 600;
            background: var(--ev-surface-soft);
            transition: all 0.2s ease;
        }

        .map-reset-button:hover {
            background: #eff6ff;
            border-color: #bfdbfe;
            color: var(--ev-primary-hover);
        }

        .map-filter-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .map-filter-field {
            display: flex;
            flex-direction: column;
            gap: 5px;
            min-width: 0;
        }

        .map-filter-field.full {
            grid-column: 1 / -1;
        }

        .map-filter-field label {
            font-size: 11px;
            font-weight: 700;
            color: var(--ev-text-muted);
            letter-spacing: 0;
            text-transform: uppercase;
        }

        #mapControlsToggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            position: absolute;
            top: 30px;
            right: 30px;
            z-index: 1001;
            background-color: var(--ev-surface);
            min-height: 44px;
            padding: 10px 14px;
            border: 1px solid rgba(148, 163, 184, 0.34);
            border-radius: 999px;
            box-shadow: 0 18px 36px -20px rgba(15, 23, 42, 0.45);
            cursor: pointer;
            color: var(--ev-text);
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0;
        }

        #mapControlsToggle:hover {
            background-color: var(--ev-surface-soft);
            transform: scale(1.05);
        }

        .map-select {
            width: 100%;
            min-height: 39px;
            padding: 8px 32px 8px 11px;
            border: 1px solid var(--ev-border);
            border-radius: 6px;
            font-size: 14px;
            color: #334155;
            background-color: var(--ev-surface);
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
            transition: all 0.2s;
        }

        .map-select:focus {
            outline: none;
            border-color: var(--ev-primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.16);
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
            border: 1px solid var(--ev-border);
            border-radius: 6px;
            font-size: 14px;
            color: #334155;
            transition: all 0.2s ease;
        }

        #mapControls .map-search-input input:focus {
            border-color: var(--ev-primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.16);
        }

        #mapControls .map-search-input button {
            padding: 8px 12px;
            background-color: var(--ev-primary);
            color: white;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            transition: background-color 0.2s ease;
        }

        #mapControls .map-search-input button:hover {
            background-color: var(--ev-primary-hover);
        }

        #mapControls .map-search-results {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid var(--ev-border);
            border-radius: 6px;
            background-color: var(--ev-surface);
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
            color: #334155;
        }

        #mapControls .map-search-results li:hover {
            background-color: var(--ev-surface-soft);
        }

        #locateMe {
            position: absolute;
            bottom: 110px;
            right: 30px;
            z-index: 1050;
            background-color: var(--ev-surface);
            border: none;
            border-radius: 50%;
            width: 44px;
            height: 44px;
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.25);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            color: var(--ev-primary);
            -webkit-appearance: none;
            -webkit-tap-highlight-color: transparent;
            touch-action: manipulation;
            pointer-events: auto;
        }

        #locateMe:hover {
            transform: scale(1.1);
            background-color: #eff6ff;
        }

        #locateMe.locating {
            animation: spin 1s linear infinite;
        }

        /* AdMob Floating Bottom Banner */
        .map-ad-bottom-container {
            position: absolute;
            bottom: calc(env(safe-area-inset-bottom, 0px) + 12px);
            left: 50%;
            transform: translateX(-50%);
            z-index: 1040;
            max-width: 728px;
            width: calc(100% - 32px);
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: 12px;
            padding: 4px 12px 8px 12px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(226, 232, 240, 0.8);
            text-align: center;
            transition: all 0.3s ease;
        }

        .dark .map-ad-bottom-container {
            background: rgba(15, 23, 42, 0.95);
            border-color: rgba(51, 65, 85, 0.8);
        }

        .map-ad-close-btn {
            position: absolute;
            top: -8px;
            right: -8px;
            width: 22px;
            height: 22px;
            background: #ef4444;
            color: white;
            border-radius: 50%;
            font-size: 13px;
            line-height: 1;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: 2px solid #ffffff;
            box-shadow: 0 2px 6px rgba(0,0,0,0.2);
            z-index: 1041;
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
            border: 2px solid var(--ev-primary);
            overflow: hidden;
            background-color: var(--ev-surface);
            z-index: 2;
            box-shadow: 0 10px 18px -10px rgba(15, 23, 42, 0.6);
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
            background-color: var(--ev-primary);
            clip-path: polygon(50% 100%, 0 0, 100% 0);
            z-index: 1;
        }

        .popup-content {
            padding: 16px;
            max-width: 320px;
            color: var(--ev-text);
        }

        .popup-content h3 {
            font-size: 16px;
            font-weight: 600;
            color: var(--ev-text);
            margin-bottom: 8px;
        }

        .popup-content p {
            font-size: 14px;
            color: #475569;
            margin-bottom: 6px;
        }

        .popup-content .charger-details {
            margin-top: 12px;
            padding-left: 0;
            list-style: none;
        }

        .popup-content .charger-details li {
            font-size: 14px;
            color: #334155;
            margin-bottom: 6px;
            padding: 8px 10px;
            border-radius: 6px;
            background: var(--ev-surface-soft);
            border: 1px solid var(--ev-border);
        }

        .popup-content .maps-link {
            display: inline-block;
            margin-top: 12px;
            padding: 8px 16px;
            background-color: var(--ev-accent);
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            transition: background-color 0.2s;
        }

        .popup-content .maps-link:hover {
            background-color: var(--ev-accent-hover);
        }

        .marker-cluster-small {
            background-color: rgba(20, 184, 166, 0.18);
        }

        .marker-cluster-small div {
            background-color: var(--ev-accent);
            color: white;
            font-weight: 700;
        }

        .marker-cluster-medium {
            background-color: rgba(245, 158, 11, 0.22);
        }

        .marker-cluster-medium div {
            background-color: #f59e0b;
            color: white;
            font-weight: 700;
        }

        .marker-cluster-large {
            background-color: rgba(239, 68, 68, 0.22);
        }

        .marker-cluster-large div {
            background-color: var(--ev-danger);
            color: white;
            font-weight: 700;
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
                background: rgba(255, 255, 255, 0.96);
                transform: translateY(120%);
                opacity: 0;
                pointer-events: none;
            }

            #mapControls.show {
                transform: translateY(0);
                opacity: 1;
                pointer-events: auto;
            }

            .map-filter-grid {
                grid-template-columns: 1fr;
            }

            #mapControlsToggle {
                top: 20px;
                right: 20px;
                width: 44px;
                padding: 10px;
                border-radius: 50%;
            }

            #mapControlsToggle span {
                display: none;
            }

            #mapid {
                border-radius: 0;
                border: none;
            }

            #locateMe {
                bottom: calc(env(safe-area-inset-bottom, 12px) + 100px);
                right: 16px;
                z-index: 1050;
            }

            .map-ad-bottom-container {
                bottom: calc(env(safe-area-inset-bottom, 8px) + 12px);
                max-width: calc(100% - 24px);
                padding: 4px 8px;
            }
        }

        .dark #mapControls {
            background: rgba(15, 23, 42, 0.94);
            border-color: rgba(51, 65, 85, 0.9);
            color: white;
        }

        .dark .map-controls-header {
            border-bottom-color: #334155;
        }

        .dark .map-controls-title {
            color: #f8fafc;
        }

        .dark .map-controls-count,
        .dark .map-filter-field label {
            color: #94a3b8;
        }

        .dark .map-select {
            background-color: #1e293b;
            border-color: #334155;
            color: #f8fafc;
        }

        .dark .map-reset-button {
            background: #1e293b;
            border-color: #334155;
            color: #cbd5e1;
        }

        .dark #mapControls .map-search-input input,
        .dark #mapControls .map-search-results {
            background-color: #1e293b;
            border-color: #334155;
            color: #f8fafc;
        }

        .dark .popup-content {
            background-color: #0f172a;
            color: white;
        }

        .dark .popup-content h3 {
            color: #e5e7eb;
        }

        .dark .popup-content .charger-details li {
            background: #1e293b;
            border-color: #334155;
            color: #cbd5e1;
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
                <div class="map-controls-header">
                    <div>
                        <div class="map-controls-title">Filter SPKLU PLN</div>
                        <div id="mapResultCount" class="map-controls-count">Memuat lokasi aktif...</div>
                    </div>
                    <button id="resetMapFilters" type="button" class="map-reset-button">Reset</button>
                </div>

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

                <div class="map-filter-grid">
                    <div class="map-filter-field full">
                        <label for="providerSelect">Provider</label>
                        <select id="providerSelect" class="map-select">
                            <option value="">Semua Provider</option>
                            @foreach ($providers as $provider)
                                <option value="{{ $provider->id }}">{{ $provider->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="map-filter-field">
                        <label for="chargingTypeSelect">Tipe Charging</label>
                        <select id="chargingTypeSelect" class="map-select">
                            <option value="">Semua Tipe</option>
                            @foreach ($chargingTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="map-filter-field">
                        <label for="locationCategorySelect">Kategori Lokasi</label>
                        <select id="locationCategorySelect" class="map-select">
                            <option value="">Semua Lokasi</option>
                            @foreach ($locationCategories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="map-filter-field full">
                        <label for="kategoriTolSelect">Kategori Tol</label>
                        <select id="kategoriTolSelect" class="map-select">
                            <option value="">Semua Kategori Tol</option>
                            @foreach (($kategoriTols ?? collect()) as $kategoriTol)
                                <option value="{{ $kategoriTol }}">{{ $kategoriTol }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <button id="mapControlsToggle" class="hover:bg-gray-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                </svg>
                <span>Filter</span>
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
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const defaultView = [-6.200000, 106.816666];
            const map = L.map('mapid', {
                zoomControl: true,
                maxZoom: 19,
                minZoom: 3,
            }).setView(defaultView, 13);
            let markers = [];
            let userMarker = null;
            let markerRenderId = 0;
            const clusterFactory = typeof L.markerClusterGroup === 'function'
                ? () => L.markerClusterGroup({
                    showCoverageOnHover: false,
                    disableClusteringAtZoom: 15,
                    spiderfyOnMaxZoom: true,
                    maxClusterRadius: 48,
                })
                : () => L.layerGroup();

            let markerCluster = clusterFactory();
            map.addLayer(markerCluster);

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

            L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
                maxZoom: 20,
                attribution: '&copy; OpenStreetMap &copy; CARTO'
            }).addTo(map);

            const plnLocations = @json($plnLocations);

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

            function isDetailActive(detail) {
                if (!detail) {
                    return false;
                }

                if (typeof detail.is_active_charger === 'boolean') {
                    return detail.is_active_charger;
                }

                if (typeof detail.is_active_charger === 'number') {
                    return detail.is_active_charger === 1;
                }

                if (typeof detail.is_active_charger === 'string') {
                    return ['1', 'true', 'aktif', 'y', 'yes'].includes(detail.is_active_charger.toLowerCase());
                }

                return false;
            }

            function formatDetail(detail) {
                if (!detail || !isDetailActive(detail)) {
                    return '';
                }

                const rawPower = detail.power ?? detail.power_charger?.name ?? '';
                const powerValue = (() => {
                    if (rawPower === null || rawPower === undefined) {
                        return '0 kW';
                    }

                    const stringValue = rawPower.toString().trim();
                    if (!stringValue) {
                        return '0 kW';
                    }

                    return /kW$/i.test(stringValue) ? stringValue : `${stringValue} kW`;
                })();

                const connectorsRaw = detail.count_connector_charger ?? detail.unit ?? 0;
                const connectors = Number.isFinite(Number(connectorsRaw))
                    ? Number(connectorsRaw)
                    : connectorsRaw || '0';

                return `
                    <li>
                        <strong>Daya ${powerValue}</strong><br>
                        ${connectors} konektor tersedia
                    </li>
                `;
            }

            function normalizeFilterValue(value) {
                return (value ?? '').toString().trim().toLowerCase();
            }

            function createMarkers(selectedProvider = '', selectedChargingType = '', selectedLocationCategory = '', selectedKategoriTol = '') {
                markerCluster.clearLayers();
                markers = [];
                const renderId = ++markerRenderId;
                let visibleLocationCount = 0;
                const normalizedKategoriTol = normalizeFilterValue(selectedKategoriTol);

                plnLocations.forEach(location => {
                    if (!location) return;

                    const matchesProvider = !selectedProvider || location.provider?.id?.toString() === selectedProvider;
                    const matchesCategory = !selectedLocationCategory || location.location_category?.id?.toString() === selectedLocationCategory;
                    const matchesKategoriTol = !normalizedKategoriTol || normalizeFilterValue(location.kategori_tol) === normalizedKategoriTol;
                    const activeDetails = Array.isArray(location.pln_charger_location_details)
                        ? location.pln_charger_location_details.filter(isDetailActive)
                        : [];
                    const matchingDetails = activeDetails.filter(detail =>
                        !selectedChargingType || detail.charging_type_id?.toString() === selectedChargingType
                    );
                    const matchesChargingType = !selectedChargingType || matchingDetails.length > 0;

                    if (!(matchesProvider && matchesCategory && matchesKategoriTol && matchesChargingType) || matchingDetails.length === 0) {
                        return;
                    }

                    visibleLocationCount += 1;

                    const providerImagePath = normalizeImagePath(location.provider?.image);
                    const locationImagePath = normalizeImagePath(location.image);
                    const providerFallbacks = [
                        providerImagePath,
                        locationImagePath,
                        '/images/ev-charging.png',
                        '/images/ev-default.png',
                        '/images/placeholder.jpg'
                    ].filter(Boolean);

                    const providerName = location.provider?.name || 'Provider Tidak Diketahui';

                    getFirstValidImage(providerFallbacks).then(providerImage => {
                        if (renderId !== markerRenderId) {
                            return;
                        }

                        const markerIcon = L.divIcon({
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

                        const detailItems = matchingDetails.map(formatDetail).join('');

                        const popupContent = `
                            <div class="popup-content">
                                <h3>${location.name || 'Lokasi Tidak Diketahui'}</h3>
                                <p>${location.address || 'Alamat tidak tersedia'}</p>
                                <p>Provider: ${providerName}</p>
                                <p>Kategori Lokasi: ${
                                    location.location_category?.name
                                    || location.location_category_name
                                    || 'Tidak Diketahui'
                                }</p>
                                ${location.kategori_tol ? `<p>Kategori Tol: ${location.kategori_tol}</p>` : ''}
                                ${detailItems ? `<ul class="charger-details">${detailItems}</ul>` : '<p>Detail Charger belum tersedia</p>'}
                                <a href="https://www.google.com/maps/search/?api=1&query=${location.latitude},${location.longitude}"
                                   class="maps-link"
                                   target="_blank"
                                   rel="noopener noreferrer">
                                    Buka di Google Maps
                                </a>
                            </div>
                        `;

                        const marker = L.marker([location.latitude, location.longitude], {
                            icon: markerIcon
                        }).bindPopup(popupContent);

                        markers.push(marker);
                        markerCluster.addLayer(marker);
                    });
                });

                const resultCount = document.getElementById('mapResultCount');
                if (resultCount) {
                    resultCount.textContent = `${visibleLocationCount.toLocaleString('id-ID')} lokasi aktif ditampilkan`;
                }
            }

            const providerSelect = document.getElementById('providerSelect');
            const chargingTypeSelect = document.getElementById('chargingTypeSelect');
            const locationCategorySelect = document.getElementById('locationCategorySelect');
            const kategoriTolSelect = document.getElementById('kategoriTolSelect');
            const resetMapFilters = document.getElementById('resetMapFilters');
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
                                className: 'map-marker',
                                html: `
                                    <div class="map-marker-pointer" style="background-color:var(--ev-danger)"></div>
                                    <div class="map-marker-image" style="border-color:var(--ev-danger); background-color:var(--ev-danger);">
                                        <div style="width: 12px; height: 12px; background-color: white; border-radius: 50%; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);"></div>
                                    </div>
                                `,
                                iconSize: [40, 50],
                                iconAnchor: [20, 50],
                                popupAnchor: [0, -50]
                            })
                        }).addTo(map);

                        map.setView(userLatLng, 15);
                        locateButton.classList.remove('locating');
                        createMarkers(
                            providerSelect.value,
                            chargingTypeSelect.value,
                            locationCategorySelect.value,
                            kategoriTolSelect.value
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
                chargingTypeSelect.value,
                locationCategorySelect.value,
                kategoriTolSelect.value
            ));

            chargingTypeSelect.addEventListener('change', () => createMarkers(
                providerSelect.value,
                chargingTypeSelect.value,
                locationCategorySelect.value,
                kategoriTolSelect.value
            ));

            locationCategorySelect.addEventListener('change', () => createMarkers(
                providerSelect.value,
                chargingTypeSelect.value,
                locationCategorySelect.value,
                kategoriTolSelect.value
            ));

            kategoriTolSelect.addEventListener('change', () => createMarkers(
                providerSelect.value,
                chargingTypeSelect.value,
                locationCategorySelect.value,
                kategoriTolSelect.value
            ));

            resetMapFilters.addEventListener('click', () => {
                providerSelect.value = '';
                chargingTypeSelect.value = '';
                locationCategorySelect.value = '';
                kategoriTolSelect.value = '';
                searchInput.value = '';
                clearSearchResults();
                createMarkers();
            });

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
