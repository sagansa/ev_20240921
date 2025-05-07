@props(['class' => ''])

<div {{ $attributes->merge(['class' => 'map-container ' . $class]) }}>
    {{ $slot }}
</div>

@push('styles')
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
@endpush

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Fungsi untuk menampilkan pesan error
            function showLocationError(message) {
                const errorDiv = document.createElement('div');
                errorDiv.className = 'location-error';
                errorDiv.textContent = message;
                document.body.appendChild(errorDiv);
                setTimeout(() => errorDiv.remove(), 3000);
            }

            // Fungsi untuk meminta izin lokasi
            function requestLocationPermission() {
                if (!navigator.geolocation) {
                    showLocationError('Geolokasi tidak didukung oleh browser Anda');
                    return;
                }

                navigator.permissions.query({
                        name: 'geolocation'
                    })
                    .then(function(permissionStatus) {
                        if (permissionStatus.state === 'prompt') {
                            navigator.geolocation.getCurrentPosition(
                                function(position) {
                                    // Trigger event ketika lokasi ditemukan
                                    window.dispatchEvent(new CustomEvent('userLocation', {
                                        detail: {
                                            latitude: position.coords.latitude,
                                            longitude: position.coords.longitude
                                        }
                                    }));
                                },
                                function(error) {
                                    let message = 'Terjadi kesalahan saat mencoba mendapatkan lokasi Anda';
                                    if (error.code === 1) {
                                        message =
                                            'Izin lokasi ditolak. Mohon aktifkan di pengaturan browser Anda';
                                    } else if (error.code === 2) {
                                        message = 'Lokasi tidak tersedia';
                                    } else if (error.code === 3) {
                                        message = 'Waktu permintaan lokasi habis';
                                    }
                                    showLocationError(message);
                                }, {
                                    enableHighAccuracy: true,
                                    timeout: 10000,
                                    maximumAge: 0
                                }
                            );
                        }
                    });
            }

            // Minta izin lokasi saat halaman dimuat
            requestLocationPermission();
        });
    </script>
@endpush
