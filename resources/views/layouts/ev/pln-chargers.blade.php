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
                                <x-table.header>Jumlah Konektor</x-table.header>
                                <x-table.header>Status</x-table.header>
                                <x-table.header>Tanggal Operasi</x-table.header>
                                <x-table.header>Tahun</x-table.header>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($plnChargerDetails as $detail)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <x-table.cell>{{ $detail->plnChargerLocation->name ?? 'N/A' }}</x-table.cell>
                                    <x-table.cell>{{ $detail->plnChargerLocation->provider->name ?? 'N/A' }}</x-table.cell>
                                    <x-table.cell>{{ $detail->chargerCategory->name ?? 'N/A' }}</x-table.cell>
                                    <x-table.cell>{{ $detail->merkCharger->name ?? 'N/A' }}</x-table.cell>
                                    <x-table.cell>{{ $detail->power ?? 'N/A' }} kW</x-table.cell>
                                    <x-table.cell>{{ $detail->count_connector_charger ?? '0' }}</x-table.cell>
                                    <x-table.cell>
                                        <span @class([
                                            'px-2 py-1 text-sm font-medium rounded-full',
                                            'text-green-700 bg-green-100 dark:text-green-300 dark:bg-green-900' =>
                                                $detail->is_active_charger,
                                            'text-red-700 bg-red-100 dark:text-red-300 dark:bg-red-900' => !$detail->is_active_charger,
                                        ])>
                                            {{ $detail->is_active_charger ? 'Aktif' : 'Tidak Aktif' }}
                                        </span>
                                    </x-table.cell>
                                    <x-table.cell>{{ $detail->operation_date ? date('d M Y', strtotime($detail->operation_date)) : 'N/A' }}</x-table.cell>
                                    <x-table.cell>{{ $detail->year ?? 'N/A' }}</x-table.cell>
                                </tr>
                            @endforeach
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
