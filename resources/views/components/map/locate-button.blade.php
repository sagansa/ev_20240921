@props(['class' => ''])

<button id="locateMe"
    class="p-2 text-black bg-white dark:bg-gray-800 dark:text-white rounded-full border border-gray-300 dark:border-gray-600 transition duration-300 hover:bg-gray-100 dark:hover:bg-gray-700 {{ $class }}">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
    </svg>
</button>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const locateButton = document.getElementById('locateMe');
            let isLocating = false;

            function showLocationError(message) {
                const errorDiv = document.createElement('div');
                errorDiv.className =
                    'fixed bottom-4 left-4 bg-red-500 text-white px-4 py-2 rounded shadow-lg z-50 animate-fade-in';
                errorDiv.textContent = message;
                document.body.appendChild(errorDiv);
                setTimeout(() => errorDiv.remove(), 3000);
            }

            locateButton.addEventListener('click', function() {
                if (isLocating) return;
                isLocating = true;
                locateButton.classList.add('animate-spin');

                if (!navigator.geolocation) {
                    showLocationError('Geolokasi tidak didukung oleh browser Anda');
                    isLocating = false;
                    locateButton.classList.remove('animate-spin');
                    return;
                }

                navigator.permissions.query({
                    name: 'geolocation'
                }).then(function(result) {
                    if (result.state === 'denied') {
                        showLocationError(
                            'Izin lokasi ditolak. Mohon aktifkan di pengaturan browser Anda');
                        isLocating = false;
                        locateButton.classList.remove('animate-spin');
                        return;
                    }

                    navigator.geolocation.getCurrentPosition(
                        function(position) {
                            window.dispatchEvent(new CustomEvent('userLocation', {
                                detail: {
                                    latitude: position.coords.latitude,
                                    longitude: position.coords.longitude
                                }
                            }));
                            isLocating = false;
                            locateButton.classList.remove('animate-spin');
                        },
                        function(error) {
                            let message =
                                'Terjadi kesalahan saat mencoba mendapatkan lokasi Anda';
                            if (error.code === 1) {
                                message =
                                    'Izin lokasi ditolak. Mohon aktifkan di pengaturan browser Anda';
                            } else if (error.code === 2) {
                                message = 'Lokasi tidak tersedia';
                            } else if (error.code === 3) {
                                message = 'Waktu permintaan lokasi habis';
                            }
                            showLocationError(message);
                            isLocating = false;
                            locateButton.classList.remove('animate-spin');
                        }, {
                            enableHighAccuracy: true,
                            timeout: 10000,
                            maximumAge: 0
                        }
                    );
                });
            });
        });
    </script>
@endpush

@push('styles')
    <style>
        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        @keyframes fade-in {
            from {
                opacity: 0;
                transform: translateY(1rem);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-spin {
            animation: spin 1s linear infinite;
        }

        .animate-fade-in {
            animation: fade-in 0.3s ease-out;
        }
    </style>
@endpush
