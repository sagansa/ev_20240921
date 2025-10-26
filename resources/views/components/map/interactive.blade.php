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
])

<div 
    x-data="interactiveMap({
        locations: @js($locations),
        defaultLocation: @js($defaultLocation),
        defaultZoom: @js($defaultZoom),
        enableClustering: @js($enableClustering),
        enableRouting: @js($enableRouting),
        enableFavorites: @js($enableFavorites),
    })"
    {{ $attributes->merge(['class' => 'interactive-map-container relative w-full ' . $class]) }}
    style="height: 100vh;"
>
    <div id="mapid" @class(['absolute inset-0', $mapClass]) style="height: 100%; width: 100%;"></div>

    <!-- Desktop Filters -->
    <div class="map-controls absolute z-10 bg-white rounded-lg shadow-lg p-4 flex flex-col gap-3 transition-all duration-300 hidden md:block"
         style="top: 100px; right: 20px;">
        <select 
            id="providerSelect" 
            class="map-select px-3 py-2 w-full text-sm rounded-lg border border-gray-300"
            x-on:change="filterMarkers()"
        >
            <option value="">Semua Provider</option>
            @foreach ($providers as $provider)
                <option value="{{ $provider->id }}">{{ $provider->name }}</option>
            @endforeach
        </select>

        <select 
            id="chargingTypeSelect"
            class="map-select px-3 py-2 w-full text-sm rounded-lg border border-gray-300"
            x-on:change="filterMarkers()"
        >
            <option value="">Semua Tipe Charging</option>
            @foreach ($chargingTypes as $type)
                <option value="{{ $type->id }}">{{ $type->name }}</option>
            @endforeach
        </select>

        <select 
            id="locationCategorySelect"
            class="map-select px-3 py-2 w-full text-sm rounded-lg border border-gray-300"
            x-on:change="filterMarkers()"
        >
            <option value="">Semua Kategori Lokasi</option>
            @foreach ($locationCategories as $category)
                <option value="{{ $category->id }}">{{ $category->name }}</option>
            @endforeach
        </select>
    </div>

    <!-- Mobile Filter Toggle Button -->
    <button 
        id="mobileFilterToggle" 
        class="mobile-filter-toggle absolute z-10 bg-white border border-gray-300 rounded-lg shadow-lg w-12 h-12 flex items-center justify-center cursor-pointer transition-all duration-300 md:hidden"
        style="top: 100px; right: 20px;"
        x-on:click="toggleMobileFilters()"
    >
        <span x-text="showMobileFilters ? '✕' : '☰'" class="text-lg"></span>
    </button>

    <!-- Mobile Filters - Initially Hidden -->
    <div 
        id="mobileFilters" 
        class="mobile-filters absolute z-10 bg-white rounded-lg shadow-lg p-4 flex flex-col gap-3 transition-all duration-300 md:hidden"
        style="top: 150px; right: 20px; width: 280px; max-width: calc(100% - 40px); display: none;"
        x-show="showMobileFilters"
        x-cloak
    >
        <select 
            id="mobileProviderSelect" 
            class="map-select px-3 py-2 w-full text-sm rounded-lg border border-gray-300"
            x-on:change="filterMarkers()"
        >
            <option value="">Semua Provider</option>
            @foreach ($providers as $provider)
                <option value="{{ $provider->id }}">{{ $provider->name }}</option>
            @endforeach
        </select>

        <select 
            id="mobileChargingTypeSelect"
            class="map-select px-3 py-2 w-full text-sm rounded-lg border border-gray-300"
            x-on:change="filterMarkers()"
        >
            <option value="">Semua Tipe Charging</option>
            @foreach ($chargingTypes as $type)
                <option value="{{ $type->id }}">{{ $type->name }}</option>
            @endforeach
        </select>

        <select 
            id="mobileLocationCategorySelect"
            class="map-select px-3 py-2 w-full text-sm rounded-lg border border-gray-300"
            x-on:change="filterMarkers()"
        >
            <option value="">Semua Kategori Lokasi</option>
            @foreach ($locationCategories as $category)
                <option value="{{ $category->id }}">{{ $category->name }}</option>
            @endforeach
        </select>
    </div>

    <!-- Locate Me Button - Positioned bottom right -->
    <button 
        id="locateMe" 
        title="Temukan lokasi saya"
        class="locate-me-button absolute z-10 bg-white border-2 border-ev-blue-500 rounded-full w-12 h-12 flex items-center justify-center shadow-lg cursor-pointer transition-all duration-300 hover:scale-110"
        style="bottom: 30px; right: 30px;"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-ev-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
    </button>

    <!-- Error Message Container -->
    <div id="locationError" class="location-error fixed bottom-4 left-1/2 transform -translate-x-1/2 bg-red-500 text-white px-4 py-2 rounded shadow-lg z-50" style="display: none;">
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
                height: 100vh; /* Full viewport height */
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
                gap: 10px;
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
                gap: 10px;
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
                width: 40px;
                height: 50px;
                position: relative;
                transition: transform 0.3s ease;
            }

            .custom-marker:hover {
                transform: scale(1.1);
                z-index: 1002;
            }

            .custom-marker-image {
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

            .map-user-location .custom-marker-image {
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

            /* Leaflet-specific fixes */
            .leaflet-container {
                height: 100%;
                width: 100%;
                font: 12px/1.5 "Helvetica Neue", Arial, Helvetica, sans-serif;
            }

            .interactive-map-popup {
                max-width: 300px;
                padding: 16px;
            }

            .interactive-map-popup img {
                width: 100%;
                height: 160px;
                object-fit: cover;
                border-radius: 6px;
                margin-bottom: 12px;
            }

            .interactive-map-popup h3 {
                font-size: 16px;
                font-weight: 600;
                color: #1f2937;
                margin-bottom: 8px;
            }

            .interactive-map-popup .charger-details {
                margin-top: 12px;
                padding-left: 16px;
                list-style-type: disc;
            }

            .interactive-map-popup .charger-details li {
                font-size: 14px;
                color: #4b5563;
                margin-bottom: 4px;
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
                .interactive-map-container {
                    padding: 0;
                    height: calc(100vh - 64px); /* Adjust for header height */
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
                return {
                    map: null,
                    markers: [],
                    markerClusterGroup: null,
                    userMarker: null,
                    favorites: [],
                    showMobileFilters: false,
                    
                    init() {
                        // Wait for DOM to be fully loaded before initializing map
                        this.$nextTick(() => {
                            this.initMap();
                        });
                        this.loadFavorites();
                        
                        // Watch for user location events
                        window.addEventListener('userLocation', (event) => {
                            this.setUserLocation(event.detail.latitude, event.detail.longitude);
                        });
                        
                        // Watch for filter changes
                        document.getElementById('providerSelect')?.addEventListener('change', () => this.filterMarkers());
                        document.getElementById('chargingTypeSelect')?.addEventListener('change', () => this.filterMarkers());
                        document.getElementById('locationCategorySelect')?.addEventListener('change', () => this.filterMarkers());
                        
                        // Watch mobile filter changes
                        document.getElementById('mobileProviderSelect')?.addEventListener('change', () => this.filterMarkers());
                        document.getElementById('mobileChargingTypeSelect')?.addEventListener('change', () => this.filterMarkers());
                        document.getElementById('mobileLocationCategorySelect')?.addEventListener('change', () => this.filterMarkers());
                        
                        // Handle locate button click
                        document.getElementById('locateMe').addEventListener('click', () => this.locateUser());
                        
                        // Request location permission on initialization
                        this.requestLocationPermission();
                    },
                    
                    toggleMobileFilters() {
                        this.showMobileFilters = !this.showMobileFilters;
                        const filters = document.getElementById('mobileFilters');
                        
                        if (this.showMobileFilters) {
                            filters.style.display = 'block';
                        } else {
                            filters.style.display = 'none';
                        }
                    },
                    
                    initMap() {
                        // Verify that Leaflet is available
                        if (typeof L === 'undefined') {
                            console.error('Leaflet is not loaded');
                            return;
                        }
                        
                        // Initialize the map with a small delay to ensure DOM is ready
                        setTimeout(() => {
                            this.map = L.map('mapid').setView(config.defaultLocation, config.defaultZoom);
                            
                            // Add tile layer
                            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                maxZoom: 19,
                                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                            }).addTo(this.map);
                            
                            // Initialize marker cluster group if enabled
                            if (config.enableClustering) {
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
                            
                            // Trigger map resize after initialization
                            setTimeout(() => {
                                this.map.invalidateSize();
                            }, 100);
                        }, 100);
                    },
                    
                    createMarkers() {
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
                        config.locations.forEach(location => {
                            if (!location || location.status === 3) return;
                            
                            // Create custom icon
                            const customIcon = L.divIcon({
                                className: 'custom-marker',
                                html: `
                                    <div class="custom-marker-pointer"></div>
                                    <div class="custom-marker-image">
                                        <img src="${location.provider?.image ? `/storage/${location.provider.image}` : '/images/placeholder.jpg'}"
                                             alt="${location.provider?.name || 'Provider Tidak Diketahui'}"
                                             onerror="(function(img) {
                                                img.src = '/images/ev-charging.png';
                                             })(this)"
                                             loading="lazy">
                                    </div>
                                `,
                                iconSize: [40, 50],
                                iconAnchor: [20, 50],
                                popupAnchor: [0, -50]
                            });
                            
                            // Create marker
                            const marker = L.marker([location.latitude, location.longitude], {
                                icon: customIcon,
                                locationData: location
                            });
                            
                            // Bind popup
                            marker.bindPopup(this.createPopupContent(location));
                            
                            // Add to map
                            if (config.enableClustering) {
                                this.markerClusterGroup.addLayer(marker);
                            } else {
                                marker.addTo(this.map);
                            }
                            
                            // Store marker reference
                            this.markers.push(marker);
                        });
                    },
                    
                    createPopupContent(location) {
                        // Create popup content with location details
                        const providerImage = location.provider?.image ? 
                            `/storage/${location.provider.image}` : 
                            '/images/placeholder.jpg';
                            
                        const providerName = location.provider?.name || 'Provider Tidak Diketahui';
                        
                        let chargersHtml = '';
                        if (location.chargers?.length > 0) {
                            chargersHtml = `
                                <ul class="charger-details">
                                    ${location.chargers.map(charger => `
                                        <li>
                                            ${charger.power_charger?.name || 'N/A'} -
                                            ${charger.current_charger?.name || 'N/A'} -
                                            ${charger.type_charger?.name || 'N/A'}
                                            (${charger.unit || 1} unit)
                                        </li>
                                    `).join('')}
                                </ul>
                            `;
                        }
                        
                        return `
                            <div class="interactive-map-popup">
                                <h3>${location.name || 'Lokasi Tidak Diketahui'}</h3>
                                
                                <img src="${providerImage}"
                                     alt="${providerName}"
                                     onerror="(function(img) {
                                        img.src = '/images/ev-station.png';
                                     })(this)"
                                     loading="lazy">
                                
                                ${chargersHtml}
                                
                                <div class="usage-info">
                                    <p>Telah digunakan <span class="usage-count">${location.charges_count || 0}</span> kali</p>
                                </div>
                                
                                <a href='https://www.openstreetmap.org/?mlat=${location.latitude}&mlon=${location.longitude}#map=15/${location.latitude}/${location.longitude}'
                                   class='maps-link'
                                   target='_blank'
                                   rel='noopener noreferrer'>
                                    Buka di OpenStreetMap
                                </a>
                            </div>
                        `;
                    },
                    
                    filterMarkers() {
                        // Get selected filter values
                        const selectedProvider = document.getElementById('providerSelect')?.value || 
                                                document.getElementById('mobileProviderSelect')?.value || '';
                        const selectedChargingType = document.getElementById('chargingTypeSelect')?.value || 
                                                     document.getElementById('mobileChargingTypeSelect')?.value || '';
                        const selectedLocationCategory = document.getElementById('locationCategorySelect')?.value || 
                                                         document.getElementById('mobileLocationCategorySelect')?.value || '';
                        
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
                                if (config.enableClustering) {
                                    if (!this.markerClusterGroup.hasLayer(marker)) {
                                        this.markerClusterGroup.addLayer(marker);
                                    }
                                } else {
                                    if (!this.map.hasLayer(marker)) {
                                        marker.addTo(this.map);
                                    }
                                }
                            } else {
                                if (config.enableClustering) {
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
                    
                    locateUser() {
                        const locateButton = document.getElementById('locateMe');
                        
                        if (!navigator.geolocation) {
                            this.showLocationError('Geolokasi tidak didukung oleh browser Anda');
                            return;
                        }
                        
                        locateButton.classList.add('locating');
                        
                        navigator.geolocation.getCurrentPosition(
                            (position) => {
                                const userLatLng = [position.coords.latitude, position.coords.longitude];
                                this.setUserLocation(userLatLng[0], userLatLng[1]);
                                locateButton.classList.remove('locating');
                            },
                            (error) => {
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