@props(['providers', 'chargingTypes', 'locationCategories'])

<div class="map-controls">
    <select id="providerSelect" class="px-3 py-2 w-full text-sm rounded-lg border dark:bg-gray-700 dark:border-gray-600">
        <option value="">Semua Provider</option>
        @foreach ($providers as $provider)
            <option value="{{ $provider->id }}">{{ $provider->name }}</option>
        @endforeach
    </select>

    <select id="chargingTypeSelect"
        class="px-3 py-2 w-full text-sm rounded-lg border dark:bg-gray-700 dark:border-gray-600">
        <option value="">Semua Tipe Charging</option>
        @foreach ($chargingTypes as $type)
            <option value="{{ $type->id }}">{{ $type->name }}</option>
        @endforeach
    </select>

    <select id="locationCategorySelect"
        class="px-3 py-2 w-full text-sm rounded-lg border dark:bg-gray-700 dark:border-gray-600">
        <option value="">Semua Kategori Lokasi</option>
        @foreach ($locationCategories as $category)
            <option value="{{ $category->id }}">{{ $category->name }}</option>
        @endforeach
    </select>
</div>

<button id="mapControlsToggle" class="map-controls-toggle">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
    </svg>
</button>
