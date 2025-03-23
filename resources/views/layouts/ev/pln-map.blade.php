@extends('layouts.main')

@section('title', 'Map - EV Charger')

@section('additional_head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
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

        #providerSelect {
            width: 200px;
        }

        #mapContainer {
            position: relative;
            width: 100%;
            height: calc(100vh - 64px);
            margin: 0;
            padding: 20px;
        }

        #mapid {
            width: 100%;
            height: 100%;
            border: 2px solid #3b82f6;
            border-radius: 8px;
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
            transition: transform 0.3s ease;
        }

        .custom-icon:hover {
            transform: scale(1.1);
            z-index: 1002;
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

            #locateMe {
                bottom: 60px;
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof L === 'undefined') {
                console.error('Leaflet library is not loaded!');
                return;
            }

            const defaultView = [-6.200000, 106.816666];
            const map = L.map('mapid').setView(defaultView, 13);
            let userMarker;
            let markers = [];

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '© OpenStreetMap'
            }).addTo(map);

            const chargerLocations = @json($plnLocations);

            function createMarkers(selectedProvider = '') {
                markers.forEach(marker => map.removeLayer(marker));
                markers = [];

                chargerLocations.forEach(location => {
                    if (!selectedProvider || location.provider.id.toString() === selectedProvider) {
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

                        let detailsHtml = '';
                        if (location.pln_charger_location_details && location.pln_charger_location_details
                            .length > 0) {
                            detailsHtml =
                                '<h4 class="mt-2 mb-1 font-semibold text-ev-blue-700">Charger Details:</h4><ul class="pl-4 list-disc">';
                            location.pln_charger_location_details.forEach(detail => {
                                const power = detail.power ? detail.power : 'N/A';
                                const connectorCount = detail.count_connector_charger ? detail
                                    .count_connector_charger : 'N/A';
                                // const category = detail.charger_category ? detail.charger_category
                                //     .name : 'N/A';
                                const merk = detail.merk_charger ? detail.merk_charger.name : 'N/A';
                                detailsHtml +=
                                    `<li class="text-ev-gray-600">${merk} - ${power} - (${connectorCount} connectors)</li>`;
                            });
                            detailsHtml += '</ul>';
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
                                <p class="mb-1 text-ev-gray-600">Location Category: ${location.location_category ? location.location_category.name : 'N/A'}</p>
                                ${detailsHtml}
                            </div>
                        `);
                        markers.push(marker);
                        marker.addTo(map);
                    }
                });
            }

            createMarkers();

            const providerSelect = document.getElementById('providerSelect');
            providerSelect.addEventListener('change', function() {
                createMarkers(this.value);
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

            locateUser();
            document.getElementById('locateMe').addEventListener('click', locateUser);

            setTimeout(function() {
                map.invalidateSize();
            }, 100);

            window.addEventListener('resize', function() {
                map.invalidateSize();
            });

            function adjustMapPosition() {
                const mobileMenu = document.getElementById('mobile-menu');
                const mapControls = document.getElementById('mapControls');
                const mapContainer = document.getElementById('mapContainer');
                const navbarHeight = 64;

                if (window.innerWidth <= 767) {
                    const mobileMenuHeight = mobileMenu ? mobileMenu.offsetHeight : 0;
                    const newTopPosition = navbarHeight + (mobileMenu && !mobileMenu.classList.contains('hidden') ?
                        mobileMenuHeight : 0);

                    mapControls.style.position = 'static';
                    mapControls.style.top = '';
                    mapControls.style.right = '';
                    mapControls.style.left = '';
                    mapControls.style.background = 'transparent';
                    mapControls.style.boxShadow = 'none';
                    mapControls.style.padding = '0';
                    mapContainer.style.marginTop = '10px';
                    mapContainer.style.height = '100vh';
                } else {
                    mapControls.style.position = 'absolute';
                    mapControls.style.top = '30px';
                    mapControls.style.right = '30px';
                    mapControls.style.left = '';
                    mapControls.style.background = 'white';
                    mapControls.style.boxShadow = '0 2px 4px rgba(0, 0, 0, 0.1)';
                    mapControls.style.padding = '10px';
                    mapContainer.style.marginTop = '';
                    mapContainer.style.height = '100vh';
                }

                map.invalidateSize();
            }

            window.addEventListener('load', adjustMapPosition);
            window.addEventListener('resize', adjustMapPosition);

            const mobileMenuToggle = document.querySelector('[onclick="toggleMenu()"]');
            if (mobileMenuToggle) {
                mobileMenuToggle.addEventListener('click', function() {
                    setTimeout(adjustMapPosition, 10);
                });
            }

            document.getElementById('mapControlsToggle').addEventListener('click', function() {
                const mapControls = document.getElementById('mapControls');
                mapControls.classList.toggle('show');
                this.classList.toggle('active');
                if (this.classList.contains('active')) {
                    this.style.transform = 'rotate(90deg)';
                } else {
                    this.style.transform = 'rotate(0deg)';
                }
            });
        });
    </script>
@endsection
