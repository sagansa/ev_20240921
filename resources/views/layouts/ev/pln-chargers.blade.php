@extends('layouts.main')

@section('title', 'PLN Chargers - EV Charger')

@section('content')
    <div class="p-8">
        <h2 class="mb-6 text-3xl font-bold text-ev-blue-800 dark:text-ev-blue-400">PLN Chargers</h2>

        <div class="flow-root mt-8">
            <div class="overflow-x-auto -mx-4 -my-2 sm:-mx-6 lg:-mx-8">
                <div class="inline-block py-2 min-w-full align-middle sm:px-6 lg:px-8">
                    <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
                        <thead>
                            <tr>
                                <x-table.header>Lokasi</x-table.header>
                                <x-table.header>Provider</x-table.header>
                                <x-table.header>Kategori</x-table.header>
                                <x-table.header>Merk</x-table.header>
                                <x-table.header>Daya</x-table.header>
                                <x-table.header>Jmlh Konektor (Unit)</x-table.header>
                                <x-table.header>Status</x-table.header>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($plnChargerDetails as $detail)
                                @php
                                    $locationName = data_get($detail, 'plnChargerLocation.name');
                                    $providerName = data_get($detail, 'plnChargerLocation.provider.name');
                                    $categoryName = data_get($detail, 'plnChargerLocation.locationCategory.name');
                                    $merkName = data_get($detail, 'merkCharger.name');

                                    $powerRaw = $detail->power;
                                    $hasPower = ! is_null($powerRaw) && $powerRaw !== '';
                                    $powerValue = $hasPower ? (float) $powerRaw : null;
                                    $powerDisplay = $hasPower
                                        ? rtrim(rtrim(number_format($powerValue, 2, '.', ''), '0'), '.') . ' kW'
                                        : 'N/A';

                                    $connectorCount = is_numeric($detail->count_connector_charger)
                                        ? (int) $detail->count_connector_charger
                                        : 0;

                                    $isActive = (bool) data_get($detail, 'is_active_charger');
                                    $statusClasses = $isActive
                                        ? 'text-green-700 bg-green-100 dark:text-green-300 dark:bg-green-900'
                                        : 'text-red-700 bg-red-100 dark:text-red-300 dark:bg-red-900';
                                    $statusLabel = $isActive ? 'Aktif' : 'Tidak Aktif';

                                    $operationDate = 'N/A';
                                    if (! empty($detail->operation_date)) {
                                        try {
                                            $operationDate = \Illuminate\Support\Carbon::parse($detail->operation_date)->translatedFormat('d M Y');
                                        } catch (\Throwable $exception) {
                                            $operationDate = 'N/A';
                                        }
                                    }

                                    $year = $detail->year ?? 'N/A';
                                @endphp

                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <x-table.cell wrap variant="emphasis">
                                        {{ $locationName ?? 'N/A' }}
                                    </x-table.cell>
                                    <x-table.cell variant="normal">
                                        {{ $providerName ?? 'N/A' }}
                                    </x-table.cell>
                                    <x-table.cell>
                                        {{ $categoryName ?? 'N/A' }}
                                    </x-table.cell>
                                    <x-table.cell>
                                        {{ $merkName ?? 'N/A' }}
                                    </x-table.cell>
                                    <x-table.cell variant="normal">
                                        {{ $powerDisplay }}
                                    </x-table.cell>
                                    <x-table.cell variant="normal" class="text-center">
                                        {{ $connectorCount }}
                                    </x-table.cell>
                                    <x-table.cell variant="plain" class="text-center">
                                        <span class="px-2 py-1 text-sm font-medium rounded-full {{ $statusClasses }}">
                                            {{ $statusLabel }}
                                        </span>
                                    </x-table.cell>
                                </tr>
                            @empty
                                <tr>
                                    <x-table.cell colspan="9" wrap variant="plain"
                                        class="text-center text-gray-500 dark:text-gray-400">
                                        Data charger PLN tidak tersedia.
                                    </x-table.cell>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if ($plnChargerDetails->hasPages())
            <div class="mt-4">
                {{ $plnChargerDetails->links() }}
            </div>
        @endif
    </div>
@endsection
