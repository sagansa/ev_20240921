@props([
    'class' => '',
    'mapClass' => 'h-full w-full rounded-lg',
    'controlsPosition' => 'top-right',
])

<div 
    x-data="{
        init() {
            // Initialize map container
            this.$nextTick(() => {
                this.initializeMap();
            });
        },
        initializeMap() {
            // This will be overridden by specific map implementations
        }
    }"
    {{ $attributes->merge(['class' => 'map-container relative w-full h-screen ' . $class]) }}
>
    <div id="mapid" @class(['absolute inset-0', $mapClass])></div>

    <div class="map-controls absolute z-10 bg-white dark:bg-gray-800 rounded-lg shadow-lg p-4 flex flex-col gap-3 transition-all duration-300">
        {{ $slot }}
    </div>

    <button 
        id="locateMe" 
        title="Temukan lokasi saya"
        class="map-locate-button absolute z-10 bg-white dark:bg-gray-800 border-2 border-ev-blue-500 dark:border-ev-blue-400 rounded-full w-10 h-10 flex items-center justify-center shadow-lg cursor-pointer transition-all duration-300 hover:scale-110"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-ev-blue-500 dark:text-ev-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
    </button>

    @push('styles')
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
                border: 2px solid #3b82f6;
                border-radius: 8px;
            }

            .map-controls {
                @if($controlsPosition === 'top-right')
                    top: 80px;
                    right: 30px;
                @elseif($controlsPosition === 'top-left')
                    top: 80px;
                    left: 30px;
                @elseif($controlsPosition === 'bottom-right')
                    bottom: 80px;
                    right: 30px;
                @elseif($controlsPosition === 'bottom-left')
                    bottom: 80px;
                    left: 30px;
                @endif
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

            .map-controls-toggle {
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

            .map-controls-toggle:hover {
                background-color: #f3f4f6;
                transform: scale(1.05);
            }

            .map-locate-button {
                position: absolute;
                bottom: 30px;
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

            .dark .leaflet-tile {
                filter: invert(1) hue-rotate(180deg) brightness(0.9) contrast(0.9);
            }

            .dark .leaflet-container {
                background: #242424;
            }

            .dark .leaflet-popup-content-wrapper {
                background-color: #1f2937;
                color: white;
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
    @endpush

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Common map functionality
                function showLocationError(message) {
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'location-error';
                    errorDiv.textContent = message;
                    document.body.appendChild(errorDiv);
                    setTimeout(() => errorDiv.remove(), 3000);
                }

                // Handle user location
                const locateButton = document.getElementById('locateMe');
                
                if (locateButton) {
                    locateButton.addEventListener('click', function() {
                        if (!navigator.geolocation) {
                            showLocationError('Geolokasi tidak didukung oleh browser Anda');
                            return;
                        }

                        locateButton.classList.add('locating');

                        navigator.geolocation.getCurrentPosition(
                            function(position) {
                                const userLatLng = [position.coords.latitude, position.coords.longitude];
                                
                                window.dispatchEvent(new CustomEvent('userLocation', {
                                    detail: {
                                        latitude: position.coords.latitude,
                                        longitude: position.coords.longitude
                                    }
                                }));
                                
                                locateButton.classList.remove('locating');
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
                                        message = 'Waktu permintaan lokasi habis';
                                        break;
                                }

                                showLocationError(message);
                            }
                        );
                    });
                }

                // Toggle controls on mobile
                const mapControlsToggle = document.getElementById('mapControlsToggle');
                const mapControls = document.querySelector('.map-controls');
                
                if (mapControlsToggle && mapControls) {
                    mapControlsToggle.addEventListener('click', function() {
                        mapControls.classList.toggle('show');
                    });
                }
            });
        </script>
    @endpush
</div>