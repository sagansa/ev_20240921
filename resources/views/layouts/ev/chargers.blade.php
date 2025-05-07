@extends('layouts.main')

@section('title', 'Chargers - EV Charger')

@section('content')
    <div class="p-8">
        <h2 class="mb-6 text-3xl font-bold text-ev-blue-800 dark:text-ev-blue-400">Chargers</h2>

        <x-charger.filter-form :provinces="$provinces" :cities="$cities" :currentChargers="$currentChargers" :typeChargers="$typeChargers" :powerChargers="$powerChargers"
            :providers="$providers" />

        @if ($chargers->count() > 0)
            <x-charger.table :chargers="$chargers" />
        @else
            <p class="text-gray-600 dark:text-gray-400">Tidak ada charger yang tersedia saat ini.</p>
        @endif
    </div>

    <!-- Modal -->
    <div id="providerModal" class="hidden overflow-y-auto fixed inset-0 z-50" aria-labelledby="modal-title" role="dialog"
        aria-modal="true">
        <div class="flex justify-center items-center px-4 pt-4 pb-20 min-h-screen text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity dark:bg-gray-800 dark:bg-opacity-75"
                aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div
                class="inline-block overflow-hidden text-left align-bottom bg-white rounded-lg shadow-xl transition-all transform dark:bg-gray-900 sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="px-4 pt-5 pb-4 bg-white dark:bg-gray-900 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 w-full text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100" id="modal-title">
                                Detail Provider
                            </h3>
                            <div class="mt-2">
                                <div id="providerDetails" class="text-sm text-gray-500 dark:text-gray-400"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="px-4 py-3 bg-gray-50 dark:bg-gray-800 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" id="closeModal"
                        class="inline-flex justify-center px-4 py-2 mt-3 w-full text-base font-medium text-gray-700 bg-white rounded-md border border-gray-300 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
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

            function submitForm() {
                form.submit();
            }

            [provinceSelect, citySelect, currentSelect, typeSelect, powerSelect, providerSelect, restAreaSelect]
            .forEach(select => {
                select.addEventListener('change', submitForm);
            });

            let debounceTimer;
            searchInput.addEventListener('input', function() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(submitForm, 300);
            });

            provinceSelect.addEventListener('change', function() {
                const provinceId = this.value;
                citySelect.innerHTML = '<option value="">Semua Kota</option>';

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
                            submitForm();
                        });
                } else {
                    citySelectWrapper.style.display = 'none';
                    submitForm();
                }
            });

            currentSelect.addEventListener('change', function() {
                const currentId = this.value;
                typeSelect.innerHTML = '<option value="">Semua Tipe</option>';
                powerSelect.innerHTML = '<option value="">Semua Daya</option>';

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
                            submitForm();
                        });
                } else {
                    typeSelectWrapper.style.display = 'none';
                    powerSelectWrapper.style.display = 'none';
                    submitForm();
                }
            });

            typeSelect.addEventListener('change', function() {
                const typeId = this.value;
                powerSelect.innerHTML = '<option value="">Semua Daya</option>';

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
                            submitForm();
                        });
                } else {
                    powerSelectWrapper.style.display = 'none';
                    submitForm();
                }
            });

            clearFilterButton.addEventListener('click', function(e) {
                e.preventDefault();
                provinceSelect.value = '';
                citySelectWrapper.style.display = 'none';
                citySelect.innerHTML = '<option value="">Semua Kota</option>';
                currentSelect.value = '';
                typeSelectWrapper.style.display = 'none';
                typeSelect.innerHTML = '<option value="">Semua Tipe</option>';
                powerSelectWrapper.style.display = 'none';
                powerSelect.innerHTML = '<option value="">Semua Daya</option>';
                providerSelect.value = '';
                restAreaSelect.value = '';
                searchInput.value = '';
                submitForm();
            });

            // Provider Modal
            const providerModal = document.getElementById('providerModal');
            const providerDetails = document.getElementById('providerDetails');
            const closeModal = document.getElementById('closeModal');

            function openModal(providerId) {
                fetch(`/get-provider-details/${providerId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            alert(data.error);
                        } else {
                            providerDetails.innerHTML = `
                                <div class="flex items-center mb-4">
                                    ${data.image ? `<img src="${data.image}" alt="${data.name}" class="object-contain mr-4 w-16 h-16">` : ''}
                                    <h2 class="text-xl font-bold dark:text-gray-100">${data.name}</h2>
                                </div>
                                <p class="dark:text-gray-300"><strong>Kontak:</strong> ${data.contact || 'N/A'}</p>
                                <p class="dark:text-gray-300"><strong>Email:</strong> ${data.email || 'N/A'}</p>
                                <p class="dark:text-gray-300"><strong>Harga:</strong> ${data.price || 'N/A'}</p>
                                <p class="dark:text-gray-300"><strong>Pajak:</strong> ${data.tax || 'N/A'}</p>
                                <p class="dark:text-gray-300"><strong>Biaya Admin:</strong> ${data.admin_fee || 'N/A'}</p>
                            `;
                            providerModal.classList.remove('hidden');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Terjadi kesalahan saat mengambil detail provider');
                    });
            }

            function closeModalFunction() {
                providerModal.classList.add('hidden');
            }

            closeModal.addEventListener('click', closeModalFunction);

            document.querySelectorAll('.provider-info').forEach(element => {
                element.addEventListener('click', function() {
                    const providerId = this.dataset.providerId;
                    openModal(providerId);
                });
            });
        });
    </script>
@endpush
