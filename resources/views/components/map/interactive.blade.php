@props([
    'locations' => [],
    'providers' => [],
    'chargingTypes' => [],
    'locationCategories' => [],
    'class' => '',
    'mapClass' => 'h-full w-full rounded-lg',
    'controlsPosition' => 'top-right',
    'defaultLocation' => ['-6.200000', '106.816666'],
    'defaultZoom' => 13,
    'enableClustering' => true,
    'enableRouting' => true,
    'enableFavorites' => true,
    'mapType' => 'community',
    'locationConsentMessage' => 'Izinkan aplikasi mendeteksi lokasi Anda untuk menampilkan stasiun terdekat?',
])

<div
    x-data="interactiveMap({
        locations: @js($locations),
        defaultLocation: @js($defaultLocation),
        defaultZoom: @js($defaultZoom),
        enableClustering: @js($enableClustering),
        enableRouting: @js($enableRouting),
        enableFavorites: @js($enableFavorites),
        mapType: @js($mapType),
        locationConsentMessage: @js($locationConsentMessage),
    })"
    x-ref="mapContainer"
    x-bind:style="{ minHeight: containerHeight, height: containerHeight, marginTop: containerOffset }"
    {{ $attributes->merge(['class' => 'interactive-map-container relative w-full z-0 overflow-hidden ' . $class]) }}
