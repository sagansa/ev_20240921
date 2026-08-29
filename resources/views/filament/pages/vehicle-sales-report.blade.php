<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Filter --}}
        <x-filament::section>
            <x-slot name="heading">
                Filter
            </x-slot>

            <div class="flex flex-wrap items-end gap-4">
                <label class="flex flex-col gap-1 text-sm text-gray-500 dark:text-gray-400">
                    Tahun
                    <select wire:model.live="year"
                            class="fi-input min-w-32 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-950 dark:border-white/10 dark:bg-white/5 dark:text-white">
                        @forelse ($years as $yearOption)
                            <option value="{{ $yearOption }}" @selected((int) $year === (int) $yearOption)>{{ $yearOption }}</option>
                        @empty
                            <option value="">—</option>
                        @endforelse
                    </select>
                </label>

                <label class="flex flex-col gap-1 text-sm text-gray-500 dark:text-gray-400">
                    Powertrain
                    <select wire:model.live="powertrain"
                            class="fi-input min-w-32 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-950 dark:border-white/10 dark:bg-white/5 dark:text-white">
                        <option value="ALL" @selected($powertrain === 'ALL')>Semua</option>
                        <option value="BEV" @selected($powertrain === 'BEV')>BEV</option>
                        <option value="PHEV" @selected($powertrain === 'PHEV')>PHEV</option>
                        <option value="HEV" @selected($powertrain === 'HEV')>HEV</option>
                        <option value="ICE" @selected($powertrain === 'ICE')>ICE</option>
                    </select>
                </label>

                <span class="text-sm text-gray-500 dark:text-gray-400">
                    Total unit: <strong class="text-gray-950 dark:text-white">{{ number_format($totalUnits) }}</strong>
                </span>
            </div>
        </x-filament::section>

        {{-- Per Brand --}}
        <x-filament::section>
            <x-slot name="heading">
                Penjualan per Brand — {{ $year ?? '—' }}
            </x-slot>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-left text-gray-500 dark:border-white/10 dark:text-gray-400">
                            <th class="px-4 py-2 font-medium">Brand</th>
                            <th class="px-4 py-2 font-medium text-right">Total Unit</th>
                            <th class="px-4 py-2 font-medium text-right">Jumlah Model</th>
                            <th class="px-4 py-2 font-medium text-right">Jumlah Type</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @forelse ($brandRows as $row)
                            <tr>
                                <td class="px-4 py-2 {{ $row['brand'] === '(tidak ter-match)' ? 'italic text-gray-400 dark:text-gray-500' : 'font-medium text-gray-950 dark:text-white' }}">
                                    {{ $row['brand'] }}
                                </td>
                                <td class="px-4 py-2 text-right text-gray-950 dark:text-white">{{ number_format($row['total_units']) }}</td>
                                <td class="px-4 py-2 text-right text-gray-500 dark:text-gray-400">{{ number_format($row['model_count']) }}</td>
                                <td class="px-4 py-2 text-right text-gray-500 dark:text-gray-400">{{ number_format($row['type_count']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-gray-400 dark:text-gray-500">Tidak ada data.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        {{-- Per Model --}}
        <x-filament::section>
            <x-slot name="heading">
                Penjualan per Model — {{ $year ?? '—' }}
            </x-slot>
            <x-slot name="description">
                Ditampilkan maksimal {{ $modelRowLimit }} model teratas (urut total unit). @if (count($modelRows) >= $modelRowLimit) Tabel terpotong. @endif
            </x-slot>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-left text-gray-500 dark:border-white/10 dark:text-gray-400">
                            <th class="px-4 py-2 font-medium">Brand</th>
                            <th class="px-4 py-2 font-medium">Model</th>
                            <th class="px-4 py-2 font-medium">Powertrain</th>
                            <th class="px-4 py-2 font-medium text-right">Total Unit</th>
                            <th class="px-4 py-2 font-medium text-right">Jumlah Type</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @forelse ($modelRows as $row)
                            <tr>
                                <td class="px-4 py-2 text-gray-950 dark:text-white">{{ $row['brand'] }}</td>
                                <td class="px-4 py-2 {{ $row['model'] === '(tidak ter-match)' ? 'italic text-gray-400 dark:text-gray-500' : 'font-medium text-gray-950 dark:text-white' }}">
                                    {{ $row['model'] }}
                                </td>
                                <td class="px-4 py-2">
                                    <span class="fi-badge rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-200 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10">
                                        {{ $row['powertrain'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-right text-gray-950 dark:text-white">{{ number_format($row['total_units']) }}</td>
                                <td class="px-4 py-2 text-right text-gray-500 dark:text-gray-400">{{ number_format($row['type_count']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-gray-400 dark:text-gray-500">Tidak ada data.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
