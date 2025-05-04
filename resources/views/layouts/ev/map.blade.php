@extends('layouts.main')

@section('title', 'Map - EV Charger')

@section('additional_head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/require.js/2.3.6/require.min.js"></script>
    <style>
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

        #mapControlsToggle {
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

        #mapControlsToggle:hover {
            background-color: #f3f4f6;
            transform: scale(1.05);
        }

        #mapContainer {
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

        @media (max-width: 767px) {
            #mapControls {
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

            #mapControls.show {
                opacity: 1;
                transform: translateY(0);
                pointer-events: auto;
            }

            #mapControlsToggle {
                display: block;
            }

            #mapContainer {
                height: calc(100vh - 64px);
                padding: 0;
            }

            #mapid {
                border-radius: 0;
                height: 100%;
            }
        }

        #providerSelect,
        #restAreaSelect,
        #currentChargerSelect {
            width: 200px;
        }

        #mapContainer {
            position: relative;
            width: 100%;
            height: calc(100vh - 64px - 2rem);
            padding: 1rem;
        }

        #mapid {
            width: 100%;
            height: 100%;
        }

        #locateMe {
            position: absolute;
            bottom: 120px;
            right: 30px;
            z-index: 1000;
        }

        .custom-icon {
            position: relative;
            width: 40px;
            height: 50px;
        }

        .custom-icon-image {
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

        .custom-icon-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .custom-icon-pointer {
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

        @media (max-width: 767px) {
            #mapControls {
                display: none;
                top: 70px;
                right: 20px;
                flex-direction: column;
                width: 80%;
                max-width: 300px;
                background-color: rgba(255, 255, 255, 0.9);
            }

            #mapControls.show {
                display: flex;
            }

            #mapControlsToggle {
                display: block;
            }

            #mapContainer {
                height: calc(100vh - 64px);
                padding: 0;
            }

            #mapid {
                border-radius: 0;
                height: 100%;
            }

            #locateMe {
                bottom: 60px; // Ubah nilai ini sesuai kebutuhan
            }
        }
    </style>
@endsection

