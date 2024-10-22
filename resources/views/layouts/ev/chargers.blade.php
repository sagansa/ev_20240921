@extends('layouts.main')

@section('title', 'Chargers - EV Charger')

@section('content')
    <div class="p-8">
        <h2 class="mb-6 text-3xl font-bold text-ev-blue-800">Chargers</h2>


        <!-- Form pencarian dan filter -->
        <form action="{{ route('chargers') }}" method="GET" class="mb-4 space-y-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3 lg:grid-cols-6">
                <input type="text" name="search" id="search" placeholder="find location..."
                    value="{{ request('search') }}"
                    class="mt-2 block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6">

                <select name="province" id="province"
                    class="mt-2 block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6">
                    <option value="">All Province</option>
                    @foreach ($provinces as $province)
                        <option value="{{ $province->id }}" {{ request('province') == $province->id ? 'selected' : '' }}>
                            {{ ucwords(strtolower($province->name)) }}
                        </option>
                    @endforeach
                </select>

                <div id="citySelectWrapper" style="{{ request('province') ? '' : 'display: none;' }}">
                    <select name="city" id="city"
                        class="mt-2 block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6">
                        <option value="">All City</option>
                        @foreach ($cities as $city)
                            <option value="{{ $city->id }}" {{ request('city') == $city->id ? 'selected' : '' }}>
                                {{ ucwords(strtolower($city->name)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <select name="current" id="current"
                    class="mt-2 block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6">
                    <option value="">All Current</option>
                    @foreach ($currentChargers as $current)
                        <option value="{{ $current->id }}" {{ request('current') == $current->id ? 'selected' : '' }}>
                            {{ $current->name }}
                        </option>
                    @endforeach
                </select>

                <div id="typeSelectWrapper" style="{{ request('current') ? '' : 'display: none;' }}">
                    <select name="type" id="type"
                        class="mt-2 block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6">
                        <option value="">All Type</option>
                        @foreach ($typeChargers as $type)
                            <option value="{{ $type->id }}" {{ request('type') == $type->id ? 'selected' : '' }}>
                                {{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div id="powerSelectWrapper" style="{{ request('type') ? '' : 'display: none;' }}">
                    <select name="power" id="power"
                        class="mt-2 block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6">
                        <option value="">All Power</option>
                        @foreach ($powerChargers as $power)
                            <option value="{{ $power->id }}" {{ request('power') == $power->id ? 'selected' : '' }}>
                                {{ $power->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <select name="provider" id="provider"
                    class="mt-2 block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6">
                    <option value="">All Provider</option>
                    @foreach ($providers as $provider)
                        <option value="{{ $provider->id }}" {{ request('provider') == $provider->id ? 'selected' : '' }}>
                            {{ $provider->name }}
                        </option>
                    @endforeach
                </select>

                <select name="rest_area" id="rest_area"
                    class="mt-2 block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6">
                    <option value="">Rest Area dan Non Rest Area</option>
                    <option value="1" {{ request('rest_area') == '1' ? 'selected' : '' }}>Rest Area</option>
                    <option value="0" {{ request('rest_area') == '0' ? 'selected' : '' }}>Bukan Rest Area</option>
                </select>
            </div>
            <div class="flex justify-end">
                <button type="button" id="clearFilter"
                    class="px-4 py-2 text-sm font-medium text-white bg-gray-600 rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                    Clear Filter
                </button>
            </div>
        </form>

        <!-- Tambahkan div untuk peta -->
        <div id="map" style="height: 400px;" class="mb-8"></div>

        <!-- Kode tabel tetap sama -->
        @if ($chargers->count() > 0)
            <div class="flow-root mt-8">
                <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                    <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                        <table class="min-w-full divide-y divide-gray-300">
                            <thead>
                                <tr>
                                    <th scope="col"
                                        class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-0">
                                        Map
                                    </th>
                                    <th scope="col"
                                        class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-0">
                                        <a href="{{ route('chargers', ['sort' => 'location', 'direction' => request('sort') == 'location' && request('direction') == 'asc' ? 'desc' : 'asc']) }}"
                                            class="inline-flex group">
                                            Location
                                            <span
                                                class="flex-none ml-2 text-gray-400 rounded group-hover:visible group-focus:visible">
                                                @if (request('sort') == 'location')
                                                    @if (request('direction') == 'asc')
                                                        &#x25B2;
                                                    @else
                                                        &#x25BC;
                                                    @endif
                                                @else
                                                    &#x25B2;
                                                @endif
                                            </span>
                                        </a>
                                    </th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                        <a href="{{ route('chargers', ['sort' => 'provider', 'direction' => request('sort') == 'provider' && request('direction') == 'asc' ? 'desc' : 'asc']) }}"
                                            class="inline-flex group">
                                            Provider
                                            <span
                                                class="flex-none ml-2 text-gray-400 rounded group-hover:visible group-focus:visible">
                                                @if (request('sort') == 'provider')
                                                    @if (request('direction') == 'asc')
                                                        &#x25B2;
                                                    @else
                                                        &#x25BC;
                                                    @endif
                                                @else
                                                    &#x25B2;
                                                @endif
                                            </span>
                                        </a>
                                    </th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                        <a href="{{ route('chargers', ['sort' => 'current', 'direction' => request('sort') == 'current' && request('direction') == 'asc' ? 'desc' : 'asc']) }}"
                                            class="inline-flex group">
                                            Current
                                            <span
                                                class="flex-none ml-2 text-gray-400 rounded group-hover:visible group-focus:visible">
                                                @if (request('sort') == 'current')
                                                    @if (request('direction') == 'asc')
                                                        &#x25B2;
                                                    @else
                                                        &#x25BC;
                                                    @endif
                                                @else
                                                    &#x25B2;
                                                @endif
                                            </span>
                                        </a>
                                    </th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                        <a href="{{ route('chargers', ['sort' => 'type', 'direction' => request('sort') == 'type' && request('direction') == 'asc' ? 'desc' : 'asc']) }}"
                                            class="inline-flex group">
                                            Type
                                            <span
                                                class="flex-none ml-2 text-gray-400 rounded group-hover:visible group-focus:visible">
                                                @if (request('sort') == 'type')
                                                    @if (request('direction') == 'asc')
                                                        &#x25B2;
                                                    @else
                                                        &#x25BC;
                                                    @endif
                                                @else
                                                    &#x25B2;
                                                @endif
                                            </span>
                                        </a>
                                    </th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                        <a href="{{ route('chargers', ['sort' => 'power', 'direction' => request('sort') == 'power' && request('direction') == 'asc' ? 'desc' : 'asc']) }}"
                                            class="inline-flex group">
                                            Power
                                            <span
                                                class="flex-none ml-2 text-gray-400 rounded group-hover:visible group-focus:visible">
                                                @if (request('sort') == 'power')
                                                    @if (request('direction') == 'asc')
                                                        &#x25B2;
                                                    @else
                                                        &#x25BC;
                                                    @endif
                                                @else
                                                    &#x25B2;
                                                @endif
                                            </span>
                                        </a>
                                    </th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                        Unit
                                    </th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                        <a href="{{ route('chargers', ['sort' => 'city', 'direction' => request('sort') == 'city' && request('direction') == 'asc' ? 'desc' : 'asc']) }}"
                                            class="inline-flex group">
                                            City
                                            <span
                                                class="flex-none ml-2 text-gray-400 rounded group-hover:visible group-focus:visible">
                                                @if (request('sort') == 'city')
                                                    @if (request('direction') == 'asc')
                                                        &#x25B2;
                                                    @else
                                                        &#x25BC;
                                                    @endif
                                                @else
                                                    &#x25B2;
                                                @endif
                                            </span>
                                        </a>
                                    </th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                        <a href="{{ route('chargers', ['sort' => 'province', 'direction' => request('sort') == 'province' && request('direction') == 'asc' ? 'desc' : 'asc']) }}"
                                            class="inline-flex group">
                                            Province
                                            <span
                                                class="flex-none ml-2 text-gray-400 rounded group-hover:visible group-focus:visible">
                                                @if (request('sort') == 'province')
                                                    @if (request('direction') == 'asc')
                                                        &#x25B2;
                                                    @else
                                                        &#x25BC;
                                                    @endif
                                                @else
                                                    &#x25B2;
                                                @endif
                                            </span>
                                        </a>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach ($chargers as $charger)
                                    <tr>
                                        <td
                                            class="py-4 pl-4 pr-3 text-sm font-medium text-gray-900 whitespace-nowrap sm:pl-0">
                                            @if ($charger->chargerLocation && $charger->chargerLocation->latitude && $charger->chargerLocation->longitude)
                                                <a href="https://www.google.com/maps/search/?api=1&query={{ $charger->chargerLocation->latitude }},{{ $charger->chargerLocation->longitude }}"
                                                    target="_blank" class="text-ev-blue-600 hover:text-ev-blue-800">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5"
                                                        viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd"
                                                            d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
                                                            clip-rule="evenodd" />
                                                    </svg>
                                                </a>
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td
                                            class="py-4 pl-4 pr-3 text-sm font-medium text-gray-900 whitespace-nowrap sm:pl-0">
                                            {{ $charger->chargerLocation->name ?? 'N/A' }}
                                        </td>
                                        <td class="px-3 py-4 text-sm text-gray-500 whitespace-nowrap">
                                            @if ($charger->chargerLocation->provider)
                                                <span class="cursor-pointer provider-info"
                                                    data-provider-id="{{ $charger->chargerLocation->provider->id }}">
                                                    {{ $charger->chargerLocation->provider->name }}
                                                </span>
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td class="px-3 py-4 text-sm text-gray-500 whitespace-nowrap">
                                            {{ $charger->currentCharger->name ?? 'N/A' }}
                                        </td>
                                        <td class="px-3 py-4 text-sm text-gray-500 whitespace-nowrap">
                                            {{ $charger->typeCharger->name ?? 'N/A' }}
                                        </td>
                                        <td class="px-3 py-4 text-sm text-gray-500 whitespace-nowrap">
                                            {{ $charger->powerCharger->name ?? 'N/A' }}
                                        </td>
                                        <td class="px-3 py-4 text-sm text-gray-500 whitespace-nowrap">
                                            {{ $charger->unit ?? 'N/A' }}
                                        </td>
                                        <td class="px-3 py-4 text-sm text-gray-500 whitespace-nowrap">
                                            {{ ucwords(strtolower($charger->chargerLocation->city->name ?? 'n/a')) }}
                                        </td>
                                        <td class="px-3 py-4 text-sm text-gray-500 whitespace-nowrap">
                                            {{ ucwords(strtolower($charger->chargerLocation->province->name ?? 'n/a')) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tambahkan pagination links -->
            <div class="mt-4">
                {{ $chargers->links() }}
            </div>
        @else
            <p class="text-gray-600">Tidak ada charger yang tersedia saat ini.</p>
        @endif
    </div>

    <!-- Modal -->
    <div id="providerModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title"
        role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div
                class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="px-4 pt-5 pb-4 bg-white sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="w-full mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg font-medium leading-6 text-gray-900" id="modal-title">
                                Provider Details
                            </h3>
                            <div class="mt-2">
                                <div id="providerDetails" class="text-sm text-gray-500"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="px-4 py-3 bg-gray-50 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button"
                        class="inline-flex justify-center w-full px-4 py-2 mt-3 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                        id="closeModal">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <!-- Tambahkan Leaflet CSS dan JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const provinceSelect = document.getElementById('province');
            const citySelectWrapper = document.getElementById('citySelectWrapper');
            const citySelect = document.getElementById('city');
            const currentSelect = document.getElementById('current');
            const typeSelectWrapper = document.getElementById('typeSelectWrapper');
            const typeSelect = document.getElementById('type');
            const powerSelectWrapper = document.getElementById('powerSelectWrapper');
            const powerSelect = document.getElementById('power');
            const providerSelect = document.getElementById('provider');
            const restAreaSelect = document.getElementById('rest_area');
            const searchInput = document.querySelector('input[name="search"]');
            const clearFilterButton = document.getElementById('clearFilter');

            // Inisialisasi peta
            var map = L.map('map').setView([-2.5489, 118.0149], 5); // Koordinat Indonesia

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            // Fungsi untuk membuat ikon kustom
            function createCustomIcon(iconUrl) {
                return L.icon({
                    iconUrl: iconUrl,
                    iconSize: [38, 38], // Sesuaikan ukuran ikon
                    iconAnchor: [19, 38], // Sesuaikan anchor ikon
                    popupAnchor: [0, -38] // Sesuaikan anchor popup
                });
            }

            // Tambahkan marker untuk setiap charger
            @foreach ($chargers as $charger)
                @if ($charger->chargerLocation && $charger->chargerLocation->latitude && $charger->chargerLocation->longitude)
                    var iconUrl =
                        '{{ $charger->chargerLocation->provider && $charger->chargerLocation->provider->image ? asset($charger->chargerLocation->provider->image) : asset('images/default-marker.png') }}';
                    var customIcon = createCustomIcon(iconUrl);

                    L.marker([{{ $charger->chargerLocation->latitude }},
                            {{ $charger->chargerLocation->longitude }}
                        ], {
                            icon: customIcon
                        })
                        .addTo(map)
                        .bindPopup(
                            "<b>{{ $charger->chargerLocation->name }}</b><br>Provider: {{ $charger->chargerLocation->provider->name ?? 'N/A' }}"
                        );
                @endif
            @endforeach

            // Fungsi untuk mengirim form
            function submitForm() {
                form.submit();
            }

            // Tambahkan event listener untuk setiap elemen filter
            [provinceSelect, citySelect, currentSelect, typeSelect, powerSelect, providerSelect, restAreaSelect]
            .forEach(select => {
                select.addEventListener('change', submitForm);
            });

            // Tambahkan debounce untuk searchInput
            let debounceTimer;
            searchInput.addEventListener('input', function() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(submitForm,
                    300); // Menunggu 300ms setelah user berhenti mengetik
            });

            provinceSelect.addEventListener('change', function() {
                const provinceId = this.value;
                citySelect.innerHTML = '<option value="">All City</option>';

                if (provinceId) {
                    citySelectWrapper.style.display = 'block';
                    fetch(`/get-cities/${provinceId}`)
                        .then(response => response.json())
                        .then(cities => {
                            cities.forEach(city => {
                                const option = document.createElement('option');
                                option.value = city.id;
                                option.textContent = city.name;
                                citySelect.appendChild(option);
                            });
                            submitForm(); // Submit form setelah mengupdate daftar kota
                        });
                } else {
                    citySelectWrapper.style.display = 'none';
                    submitForm(); // Submit form jika provinsi di-reset
                }
            });

            currentSelect.addEventListener('change', function() {
                const currentId = this.value;
                typeSelect.innerHTML = '<option value="">All Type</option>';
                powerSelect.innerHTML = '<option value="">All Power</option>';

                if (currentId) {
                    typeSelectWrapper.style.display = 'block';
                    fetch(`/get-type-chargers/${currentId}`)
                        .then(response => response.json())
                        .then(types => {
                            types.forEach(type => {
                                const option = document.createElement('option');
                                option.value = type.id;
                                option.textContent = type.name;
                                typeSelect.appendChild(option);
                            });
                            submitForm(); // Submit form setelah mengupdate daftar type
                        });
                } else {
                    typeSelectWrapper.style.display = 'none';
                    powerSelectWrapper.style.display = 'none';
                    submitForm(); // Submit form jika current di-reset
                }
            });

            typeSelect.addEventListener('change', function() {
                const typeId = this.value;
                powerSelect.innerHTML = '<option value="">All Power</option>';

                if (typeId) {
                    powerSelectWrapper.style.display = 'block';
                    fetch(`/get-power-chargers/${typeId}`)
                        .then(response => response.json())
                        .then(powers => {
                            powers.forEach(power => {
                                const option = document.createElement('option');
                                option.value = power.id;
                                option.textContent = power.name;
                                powerSelect.appendChild(option);
                            });
                            submitForm(); // Submit form setelah mengupdate daftar power
                        });
                } else {
                    powerSelectWrapper.style.display = 'none';
                    submitForm(); // Submit form jika type di-reset
                }
            });

            clearFilterButton.addEventListener('click', function(e) {
                e.preventDefault(); // Mencegah form submit default
                // Reset all filter
                provinceSelect.value = '';
                citySelectWrapper.style.display = 'none';
                citySelect.innerHTML = '<option value="">All City</option>';
                currentSelect.value = '';
                typeSelectWrapper.style.display = 'none';
                typeSelect.innerHTML = '<option value="">All Type</option>';
                powerSelectWrapper.style.display = 'none';
                powerSelect.innerHTML = '<option value="">All Power</option>';
                providerSelect.value = '';
                restAreaSelect.value = '';
                searchInput.value = '';

                // Submit form untuk me-refresh halaman dengan filter yang sudah di-reset
                submitForm();
            });
        });

        // Tambahkan kode berikut di bagian bawah script
        document.addEventListener('DOMContentLoaded', function() {
            const providerModal = document.getElementById('providerModal');
            const providerDetails = document.getElementById('providerDetails');
            const closeModal = document.getElementById('closeModal');

            // Fungsi untuk membuka modal
            function openModal(providerId) {
                fetch(`/get-provider-details/${providerId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            alert(data.error);
                        } else {
                            providerDetails.innerHTML = `
                                <div class="flex items-center mb-4">
                                    ${data.image ? `<img src="${data.image}" alt="${data.name}" class="object-contain w-16 h-16 mr-4">` : ''}
                                    <h2 class="text-xl font-bold">${data.name}</h2>
                                </div>
                                <p><strong>Contact:</strong> ${data.contact || 'N/A'}</p>
                                <p><strong>Email:</strong> ${data.email || 'N/A'}</p>
                                <p><strong>Price:</strong> ${data.price || 'N/A'}</p>
                                <p><strong>Tax:</strong> ${data.tax || 'N/A'}</p>
                                <p><strong>Admin Fee:</strong> ${data.admin_fee || 'N/A'}</p>
                            `;
                            providerModal.classList.remove('hidden');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while fetching provider details');
                    });
            }

            // Fungsi untuk menutup modal
            function closeModalFunction() {
                providerModal.classList.add('hidden');
            }

            // Event listener untuk menutup modal
            closeModal.addEventListener('click', closeModalFunction);

            // Event listener untuk membuka modal saat provider diklik
            document.querySelectorAll('.provider-info').forEach(element => {
                element.addEventListener('click', function() {
                    const providerId = this.dataset.providerId;
                    openModal(providerId);
                });
            });
        });
    </script>
@endpush
