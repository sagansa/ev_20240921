@props(['provinces', 'cities', 'currentChargers', 'typeChargers', 'powerChargers', 'providers'])

<form action="{{ route('chargers') }}" method="GET" class="mb-4 space-y-4">
    <div class="grid grid-cols-1 gap-4 md:grid-cols-3 lg:grid-cols-6">
        {{-- Search Input --}}
        <x-input type="text" name="search" id="search" placeholder="Cari lokasi..." :value="request('search')"
            class="block py-1.5 pr-10 pl-3 mt-2 w-full text-gray-900 dark:text-white dark:bg-gray-800 rounded-md border-0 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6" />

        {{-- Province Select --}}
        <x-select name="province" id="province"
            class="block py-1.5 pr-10 pl-3 mt-2 w-full text-gray-900 dark:text-white dark:bg-gray-800 rounded-md border-0 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6">
            <option value="">Semua Provinsi</option>
            @foreach ($provinces as $province)
                <option value="{{ $province->id }}" {{ request('province') == $province->id ? 'selected' : '' }}>
                    {{ ucwords(strtolower($province->name)) }}
                </option>
            @endforeach
        </x-select>

        {{-- City Select --}}
        <div id="citySelectWrapper" style="{{ request('province') ? '' : 'display: none;' }}">
            <x-select name="city" id="city"
                class="block py-1.5 pr-10 pl-3 mt-2 w-full text-gray-900 dark:text-white dark:bg-gray-800 rounded-md border-0 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6">
                <option value="">Semua Kota</option>
                @foreach ($cities as $city)
                    <option value="{{ $city->id }}" {{ request('city') == $city->id ? 'selected' : '' }}>
                        {{ ucwords(strtolower($city->name)) }}
                    </option>
                @endforeach
            </x-select>
        </div>

        {{-- Current Select --}}
        <x-select name="current" id="current"
            class="block py-1.5 pr-10 pl-3 mt-2 w-full text-gray-900 dark:text-white dark:bg-gray-800 rounded-md border-0 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6">
            <option value="">Semua Arus</option>
            @foreach ($currentChargers as $current)
                <option value="{{ $current->id }}" {{ request('current') == $current->id ? 'selected' : '' }}>
                    {{ $current->name }}
                </option>
            @endforeach
        </x-select>

        {{-- Type Select --}}
        <div id="typeSelectWrapper" style="{{ request('current') ? '' : 'display: none;' }}">
            <x-select name="type" id="type"
                class="block py-1.5 pr-10 pl-3 mt-2 w-full text-gray-900 dark:text-white dark:bg-gray-800 rounded-md border-0 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6">
                <option value="">Semua Tipe</option>
                @foreach ($typeChargers as $type)
                    <option value="{{ $type->id }}" {{ request('type') == $type->id ? 'selected' : '' }}>
                        {{ $type->name }}
                    </option>
                @endforeach
            </x-select>
        </div>

        {{-- Power Select --}}
        <div id="powerSelectWrapper" style="{{ request('type') ? '' : 'display: none;' }}">
            <x-select name="power" id="power"
                class="block py-1.5 pr-10 pl-3 mt-2 w-full text-gray-900 dark:text-white dark:bg-gray-800 rounded-md border-0 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6">
                <option value="">Semua Daya</option>
                @foreach ($powerChargers as $power)
                    <option value="{{ $power->id }}" {{ request('power') == $power->id ? 'selected' : '' }}>
                        {{ $power->name }}
                    </option>
                @endforeach
            </x-select>
        </div>

        {{-- Provider Select --}}
        <x-select name="provider" id="provider"
            class="block py-1.5 pr-10 pl-3 mt-2 w-full text-gray-900 dark:text-white dark:bg-gray-800 rounded-md border-0 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6">
            <option value="">Semua Provider</option>
            @foreach ($providers as $provider)
                <option value="{{ $provider->id }}" {{ request('provider') == $provider->id ? 'selected' : '' }}>
                    {{ $provider->name }}
                </option>
            @endforeach
        </x-select>

        {{-- Rest Area Select --}}
        <x-select name="rest_area" id="rest_area"
            class="block py-1.5 pr-10 pl-3 mt-2 w-full text-gray-900 dark:text-white dark:bg-gray-800 rounded-md border-0 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6">
            <option value="">Rest Area dan Non Rest Area</option>
            <option value="1" {{ request('rest_area') == '1' ? 'selected' : '' }}>Rest Area</option>
            <option value="0" {{ request('rest_area') == '0' ? 'selected' : '' }}>Bukan Rest Area</option>
        </x-select>
    </div>

    <div class="flex justify-end">
        <x-button type="button" id="clearFilter"
            class="px-4 py-2 text-sm font-medium text-white bg-gray-600 dark:bg-gray-700 rounded-md hover:bg-gray-700 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
            Reset Filter
        </x-button>
    </div>
</form>
