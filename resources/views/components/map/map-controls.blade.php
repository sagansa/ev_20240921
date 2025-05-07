@props([
    'providers' => [],
    'chargingTypes' => [],
    'locationCategories' => [],
    'class' => '',
])

<div class="flex flex-wrap gap-4 p-4 bg-white dark:bg-gray-800 rounded-lg shadow-lg {{ $class }}">
    <div class="flex-1 min-w-[200px]">
        <select id="providerSelect"
            class="px-3 py-2 w-full text-base rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:outline-none focus:ring-ev-blue-500 focus:border-ev-blue-500">
            <option value="">Semua Provider</option>
            @foreach ($providers as $provider)
                <option value="{{ $provider->id }}">{{ $provider->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="flex-1 min-w-[200px]">
        <select id="chargingTypeSelect"
            class="px-3 py-2 w-full text-base rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:outline-none focus:ring-ev-blue-500 focus:border-ev-blue-500">
            <option value="">Semua Tipe Charging</option>
            @foreach ($chargingTypes as $type)
                <option value="{{ $type->id }}">{{ $type->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="flex-1 min-w-[200px]">
        <select id="locationCategorySelect"
            class="px-3 py-2 w-full text-base rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:outline-none focus:ring-ev-blue-500 focus:border-ev-blue-500">
            <option value="">Semua Kategori Lokasi</option>
            @foreach ($locationCategories as $category)
                <option value="{{ $category->id }}">{{ $category->name }}</option>
            @endforeach
        </select>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const providerSelect = document.getElementById('providerSelect');
            const chargingTypeSelect = document.getElementById('chargingTypeSelect');
            const locationCategorySelect = document.getElementById('locationCategorySelect');

            function updateMarkers() {
                const selectedProvider = providerSelect.value;
                const selectedChargingType = chargingTypeSelect.value;
                const selectedLocationCategory = locationCategorySelect.value;

                window.dispatchEvent(new CustomEvent('updateMarkers', {
                    detail: {
                        provider: selectedProvider,
                        chargingType: selectedChargingType,
                        locationCategory: selectedLocationCategory
                    }
                }));
            }

            providerSelect.addEventListener('change', updateMarkers);
            chargingTypeSelect.addEventListener('change', updateMarkers);
            locationCategorySelect.addEventListener('change', updateMarkers);
        });
    </script>
@endpush