@section('content')
    <div class="relative">
        <button id="mapControlsToggle" class="md:hidden">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
            </svg>
        </button>
        <div id="mapControls" class="flex flex-wrap gap-1 mb-0">
            <div class="flex-1 min-w-[200px]">
                <select id="providerSelect"
                    class="py-2 pr-10 pl-3 w-full text-base rounded-md border-gray-300 focus:outline-none focus:ring-ev-blue-500 focus:border-ev-blue-500 sm:text-sm">
                    <option value="">All Providers</option>
                    @foreach ($providers as $provider)
                        <option value="{{ $provider->id }}">{{ $provider->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1 min-w-[200px]">
                <select id="restAreaSelect"
                    class="py-2 pr-10 pl-3 w-full text-base rounded-md border-gray-300 focus:outline-none focus:ring-ev-blue-500 focus:border-ev-blue-500 sm:text-sm">
                    <option value="">Rest Area & Non-Rest Area</option>
                    <option value="1">Rest Area Only</option>
                    <option value="0">Non-Rest Area Only</option>
                </select>
            </div>
            <div class="flex-1 min-w-[200px]">
                <select id="currentChargerSelect"
                    class="py-2 pr-10 pl-3 w-full text-base rounded-md border-gray-300 focus:outline-none focus:ring-ev-blue-500 focus:border-ev-blue-500 sm:text-sm">
                    <option value="">All Current Types</option>
                    @foreach ($currentChargers as $currentCharger)
                        <option value="{{ $currentCharger->id }}">{{ $currentCharger->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div id="mapContainer">
            <div id="mapid" class="rounded-lg shadow-lg"></div>
            <button id="locateMe"
                class="p-2 text-black bg-white rounded border border-gray-300 transition duration-300 hover:bg-gray-100">
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
@endsection

@push('scripts')
    <script>
        require.config({
            paths: {
                'leaflet': 'https://unpkg.com/leaflet/dist/leaflet'
            }
        });

        require(['leaflet'], function(L) {
            const defaultView = [-6.200000, 106.816666];
            const map = L.map('mapid').setView(defaultView, 13);
            let userMarker;
            let markers = [];

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '© OpenStreetMap'
            }).addTo(map);

            const chargerLocations = @json($chargerLocations);

            function createMarkers(selectedProvider = '', isRestArea = '', selectedCurrentCharger = '') {
                markers.forEach(marker => map.removeLayer(marker));
                markers = [];

                chargerLocations.forEach(location => {
                    if (
                        location.status !== 3 &&
                        (!selectedProvider || location.provider.id.toString() === selectedProvider) &&
                        (isRestArea === '' || location.is_rest_area.toString() === isRestArea) &&
                        (!selectedCurrentCharger || location.chargers.some(charger => charger
                            .current_charger && charger.current_charger.id.toString() ===
                            selectedCurrentCharger))
                    ) {
                        // Buat ikon kustom untuk setiap provider
                        const customIcon = L.divIcon({
                            className: 'custom-icon',
                            html: `
                                <div class="custom-icon-pointer"></div>
                                <div class="custom-icon-image">
                                    <img src="/storage/${location.provider.image}" alt="${location.provider.name}">
                                </div>
                            `,
                            iconSize: [40, 50],
                            iconAnchor: [20, 50],
                            popupAnchor: [0, -50]
                        });

                        let chargersHtml = '';
                        if (location.chargers && location.chargers.length > 0) {
                            chargersHtml =
                                '<h4 class="mt-2 mb-1 font-semibold text-ev-blue-700">Chargers:</h4><ul class="pl-4 list-disc">';
                            location.chargers.forEach(charger => {
                                if (!selectedCurrentCharger || (charger.current_charger && charger
                                        .current_charger.id.toString() === selectedCurrentCharger
                                    )) {
                                    const powerCharger = charger.power_charger ? charger
                                        .power_charger.name : 'N/A';
                                    const currentCharger = charger.current_charger ? charger
                                        .current_charger.name : 'N/A';
                                    const typeCharger = charger.type_charger ? charger.type_charger
                                        .name : 'N/A';
                                    const units = charger.unit || 1;
                                    chargersHtml +=
                                        `<li class="text-ev-gray-600">${powerCharger} - ${currentCharger} - ${typeCharger} (${units} unit)</li>`;
                                }
                            });
                            chargersHtml += '</ul>';
                        }

                        const marker = L.marker([location.latitude, location.longitude], {
                            icon: customIcon
                        }).bindPopup(`
                            <div class="p-4 max-w-xs rounded-lg shadow-md bg-ev-white">
                                <h3 class="mb-2 text-lg font-bold text-ev-blue-800">${location.name}</h3>
                                ${location.image ? `<img src="/storage/${location.image}" alt="${location.name}" class="object-cover mb-2 w-full h-32 rounded" onerror="this.onerror=null; this.src='/images/placeholder.jpg';">` : ''}
                                <a href="https://www.google.com/maps/search/?api=1&query=${location.latitude},${location.longitude}"
                                   class="inline-block px-4 py-2 mt-2 text-sm text-white rounded transition duration-300 bg-ev-green-100 hover:bg-ev-green-600"
                                   target="_blank">
                                    Open in Google Maps
                                </a>
                                ${chargersHtml}
                                <p class="mb-1 text-ev-gray-600">Contributor: ${location.user ? location.user.name : 'Unknown'}</p>
                            </div>
                        `);
                        markers.push(marker);
                        marker.addTo(map);
                    }
                });
            }

            createMarkers();

            // Provider select functionality
            const providerSelect = document.getElementById('providerSelect');
            const restAreaSelect = document.getElementById('restAreaSelect');
            const currentChargerSelect = document.getElementById('currentChargerSelect');

            providerSelect.addEventListener('change', function() {
                createMarkers(this.value, restAreaSelect.value, currentChargerSelect.value);
            });

            restAreaSelect.addEventListener('change', function() {
                createMarkers(providerSelect.value, this.value, currentChargerSelect.value);
            });

            currentChargerSelect.addEventListener('change', function() {
                createMarkers(providerSelect.value, restAreaSelect.value, this.value);
            });

            function locateUser() {
                if ("geolocation" in navigator) {
                    navigator.geolocation.getCurrentPosition(function(position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;

                        if (userMarker) {
                            map.removeLayer(userMarker);
                        }

                        userMarker = L.marker([lat, lng], {
                            icon: L.icon({
                                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                                iconSize: [25, 41],
                                iconAnchor: [12, 41],
                                popupAnchor: [1, -34],
                                shadowSize: [41, 41]
                            })
                        }).addTo(map).bindPopup("You are here!").openPopup();

                        map.setView([lat, lng], 15);
                    }, function(error) {
                        console.error("Error: " + error.message);
                        alert("Unable to retrieve your location. Using default view.");
                        map.setView(defaultView, 13);
                    });
                } else {
                    alert("Geolocation is not supported by your browser. Using default view.");
                    map.setView(defaultView, 13);
                }
            }

            // Attempt to locate user immediately
            locateUser();

            // Add click event for manual location refresh
            document.getElementById('locateMe').addEventListener('click', locateUser);

            // Tambahkan ini untuk memastikan peta dirender dengan benar setelah semua elemen dimuat
            setTimeout(function() {
                map.invalidateSize();
            }, 100);

            // Tambahkan event listener untuk mengubah ukuran peta saat jendela diubah ukurannya
            window.addEventListener('resize', function() {
                map.invalidateSize();
            });

            // Tambahkan ini untuk menyesuaikan posisi mapControls dan mapContainer
            function adjustMapPosition() {
                const mobileMenu = document.getElementById('mobile-menu');
                const mapControls = document.getElementById('mapControls');
                const mapContainer = document.getElementById('mapContainer');
                const navbarHeight = 64; // Sesuaikan dengan tinggi navbar Anda

                if (window.innerWidth <= 767) {
                    const mobileMenuHeight = mobileMenu.offsetHeight;
                    const newTopPosition = navbarHeight + (mobileMenu.classList.contains('hidden') ? 0 :
                        mobileMenuHeight);

                    mapControls.style.position = 'static';
                    mapControls.style.top = '';
                    mapControls.style.right = '';
                    mapControls.style.left = '';
                    mapControls.style.background = 'transparent';
                    mapControls.style.boxShadow = 'none';
                    mapControls.style.padding = '0';
                    mapContainer.style.marginTop = '10px';
                    mapContainer.style.height = `calc(100vh - ${newTopPosition}px - 100px)`;
                } else {
                    mapControls.style.position = 'absolute';
                    mapControls.style.top = '30px';
                    mapControls.style.right = '30px';
                    mapControls.style.left = '';
                    mapControls.style.background = 'white';
                    mapControls.style.boxShadow = '0 2px 4px rgba(0, 0, 0, 0.1)';
                    mapControls.style.padding = '10px';
                    mapContainer.style.marginTop = '';
                    mapContainer.style.height = 'calc(100vh - 64px - 2rem)';
                }

                map.invalidateSize();
            }

            // Panggil fungsi saat halaman dimuat dan saat ukuran jendela berubah
            window.addEventListener('load', adjustMapPosition);
            window.addEventListener('resize', adjustMapPosition);

            // Tambahkan event listener untuk toggle menu mobile
            const mobileMenuToggle = document.querySelector('[onclick="toggleMenu()"]');
            if (mobileMenuToggle) {
                mobileMenuToggle.addEventListener('click', function() {
                    setTimeout(adjustMapPosition,
                        10); // Sedikit delay untuk memastikan transisi menu selesai
                });
            }
        });

        // Tambahkan ini di akhir script
        document.getElementById('mapControlsToggle').addEventListener('click', function() {
            const mapControls = document.getElementById('mapControls');
            mapControls.classList.toggle('show');
        });
    </script>
@endpush