>
    <div id="mapid" @class(['absolute inset-0', $mapClass]) style="height: 100%; width: 100%;"></div>

    <!-- Desktop Filters -->
    <div class="map-controls absolute z-[1200] bg-white rounded-lg shadow-lg p-4 flex flex-col gap-3 transition-all duration-300 hidden md:block"
         x-bind:style="{ top: controlsOffset, right: '20px' }">
        <div class="w-full map-search">
            <div class="flex items-center justify-between gap-2">
                <label for="mapSearchInput" class="text-xs font-medium tracking-wide text-gray-500 uppercase">Cari Lokasi</label>
                <button
                    type="button"
                    class="text-xs text-gray-400 transition hover:text-gray-600"
                    x-show="searchResults.length > 0 || searchQuery"
                    x-on:click="clearSearchResults(true)"
                >
                    Bersihkan
                </button>
            </div>
            <div class="map-search-input">
                <input
                    id="mapSearchInput"
                    type="text"
                    x-model="searchQuery"
                    x-on:keydown.enter.prevent="performSearch()"
                    placeholder="Masukkan alamat atau tempat"
                    class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-ev-blue-400"
                >
                <button
                    type="button"
                    class="px-3 py-2 text-sm font-medium text-white transition rounded-lg bg-ev-blue-500 hover:bg-ev-blue-600 disabled:opacity-60"
                    x-bind:disabled="searchPending"
                    x-on:click="performSearch()"
                >
                    <span x-show="!searchPending">Cari</span>
                    <span x-show="searchPending" class="flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"></path>
                        </svg>
                        Memuat
                    </span>
                </button>
            </div>
            <p class="mt-1 text-xs text-red-500" x-show="searchError" x-text="searchError"></p>
            <div class="map-search-results" x-show="searchResults.length > 0" x-cloak>
                <ul>
                    <template x-for="result in searchResults" :key="result.place_id">
                        <li x-on:click="selectSearchResult(result)">
                            <div class="result-name" x-text="result.display_name"></div>
                            <div class="result-meta" x-text="result.type ? result.type.replace('_', ' ') : 'Lokasi ditemukan'"></div>
                        </li>
                    </template>
                </ul>
            </div>
        </div>
        <select
            id="providerSelect"
            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg map-select"
            x-on:change="filterMarkers()"
        >
            <option value="">Semua Provider</option>
            @foreach ($providers as $provider)
                <option value="{{ $provider->id }}">{{ $provider->name }}</option>
            @endforeach
        </select>

        <select
            id="chargingTypeSelect"
            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg map-select"
            x-on:change="filterMarkers()"
        >
            <option value="">Semua Tipe Charging</option>
            @foreach ($chargingTypes as $type)
                <option value="{{ $type->id }}">{{ $type->name }}</option>
            @endforeach
        </select>

        @if ($mapType !== 'pln')
            <select
                id="locationCategorySelect"
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg map-select"
                x-on:change="filterMarkers()"
            >
                <option value="">Semua Kategori Lokasi</option>
                @foreach ($locationCategories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
        @endif
    </div>

    <!-- Mobile Filter Toggle Button -->
    <button
        id="mobileFilterToggle"
        class="mobile-filter-toggle absolute z-[1200] bg-white border border-gray-300 rounded-lg shadow-lg w-12 h-12 flex items-center justify-center cursor-pointer transition-all duration-300 md:hidden"
        x-bind:style="{ top: controlsOffset, right: '20px' }"
        x-on:click="toggleMobileFilters()"
    >
        <span x-text="showMobileFilters ? '✕' : '☰'" class="text-lg"></span>
    </button>

    <!-- Mobile Filters - Initially Hidden -->
    <div
        id="mobileFilters"
        class="mobile-filters absolute z-[1200] bg-white rounded-lg shadow-lg p-4 flex flex-col gap-2 transition-all duration-300 md:hidden"
        x-bind:style="{ top: mobileControlsOffset, right: '20px', width: '280px', maxWidth: 'calc(100% - 40px)' }"
        x-show="showMobileFilters"
        x-cloak
    >
        <div class="w-full map-search">
            <label for="mapSearchInputMobile" class="text-xs font-medium tracking-wide text-gray-500 uppercase">Cari Lokasi</label>
            <div class="map-search-input">
                <input
                    id="mapSearchInputMobile"
                    type="text"
                    x-model="searchQuery"
                    x-on:keydown.enter.prevent="performSearch()"
                    placeholder="Masukkan alamat atau tempat"
                    class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-ev-blue-400"
                >
                <button
                    type="button"
                    class="px-3 py-2 text-sm font-medium text-white transition rounded-lg bg-ev-blue-500 hover:bg-ev-blue-600 disabled:opacity-60"
                    x-bind:disabled="searchPending"
                    x-on:click="performSearch()"
                >
                    Cari
                </button>
            </div>
            <p class="mt-1 text-xs text-red-500" x-show="searchError" x-text="searchError"></p>
            <div class="map-search-results" x-show="searchResults.length > 0" x-cloak>
                <ul>
                    <template x-for="result in searchResults" :key="result.place_id">
                        <li x-on:click="selectSearchResult(result)">
                            <div class="result-name" x-text="result.display_name"></div>
                            <div class="result-meta" x-text="result.type ? result.type.replace('_', ' ') : 'Lokasi ditemukan'"></div>
                        </li>
                    </template>
                </ul>
            </div>
        </div>

        <select
            id="mobileProviderSelect"
            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg map-select"
            x-on:change="filterMarkers()"
        >
            <option value="">Semua Provider</option>
            @foreach ($providers as $provider)
                <option value="{{ $provider->id }}">{{ $provider->name }}</option>
            @endforeach
        </select>

        <select
            id="mobileChargingTypeSelect"
            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg map-select"
            x-on:change="filterMarkers()"
        >
            <option value="">Semua Tipe Charging</option>
            @foreach ($chargingTypes as $type)
                <option value="{{ $type->id }}">{{ $type->name }}</option>
            @endforeach
        </select>

        @if ($mapType !== 'pln')
            <select
                id="mobileLocationCategorySelect"
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg map-select"
                x-on:change="filterMarkers()"
            >
                <option value="">Semua Kategori Lokasi</option>
                @foreach ($locationCategories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
        @endif
    </div>

    <!-- Locate Me Button - Positioned bottom right -->
    <button
        id="locateMe"
        title="Temukan lokasi saya"
        class="locate-me-button absolute z-[1200] bg-white border-2 border-ev-blue-500 rounded-full w-12 h-12 flex items-center justify-center shadow-lg cursor-pointer transition-all duration-300 hover:scale-110"
        style="bottom: 30px; right: 30px;"
        x-ref="locateButton"
        x-on:click="locateUser()"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-ev-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
    </button>

    <!-- Error Message Container -->
    <div id="locationError" class="fixed z-50 px-4 py-2 text-white transform -translate-x-1/2 bg-red-500 rounded shadow-lg location-error bottom-4 left-1/2" style="display: none;">
        <span id="errorMessage"></span>
    </div>

    <script>
        // Ensure Leaflet is available before initializing
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof L === 'undefined') {
                console.error('Leaflet is not loaded');
                document.getElementById('mapid').innerHTML = '<div class="flex items-center justify-center h-full text-red-500">Error: Map library not loaded</div>';
                return;
            }
        });
    </script>

    @push('styles')
        <style>
            .interactive-map-container {
                position: relative;
                width: 100%;
                min-height: 100vh;
                margin: 0;
                padding: 0;
            }

            #mapid {
                height: 100%;
                width: 100%;
                z-index: 1;
            }

            .map-controls {
                top: 100px; /* Below the header */
                right: 20px;
                z-index: 1000;
                background-color: white;
                padding: 15px;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                display: flex;
                flex-direction: column;
                gap: 12px;
                transition: all 0.3s ease;
                max-width: 300px;
                width: 100%;
            }

            .mobile-filter-toggle {
                top: 100px; /* Below the header */
                right: 20px;
                z-index: 1000;
                width: 48px;
                height: 48px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.2rem;
                background-color: white;
                border: 2px solid #3b82f6;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                cursor: pointer;
                transition: all 0.3s ease;
            }

            .mobile-filters {
                top: 150px; /* Below the toggle button */
                right: 20px;
                z-index: 999;
                background-color: white;
                padding: 15px;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                display: flex;
                flex-direction: column;
                gap: 12px;
                transition: all 0.3s ease;
                max-width: calc(100% - 40px);
                width: 300px;
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
                box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
            }

            .map-search {
                display: flex;
                flex-direction: column;
                gap: 6px;
                width: 100%;
            }

            .map-search-input {
                display: flex;
                align-items: stretch;
                gap: 8px;
                width: 100%;
            }

            .map-search-input input {
                flex: 1 1 auto;
                min-width: 0;
            }

            .map-search-input button {
                flex-shrink: 0;
                white-space: nowrap;
            }

            .map-search-results {
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                max-height: 192px;
                overflow-y: auto;
                background-color: #ffffff;
                box-shadow: 0 8px 12px -8px rgba(15, 23, 42, 0.15);
                scrollbar-width: thin;
            }

            .map-search-results ul {
                list-style: none;
                margin: 0;
                padding: 0;
            }

            .map-search-results li {
                padding: 10px 12px;
                cursor: pointer;
                transition: background-color 0.15s ease;
            }

            .map-search-results li:hover {
                background-color: #f3f4f6;
            }

            .map-search-results .result-name {
                font-size: 0.85rem;
                font-weight: 500;
                color: #1f2937;
            }

            .map-search-results .result-meta {
                font-size: 0.75rem;
                color: #6b7280;
                margin-top: 2px;
            }

            .locate-me-button {
                position: absolute;
                bottom: 30px; /* Positioned at the bottom */
                right: 30px;  /* Positioned at the right */
                z-index: 1000;
                width: 48px;
                height: 48px;
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

            .locate-me-button:hover {
                transform: scale(1.1);
                background-color: #3b82f6;
                color: white;
            }

            .locate-me-button.locating {
                background-color: #3b82f6;
                color: white;
                animation: spin 1s linear infinite;
            }

            .custom-marker {
                position: relative;
                display: inline-flex;
                align-items: flex-start;
                justify-content: center;
                transition: transform 0.2s ease;
            }

            .custom-marker:hover {
                transform: translateY(-2px) scale(1.03);
                z-index: 1002;
            }

            .marker-pin {
                position: relative;
                width: var(--pin-width, 46px);
                height: var(--pin-height, 64px);
                display: flex;
                align-items: center;
                justify-content: center;
                filter: drop-shadow(0 6px 12px rgba(15, 23, 42, 0.18));
            }

            .marker-pin__shape {
                display: block;
                width: 100%;
                height: 100%;
            }

            .marker-pin__avatar {
                position: absolute;
                inset: calc(var(--pin-height, 64px) * 0.18) auto auto 50%;
                transform: translate(-50%, 0);
                width: var(--pin-avatar, 30px);
                height: var(--pin-avatar, 30px);
                border-radius: 9999px;
                background-color: #ffffff;
                background-size: cover;
                background-position: center;
                background-repeat: no-repeat;
                box-shadow: 0 0 0 2px #ffffff, 0 0 0 4px var(--pin-stroke, #1d4ed8);
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
            }

            .marker-pin__avatar span {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: calc(var(--pin-avatar, 30px) * 0.44);
                font-weight: 600;
                color: var(--pin-stroke, #1d4ed8);
                text-transform: uppercase;
                letter-spacing: 0.05em;
            }

            .marker-pin__avatar--initials {
                background-color: #f8fafc;
            }

            .custom-marker.map-user-location {
                width: 32px;
                height: 48px;
                display: flex;
                align-items: flex-start;
                justify-content: center;
            }

            .custom-marker.map-user-location .custom-marker-image {
                position: absolute;
                top: 4px;
                left: 50%;
                transform: translateX(-50%);
                width: 26px;
                height: 26px;
                border-radius: 50%;
                border: 3px solid #ffffff;
                box-shadow: 0 0 0 2px #3b82f6;
                background-color: #ffffff;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .custom-marker.map-user-location .custom-marker-pointer {
                position: absolute;
                bottom: 0;
                left: 50%;
                transform: translateX(-50%);
                width: 18px;
                height: 18px;
                background-color: #3b82f6;
                clip-path: polygon(50% 100%, 0 0, 100% 0);
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

            /* Leaflet-specific fixes */
            .leaflet-container {
                height: 100%;
                width: 100%;
                font: 12px/1.5 "Helvetica Neue", Arial, Helvetica, sans-serif;
            }

            .interactive-map-popup {
                max-width: 320px;
                padding: 16px;
            }

            .popup-provider {
                display: flex;
                align-items: center;
                gap: 12px;
                margin-bottom: 12px;
            }

            .popup-provider-logo {
                width: 48px;
                height: 48px;
                border-radius: 9999px;
                background-color: #ffffff;
                background-size: cover;
                background-position: center;
                border: 2px solid #e5e7eb;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: 600;
                font-size: 1rem;
                color: #1f2937;
                text-transform: uppercase;
            }

            .popup-provider-logo--initials {
                background-color: #eff6ff;
                color: #2563eb;
            }

            .popup-provider-info h3 {
                margin: 0;
                font-size: 1.05rem;
                font-weight: 600;
                color: #1f2937;
            }

            .popup-provider-name {
                margin: 2px 0 0;
                font-size: 0.85rem;
                color: #6b7280;
            }

            .popup-meta {
                list-style: none;
                margin: 12px 0;
                padding: 0;
                display: flex;
                flex-direction: column;
                gap: 6px;
            }

            .popup-meta li {
                display: flex;
                gap: 8px;
                font-size: 0.85rem;
            }

            .popup-meta .meta-label {
                font-weight: 600;
                color: #374151;
                width: 100px;
                flex-shrink: 0;
            }

            .popup-meta .meta-value {
                color: #4b5563;
            }

            .popup-section h4 {
                font-size: 0.9rem;
                font-weight: 600;
                color: #1f2937;
                margin: 16px 0 6px;
            }

            .charger-details {
                list-style: none;
                padding: 0;
                margin: 0;
                display: flex;
                flex-direction: column;
                gap: 10px;
            }

            .charger-details li {
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                padding: 10px;
                background-color: #f9fafb;
            }

            .charger-details .detail-title {
                font-weight: 600;
                font-size: 0.9rem;
                color: #1f2937;
                margin-bottom: 6px;
            }

            .charger-details .detail-headline {
                font-size: 0.85rem;
                color: #111827;
            }

            .charger-details .detail-grid {
                display: grid;
                grid-template-columns: auto 1fr;
                gap: 4px 12px;
                font-size: 0.8rem;
            }

            .charger-details .detail-grid--meta {
                margin-top: 6px;
            }

            .charger-details .detail-label {
                font-weight: 500;
                color: #374151;
            }

            .charger-details .detail-value {
                color: #4b5563;
            }

            .interactive-map-popup .maps-link {
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

            .interactive-map-popup .maps-link:hover {
                background-color: #059669;
            }

            .interactive-map-popup .usage-info {
                margin-top: 12px;
                padding: 10px;
                background-color: #eff6ff;
                border-radius: 6px;
                font-size: 0.85rem;
                color: #1d4ed8;
                border: 1px solid #bfdbfe;
            }

            .usage-count {
                font-weight: 600;
                color: #1d4ed8;
            }

            @media (max-width: 767px) {
                .interactive-map-container {
                    padding: 0;
                    overflow: hidden;
                }

                .map-controls {
                    display: none;
                }

                .mobile-filter-toggle {
                    display: flex;
                }

                #mapid {
                    height: 100%;
                }
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            function interactiveMap(config) {
                config = Object.assign({
                    locations: [],
                    defaultLocation: ['-6.200000', '106.816666'],
                    defaultZoom: 13,
                    enableClustering: true,
                    enableRouting: true,
                    enableFavorites: true,
                    mapType: 'community',
                    locationConsentMessage: 'Izinkan aplikasi mendeteksi lokasi Anda untuk menampilkan stasiun terdekat?',
                }, config || {});

                return {
                    map: null,
                    markers: [],
                    markerClusterGroup: null,
                    userMarker: null,
                    favorites: [],
                    showMobileFilters: false,
                    containerHeight: '100vh',
                    containerOffset: '0px',
                    controlsOffset: '100px',
                    mobileControlsOffset: '160px',
                    resizeListener: null,
                    mapOptions: config,
                    mapType: config.mapType,
                    locationConsentAcknowledged: false,
                    locationConsentMessage: config.locationConsentMessage,
                    autoLocationAttempted: false,
                    searchQuery: '',
                    searchResults: [],
                    searchPending: false,
                    searchError: '',
                    searchMarker: null,

                    init() {
                        this.updateLayout();
                        this.resizeListener = () => {
                            this.updateLayout();
                            if (this.map) {
                                this.map.invalidateSize();
                            }
                        };
                        window.addEventListener('resize', this.resizeListener);
                        document.addEventListener('alpine:destroy', (event) => {
                            if (event.target === this.$el && this.resizeListener) {
                                window.removeEventListener('resize', this.resizeListener);
                            }
                        });

                        // Wait for DOM to be fully loaded before initializing map
                        this.$nextTick(() => {
                            this.initMap();
                            setTimeout(() => {
                                this.updateLayout();
                                if (this.map) {
                                    this.map.invalidateSize();
                                }
                            }, 250);
                        });
                        this.loadFavorites();
                        this.$watch('searchQuery', (value) => {
                            if (!value || !value.trim()) {
                                this.clearSearchResults(false);
                            }
                        });

                        // Watch for user location events
                        window.addEventListener('userLocation', (event) => {
                            this.setUserLocation(event.detail.latitude, event.detail.longitude);
                        });

                        // Request location permission on initialization
                        this.requestLocationPermission();
                        setTimeout(() => {
                            this.autoLocateUser();
                        }, 1000);
                    },

                    toggleMobileFilters() {
                        this.showMobileFilters = !this.showMobileFilters;
                        this.$nextTick(() => {
                            if (this.map) {
                                this.map.invalidateSize();
                            }
                        });
                    },

                    async performSearch() {
                        const query = (this.searchQuery || '').trim();
                        if (!query) {
                            this.clearSearchResults(true);
                            return;
                        }

                        this.searchPending = true;
                        this.searchError = '';

                        try {
                            const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&limit=5&addressdetails=1&q=${encodeURIComponent(query)}`, {
                                headers: {
                                    'Accept': 'application/json',
                                },
                            });

                            if (!response.ok) {
                                throw new Error('Permintaan pencarian gagal. Silakan coba lagi.');
                            }

                            const results = await response.json();

                            if (Array.isArray(results) && results.length > 0) {
                                this.searchResults = results;
                                this.searchError = '';
                            } else {
                                this.searchResults = [];
                                this.searchError = 'Lokasi tidak ditemukan. Coba kata kunci lain.';
                            }
                        } catch (error) {
                            console.error('Search error', error);
                            this.searchError = error.message || 'Terjadi kesalahan saat pencarian.';
                        } finally {
                            this.searchPending = false;
                        }
                    },

                    clearSearchResults(resetQuery = false) {
                        if (resetQuery) {
                            this.searchQuery = '';
                        }
                        this.searchResults = [];
                        this.searchError = '';
                        this.searchPending = false;
                    },

                    selectSearchResult(result) {
                        if (!result) {
                            return;
                        }

                        const latitude = parseFloat(result.lat);
                        const longitude = parseFloat(result.lon);

                        if (Number.isNaN(latitude) || Number.isNaN(longitude)) {
                            this.searchError = 'Koordinat lokasi tidak valid.';
                            return;
                        }

                        const target = [latitude, longitude];

                        if (this.searchMarker) {
                            this.map.removeLayer(this.searchMarker);
                            this.searchMarker = null;
                        }

                        this.searchMarker = L.circleMarker(target, {
                            radius: 10,
                            color: '#f97316',
                            weight: 2,
                            fillColor: '#fb923c',
                            fillOpacity: 0.7,
                        }).addTo(this.map);

                        this.searchMarker.bindPopup(`<div class="text-sm font-medium text-gray-800">${this.escapeHtml(result.display_name)}</div>`).openPopup();

                        this.map.setView(target, Math.max(this.map.getZoom(), 14));
                        this.searchQuery = result.display_name;
                        this.clearSearchResults(false);
                        this.showMobileFilters = false;
                    },

                    initMap() {
                        // Verify that Leaflet is available
                        if (typeof L === 'undefined') {
                            console.error('Leaflet is not loaded');
                            return;
                        }

                        // Initialize the map with a small delay to ensure DOM is ready
                        setTimeout(() => {
                            const { defaultLocation, defaultZoom, enableClustering } = this.mapOptions;
                            this.map = L.map('mapid').setView(defaultLocation, defaultZoom);

                            // Add tile layer
                            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                maxZoom: 19,
                                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                            }).addTo(this.map);

                            // Initialize marker cluster group if enabled
                            if (enableClustering) {
                                this.markerClusterGroup = L.markerClusterGroup({
                                    spiderfyOnMaxZoom: true,
                                    showCoverageOnHover: false,
                                    zoomToBoundsOnClick: true
                                });
                                this.map.addLayer(this.markerClusterGroup);
                            }

                            // Create markers
                            this.createMarkers();

                            // Handle map events
                            this.map.on('moveend', () => this.filterMarkers());
                            this.map.on('zoomend', () => this.filterMarkers());
                            this.map.on('click', () => this.clearSearchResults(false));

                            // Trigger map resize after initialization
                            setTimeout(() => {
                                this.map.invalidateSize();
                            }, 100);
                        }, 100);
                    },

                    updateLayout() {
                        const navHeight = this.calculateHeaderOffset();
                        this.containerHeight = navHeight > 0
                            ? `calc(100vh - ${navHeight}px)`
                            : '100vh';
                        this.containerOffset = navHeight > 0
                            ? `${navHeight}px`
                            : '0px';
                        const controlsTop = (navHeight || 0) + 8;
                        this.controlsOffset = `${controlsTop}px`;
                        this.mobileControlsOffset = `${controlsTop + 48}px`;
                    },

                    calculateHeaderOffset() {
                        const nav = document.querySelector('nav');
                        return nav ? Math.round(nav.getBoundingClientRect().height) : 0;
                    },

                    createMarkers() {
                        const { locations = [], enableClustering } = this.mapOptions;

                        // Clear existing markers
                        if (this.markerClusterGroup) {
                            this.markerClusterGroup.clearLayers();
                        } else {
                            this.map.eachLayer(layer => {
                                if (layer instanceof L.Marker && !layer.options.isUserMarker) {
                                    this.map.removeLayer(layer);
                                }
                            });
                        }
                        this.markers = [];

                        // Create markers for all locations
                        locations.forEach(location => {
                            if (!location || location.status === 3) return;

                            const markerIcon = this.buildMarkerIcon(location);

                            // Create marker
                            const marker = L.marker([location.latitude, location.longitude], {
                                icon: markerIcon,
                                locationData: location
                            });

                            // Bind popup
                            marker.bindPopup(this.createPopupContent(location));

                            // Add to map
                            if (enableClustering && this.markerClusterGroup) {
                                this.markerClusterGroup.addLayer(marker);
                            } else {
                                marker.addTo(this.map);
                            }

                            // Store marker reference
                            this.markers.push(marker);
                        });
                    },

                    buildMarkerIcon(location) {
                        const markerVariant = this.mapType === 'pln' ? 'marker-pln' : 'marker-community';
                        const providerNameRaw = location.provider?.name || location.name || 'Lokasi Charger';
                        const providerName = this.escapeHtml(providerNameRaw);
                        const isPln = this.mapType === 'pln';
                        const fillColor = isPln ? '#10b981' : '#3b82f6';
                        const strokeColor = isPln ? '#047857' : '#1d4ed8';
                        const iconWidth = isPln ? 42 : 44;
                        const iconHeight = Math.round(iconWidth * 1.35);
                        const avatarSize = Math.round(iconWidth * 0.58);
                        const iconAnchor = [iconWidth / 2, iconHeight];
                        const popupAnchor = [0, -iconHeight + 12];
                        const providerLogo = this.getProviderLogo(location);
                        const markerStyle = `--pin-width:${iconWidth}px;--pin-height:${iconHeight}px;--pin-stroke:${strokeColor};--pin-avatar:${avatarSize}px;`;
                        const providerInitials = this.getInitials(providerNameRaw);
                        const hasLogo = !!providerLogo;
                        const svgMarkup = `
                            <svg class="marker-pin__shape" viewBox="0 0 32 44" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path d="M16 1C7.725 1 1 7.725 1 16c0 10.021 10.388 23.494 14.555 28.43a1.5 1.5 0 002.29 0C22.012 39.494 32 26.021 32 16 32 7.725 24.275 1 16 1Z" fill="${fillColor}" stroke="${strokeColor}" stroke-width="2" />
                            </svg>
                        `;
                        const avatarStyle = hasLogo
                            ? `style="background-image:url('${this.escapeForCssUrl(providerLogo)}');"`
                            : '';
                        const avatarMarkup = `
                            <div class="marker-pin__avatar ${hasLogo ? '' : 'marker-pin__avatar--initials'}" ${avatarStyle}>
                                <span style="display:${hasLogo ? 'none' : 'inline-flex'};">${providerInitials}</span>
                            </div>
                        `;

                        return L.divIcon({
                            className: `custom-marker ${markerVariant}`,
                            html: `
                                <div class="marker-pin" style="${markerStyle}">
                                    ${svgMarkup}
                                    ${avatarMarkup}
                                </div>
                            `,
                            iconSize: [iconWidth, iconHeight],
                            iconAnchor,
                            popupAnchor,
                        });
                    },

                    getProviderLogo(location) {
                        const provider = location?.provider;
                        const normalized = this.normalizeStoragePath(provider?.image);

                        return normalized || '/images/ev-charging.png';
                    },

                    getInitials(name) {
                        if (!name) {
                            return '';
                        }

                        return name
                            .toString()
                            .trim()
                            .split(/\s+/)
                            .filter(Boolean)
                            .slice(0, 2)
                            .map(part => part.charAt(0))
                            .join('')
                            .toUpperCase();
                    },

                    buildLocationMeta(location) {
                        const meta = [];

                        if (location.address) {
                            meta.push({ label: 'Alamat', value: location.address });
                        }

                        // if (location.city?.name) {
                        //     meta.push({ label: 'Kota', value: location.city.name });
                        // } else if (location.city_name) {
                        //     meta.push({ label: 'Kota', value: location.city_name });
                        // }

                        // if (location.province?.name) {
                        //     meta.push({ label: 'Provinsi', value: location.province.name });
                        // } else if (location.province_name) {
                        //     meta.push({ label: 'Provinsi', value: location.province_name });
                        // }

                        // if (location.location_category?.name) {
                        //     meta.push({ label: 'Kategori', value: location.location_category.name });
                        // }

                        // if (location.operator) {
                        //     meta.push({ label: 'Operator', value: location.operator });
                        // }

                        return meta;
                    },

                    renderPlnDetail(detail) {
                        if (!detail) {
                            return '';
                        }

                        const rawTypeName = detail.charging_type?.name
                            || detail.charging_type_name
                            || detail.charger_category?.name
                            || 'Tipe tidak diketahui';
                        const rawPowerName = detail.power_charger?.name
                            || detail.power_charger_name
                            || null;
                        const rawPowerValue = detail.power;
                        const powerText = (() => {
                            if (rawPowerValue !== undefined && rawPowerValue !== null && rawPowerValue !== '') {
                                const value = rawPowerValue.toString();
                                return /kW$/i.test(value.trim()) ? value : `${value} kW`;
                            }

                            return rawPowerName;
                        })();

                        const unitValue = detail.unit ?? detail.count_connector_charger ?? null;
                        const unitText = unitValue !== null && unitValue !== undefined && unitValue !== ''
                            ? `${unitValue} unit`
                            : null;

                        const headlineParts = [rawTypeName, powerText, unitText]
                            .filter(part => part !== null && part !== undefined && String(part).trim().length > 0);
                        const headline = headlineParts.length
                            ? headlineParts.map(part => this.escapeHtml(part)).join(' - ')
                            : 'Detail charger tidak tersedia';

                        const infoRows = [];
                        if (detail.count_connector_charger !== undefined && detail.count_connector_charger !== null) {
                            infoRows.push({
                                label: 'Konektor',
                                value: `${detail.count_connector_charger}`,
                            });
                        }

                        if (detail.is_active_charger !== undefined && detail.is_active_charger !== null) {
                            const isActive = detail.is_active_charger === true
                                || detail.is_active_charger === 1
                                || detail.is_active_charger === '1';
                            infoRows.push({
                                label: 'Status',
                                value: isActive ? 'Aktif' : 'Tidak Aktif',
                            });
                        }

                        if (detail.operation_date) {
                            infoRows.push({
                                label: 'Operasi',
                                value: this.formatDate(detail.operation_date),
                            });
                        }

                        if (detail.year) {
                            infoRows.push({
                                label: 'Tahun',
                                value: detail.year,
                            });
                        }

                        const rowsHtml = infoRows.length
                            ? `
                                <div class="detail-grid detail-grid--meta">
                                    ${infoRows.map(row => `
                                        <span class="detail-label">${this.escapeHtml(row.label)}</span>
                                        <span class="detail-value">${this.escapeHtml(row.value ?? '-')}</span>
                                    `).join('')}
                                </div>
                            `
                            : '';

                        return `
                            <li>
                                <div class="detail-title detail-headline">${headline}</div>
                                ${rowsHtml}
                            </li>
                        `;
                    },

                    renderCommunityDetail(charger) {
                        if (!charger) {
                            return '';
                        }

                        const typeNameRaw = charger.type_charger?.name || 'Tipe tidak diketahui';
                        const currentNameRaw = charger.current_charger?.name || 'Arus tidak diketahui';
                        const powerNameRaw = charger.power_charger?.name || 'Daya tidak diketahui';
                        const unitCount = charger.unit || 1;

                        const rows = [
                            { label: 'Tipe Charging', value: typeNameRaw },
                            { label: 'Arus', value: currentNameRaw },
                            { label: 'Daya', value: powerNameRaw },
                            { label: 'Unit', value: `${unitCount} unit` },
                        ];

                        return `
                            <li>
                                <div class="detail-title">${this.escapeHtml(typeNameRaw)}</div>
                                <div class="detail-grid">
                                    ${rows.map(row => `
                                        <span class="detail-label">${this.escapeHtml(row.label)}</span>
                                        <span class="detail-value">${this.escapeHtml(row.value ?? '-')}</span>
                                    `).join('')}
                                </div>
                            </li>
                        `;
                    },

                    normalizeStoragePath(path) {
                        if (!path) {
                            return null;
                        }

                        if (typeof path !== 'string') {
                            return null;
                        }

                        const trimmed = path.trim();
                        if (!trimmed.length) {
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

                        if (/^\/?storage\//i.test(trimmed)) {
                            return `/${trimmed.replace(/^\/+/, '')}`;
                        }

                        return `/storage/${trimmed.replace(/^\/+/, '')}`;
                    },

                    escapeHtml(value) {
                        if (value === null || value === undefined) {
                            return '';
                        }

                        return value
                            .toString()
                            .replace(/&/g, '&amp;')
                            .replace(/</g, '&lt;')
                            .replace(/>/g, '&gt;')
                            .replace(/"/g, '&quot;')
                            .replace(/'/g, '&#039;');
                    },

                    escapeForCssUrl(value) {
                        if (value === null || value === undefined) {
                            return '';
                        }

                        return value
                            .toString()
                            .replace(/\\/g, '\\\\')
                            .replace(/'/g, "\\'")
                            .replace(/\)/g, '\\)');
                    },

                    createPopupContent(location) {
                        const locationName = this.escapeHtml(location.name || 'Lokasi Tidak Diketahui');
                        const providerNameRaw = location.provider?.name || '';
                        const providerName = this.escapeHtml(providerNameRaw || 'Provider Tidak Diketahui');
                        const providerLogoUrl = this.getProviderLogo(location);
                        const providerLogoStyle = providerLogoUrl
                            ? `style="background-image:url('${this.escapeForCssUrl(providerLogoUrl)}');"`
                            : '';
                        const providerInitials = this.getInitials(providerNameRaw || locationName);
                        const locationMeta = this.buildLocationMeta(location);
                        const latitude = location.latitude;
                        const longitude = location.longitude;
                        const googleMapsUrl = (latitude && longitude)
                            ? `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(`${latitude},${longitude}`)}`
                            : null;

                        const locationMetaHtml = locationMeta.length
                            ? `
                                <ul class="popup-meta">
                                    ${locationMeta.map(item => `
                                        <li>
                                            <span class="meta-label">${this.escapeHtml(item.label)}</span>
                                            <span class="meta-value">${this.escapeHtml(item.value)}</span>
                                        </li>
                                    `).join('')}
                                </ul>
                            `
                            : '';

                        let detailsHtml = '';
                        if (this.mapType === 'pln') {
                            const details = Array.isArray(location.pln_charger_location_details)
                                ? location.pln_charger_location_details
                                : [];

                            if (details.length) {
                                detailsHtml = `
                                    <div class="popup-section">
                                        <h4>Detail Charger</h4>
                                        <ul class="charger-details">
                                            ${details.map(detail => this.renderPlnDetail(detail)).join('')}
                                        </ul>
                                    </div>
                                `;
                            }
                        } else if (Array.isArray(location.chargers) && location.chargers.length > 0) {
                            detailsHtml = `
                                <div class="popup-section">
                                    <h4>Detail Charger</h4>
                                    <ul class="charger-details">
                                        ${location.chargers.map(charger => this.renderCommunityDetail(charger)).join('')}
                                    </ul>
                                </div>
                            `;
                        }

                        const usageHtml = this.mapType !== 'pln'
                            ? `
                                <div class="usage-info">
                                    <p>Telah digunakan <span class="usage-count">${location.charges_count || 0}</span> kali</p>
                                </div>
                            `
                            : '';

                        const providerLogoHtml = providerLogoUrl
                            ? `<div class="popup-provider-logo" ${providerLogoStyle}></div>`
                            : `<div class="popup-provider-logo popup-provider-logo--initials">${providerInitials}</div>`;

                        return `
                            <div class="interactive-map-popup">
                                <div class="popup-provider">
                                    ${providerLogoHtml}
                                    <div class="popup-provider-info">
                                        <h3>${locationName}</h3>
                                        <p class="popup-provider-name">${providerName}</p>
                                    </div>
                                </div>

                                ${locationMetaHtml}
                                ${detailsHtml}
                                ${usageHtml}

                                ${googleMapsUrl ? `
                                    <a href="${googleMapsUrl}"
                                       class="maps-link"
                                       target="_blank"
                                       rel="noopener noreferrer">
                                        Buka di Google Maps
                                    </a>
                                ` : ''}
                            </div>
                        `;
                    },

                    formatDate(value) {
                        if (!value) {
                            return '';
                        }

                        const date = new Date(value);
                        if (Number.isNaN(date.getTime())) {
                            return String(value);
                        }

                        return date.toLocaleDateString('id-ID', {
                            day: '2-digit',
                            month: 'short',
                            year: 'numeric',
                        });
                    },

                    filterMarkers() {
                        // Get selected filter values
                        const selectedProvider = document.getElementById('providerSelect')?.value ||
                                                document.getElementById('mobileProviderSelect')?.value || '';
                        const selectedChargingType = document.getElementById('chargingTypeSelect')?.value ||
                                                     document.getElementById('mobileChargingTypeSelect')?.value || '';
                        const selectedLocationCategory = document.getElementById('locationCategorySelect')?.value ||
                                                         document.getElementById('mobileLocationCategorySelect')?.value || '';
                        const { enableClustering } = this.mapOptions;

                        // Filter markers based on selections
                        this.markers.forEach(marker => {
                            const location = marker.options.locationData;

                            // Check filters
                            const matchesProvider = !selectedProvider || location.provider?.id?.toString() === selectedProvider;
                            const matchesChargingType = !selectedChargingType ||
                                location.chargers?.some(charger =>
                                    charger.current_charger?.id?.toString() === selectedChargingType
                                );
                            const matchesLocationCategory = !selectedLocationCategory ||
                                location.location_category?.id?.toString() === selectedLocationCategory;

                            // Show/hide marker based on filters
                            if (matchesProvider && matchesChargingType && matchesLocationCategory) {
                                if (enableClustering && this.markerClusterGroup) {
                                    if (!this.markerClusterGroup.hasLayer(marker)) {
                                        this.markerClusterGroup.addLayer(marker);
                                    }
                                } else {
                                    if (!this.map.hasLayer(marker)) {
                                        marker.addTo(this.map);
                                    }
                                }
                            } else {
                                if (enableClustering && this.markerClusterGroup) {
                                    if (this.markerClusterGroup.hasLayer(marker)) {
                                        this.markerClusterGroup.removeLayer(marker);
                                    }
                                } else {
                                    if (this.map.hasLayer(marker)) {
                                        this.map.removeLayer(marker);
                                    }
                                }
                            }
                        });
                    },

                    setUserLocation(latitude, longitude) {
                        const userLatLng = [latitude, longitude];

                        // Remove existing user marker
                        if (this.userMarker) {
                            this.map.removeLayer(this.userMarker);
                        }

                        // Create new user marker
                        const userIcon = L.divIcon({
                            className: 'custom-marker map-user-location',
                            html: `
                                <div class="custom-marker-pointer"></div>
                                <div class="custom-marker-image">
                                    <div class="map-user-dot"></div>
                                </div>
                            `,
                            iconSize: [40, 50],
                            iconAnchor: [20, 50]
                        });

                        this.userMarker = L.marker(userLatLng, {
                            icon: userIcon,
                            isUserMarker: true
                        }).addTo(this.map);

                        // Center map on user location
                        this.map.setView(userLatLng, 15);

                        // Refresh markers
                        this.filterMarkers();
                    },

                    autoLocateUser() {
                        if (this.autoLocationAttempted) {
                            return;
                        }
                        this.autoLocationAttempted = true;

                        if (!navigator.geolocation) {
                            this.showLocationError('Geolokasi tidak didukung oleh browser Anda');
                            return;
                        }

                        const locateButton = this.$refs.locateButton;
                        locateButton?.classList.add('locating');

                        navigator.geolocation.getCurrentPosition(
                            (position) => {
                                const userLatLng = [position.coords.latitude, position.coords.longitude];
                                this.setUserLocation(userLatLng[0], userLatLng[1]);
                                locateButton?.classList.remove('locating');
                                this.locationConsentAcknowledged = true;
                            },
                            (error) => {
                                locateButton?.classList.remove('locating');
                                let message = 'Terjadi kesalahan saat mencoba mendapatkan lokasi Anda';

                                switch (error.code) {
                                    case error.PERMISSION_DENIED:
                                        message = 'Anda menolak permintaan geolokasi';
                                        this.locationConsentAcknowledged = false;
                                        break;
                                    case error.POSITION_UNAVAILABLE:
                                        message = 'Informasi lokasi tidak tersedia';
                                        break;
                                    case error.TIMEOUT:
                                        message = 'Waktu permintaan lokasi habis';
                                        break;
                                }

                                this.showLocationError(message);
                            },
                            {
                                enableHighAccuracy: true,
                                timeout: 10000,
                                maximumAge: 0
                            }
                        );
                    },

                    locateUser() {
                        const locateButton = this.$refs.locateButton;

                        if (!navigator.geolocation) {
                            this.showLocationError('Geolokasi tidak didukung oleh browser Anda');
                            return;
                        }

                        if (!this.locationConsentAcknowledged) {
                            const consentGranted = window.confirm(this.locationConsentMessage || 'Izinkan kami mengakses lokasi Anda?');
                            if (!consentGranted) {
                                return;
                            }
                            this.locationConsentAcknowledged = true;
                        }

                        locateButton?.classList.add('locating');

                        navigator.geolocation.getCurrentPosition(
                            (position) => {
                                const userLatLng = [position.coords.latitude, position.coords.longitude];
                                this.setUserLocation(userLatLng[0], userLatLng[1]);
                                locateButton?.classList.remove('locating');
                            },
                            (error) => {
                                locateButton?.classList.remove('locating');
                                let message = 'Terjadi kesalahan saat mencoba mendapatkan lokasi Anda';

                                switch (error.code) {
                                    case error.PERMISSION_DENIED:
                                        message = 'Anda menolak permintaan geolokasi';
                                        this.locationConsentAcknowledged = false;
                                        break;
                                    case error.POSITION_UNAVAILABLE:
                                        message = 'Informasi lokasi tidak tersedia';
                                        break;
                                    case error.TIMEOUT:
                                        message = 'Waktu permintaan lokasi habis';
                                        break;
                                }

                                this.showLocationError(message);
                            },
                            {
                                enableHighAccuracy: true,
                                timeout: 10000,
                                maximumAge: 0
                            }
                        );
                    },

                    requestLocationPermission() {
                        // Request location permission on initialization
                        if (navigator.permissions && navigator.permissions.query) {
                            navigator.permissions.query({name: 'geolocation'})
                                .then(permissionStatus => {
                                    if (permissionStatus.state === 'prompt') {
                                        // We'll request it when the user clicks the locate button
                                        console.log('Location permission not yet granted, will prompt when button clicked');
                                    } else if (permissionStatus.state === 'granted') {
                                        // Permission already granted, we can get location directly
                                        console.log('Location permission already granted');
                                    } else if (permissionStatus.state === 'denied') {
                                        // Permission denied, notify user
                                        console.log('Location permission denied');
                                    }
                                });
                        }
                    },

                    showLocationError(message) {
                        const errorDiv = document.getElementById('locationError');
                        const errorMessage = document.getElementById('errorMessage');

                        errorMessage.textContent = message;
                        errorDiv.style.display = 'block';

                        setTimeout(() => {
                            errorDiv.style.display = 'none';
                        }, 3000);
                    },

                    loadFavorites() {
                        // Load favorites from localStorage
                        const favorites = localStorage.getItem('ev-charger-favorites');
                        if (favorites) {
                            this.favorites = JSON.parse(favorites);
                        }
                    },

                    saveFavorites() {
                        // Save favorites to localStorage
                        localStorage.setItem('ev-charger-favorites', JSON.stringify(this.favorites));
                    },

                    toggleFavorite(locationId) {
                        // Toggle favorite status for a location
                        const index = this.favorites.indexOf(locationId);
                        if (index > -1) {
                            this.favorites.splice(index, 1);
                        } else {
                            this.favorites.push(locationId);
                        }
                        this.saveFavorites();
                    },

                    isFavorite(locationId) {
                        // Check if a location is favorited
                        return this.favorites.includes(locationId);
                    }
                };
            }
        </script>
    @endpush
</div>
