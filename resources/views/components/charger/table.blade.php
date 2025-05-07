@props(['chargers'])

<div class="flow-root mt-8">
    <div class="overflow-x-auto -mx-4 -my-2 sm:-mx-6 lg:-mx-8">
        <div class="inline-block py-2 min-w-full align-middle sm:px-6 lg:px-8">
            <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
                <thead>
                    <tr>
                        <x-charger.table.header>Map</x-charger.table.header>
                        <x-charger.table.sortable-header name="location">Lokasi</x-charger.table.sortable-header>
                        <x-charger.table.sortable-header name="provider">Provider</x-charger.table.sortable-header>
                        <x-charger.table.sortable-header name="current">Arus</x-charger.table.sortable-header>
                        <x-charger.table.sortable-header name="type">Tipe</x-charger.table.sortable-header>
                        <x-charger.table.sortable-header name="power">Daya</x-charger.table.sortable-header>
                        <x-charger.table.header>Unit</x-charger.table.header>
                        <x-charger.table.sortable-header name="usage">Penggunaan</x-charger.table.sortable-header>
                        <x-charger.table.sortable-header name="city">Kota</x-charger.table.sortable-header>
                        <x-charger.table.sortable-header name="province">Provinsi</x-charger.table.sortable-header>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($chargers as $charger)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <x-charger.table.cell>
                                @if ($charger->chargerLocation && $charger->chargerLocation->latitude && $charger->chargerLocation->longitude)
                                    <a href="https://www.google.com/maps/search/?api=1&query={{ $charger->chargerLocation->latitude }},{{ $charger->chargerLocation->longitude }}"
                                        target="_blank"
                                        class="text-ev-blue-600 hover:text-ev-blue-800 dark:text-ev-blue-400 dark:hover:text-ev-blue-300">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 20 20"
                                            fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </a>
                                @else
                                    N/A
                                @endif
                            </x-charger.table.cell>
                            <x-charger.table.cell>{{ $charger->chargerLocation->name ?? 'N/A' }}</x-charger.table.cell>
                            <x-charger.table.cell>
                                @if ($charger->chargerLocation->provider)
                                    <span class="cursor-pointer provider-info"
                                        data-provider-id="{{ $charger->chargerLocation->provider->id }}">
                                        {{ $charger->chargerLocation->provider->name }}
                                    </span>
                                @else
                                    N/A
                                @endif
                            </x-charger.table.cell>
                            <x-charger.table.cell>{{ $charger->currentCharger->name ?? 'N/A' }}</x-charger.table.cell>
                            <x-charger.table.cell>{{ $charger->typeCharger->name ?? 'N/A' }}</x-charger.table.cell>
                            <x-charger.table.cell>{{ $charger->powerCharger->name ?? 'N/A' }}</x-charger.table.cell>
                            <x-charger.table.cell>{{ $charger->unit ?? 'N/A' }}</x-charger.table.cell>
                            <x-charger.table.cell>
                                <span
                                    class="px-2 py-1 text-sm font-medium text-blue-700 bg-blue-100 rounded-full dark:text-blue-300 dark:bg-blue-900">
                                    {{ $charger->charges_count ?? '0' }} kali
                                </span>
                            </x-charger.table.cell>
                            <x-charger.table.cell>{{ ucwords(strtolower($charger->chargerLocation->city->name ?? 'n/a')) }}</x-charger.table.cell>
                            <x-charger.table.cell>{{ ucwords(strtolower($charger->chargerLocation->province->name ?? 'n/a')) }}</x-charger.table.cell>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-4">
    {{ $chargers->links() }}
</div>
