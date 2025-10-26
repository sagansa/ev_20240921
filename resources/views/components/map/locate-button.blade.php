@props(['class' => ''])

<button 
    id="locateMe" 
    {{ $attributes->merge(['class' => 'map-locate-button ' . $class]) }}
    title="Temukan lokasi saya"
>
    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
              d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
              d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
    </svg>
</button>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const locateButton = document.getElementById('locateMe');
            let isLocating = false;

            function showLocationError(message) {
                const errorDiv = document.createElement('div');
                errorDiv.className = 'location-error';
                errorDiv.textContent = message;
                document.body.appendChild(errorDiv);
                setTimeout(() => errorDiv.remove(), 3000);
            }

            locateButton.addEventListener('click', function() {
                if (isLocating) return;
                isLocating = true;
                locateButton.classList.add('locating');

                if (!navigator.geolocation) {
                    showLocationError('Geolokasi tidak didukung oleh browser Anda');
                    isLocating = false;
                    locateButton.classList.remove('locating');
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
                        locateButton.classList.remove('locating');
                    },
                    function(error) {
                        isLocating = false;
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
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 0
                    }
                );
            });
        });
    </script>