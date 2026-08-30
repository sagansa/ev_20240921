<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Indikator kesehatan hubungan --}}
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                <p class="text-xs text-gray-500 dark:text-gray-400">Model tanpa kategori</p>
                <p class="text-2xl font-bold {{ $report['modelsWithoutCategory'] > 0 ? 'text-amber-500' : 'text-success-500' }}">
                    {{ $report['modelsWithoutCategory'] }}
                </p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                <p class="text-xs text-gray-500 dark:text-gray-400">Baris stats tak ter-link ({{ $report['year'] }})</p>
                <p class="text-2xl font-bold {{ count($report['unlinked']) > 0 ? 'text-danger-500' : 'text-success-500' }}">
                    {{ count($report['unlinked']) }} <span class="text-sm font-normal">brand</span>
                </p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                <p class="text-xs text-gray-500 dark:text-gray-400">Type yatim (model hilang)</p>
                <p class="text-2xl font-bold {{ $report['orphanTypes'] > 0 ? 'text-danger-500' : 'text-success-500' }}">
                    {{ $report['orphanTypes'] }}
                </p>
            </div>
        </div>

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
                        @forelse ($report['years'] as $yearOption)
                            <option value="{{ $yearOption }}" @selected((int) $year === (int) $yearOption)>{{ $yearOption }}</option>
                        @empty
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforelse
                    </select>
                </label>

                <label class="flex flex-col gap-1 text-sm text-gray-500 dark:text-gray-400">
                    Powertrain
                    <select wire:model.live="powertrain"
                            class="fi-input min-w-32 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-950 dark:border-white/10 dark:bg-white/5 dark:text-white">
                        <option value="ALL" @selected($powertrain === 'ALL')>Semua</option>
                        @foreach (['BEV', 'PHEV', 'HEV', 'ICE'] as $pt)
                            <option value="{{ $pt }}" @selected($powertrain === $pt)>{{ $pt }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="flex flex-col gap-1 text-sm text-gray-500 dark:text-gray-400">
                    Kategori
                    <select wire:model.live="category"
                            class="fi-input min-w-40 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-950 dark:border-white/10 dark:bg-white/5 dark:text-white">
                        <option value="ALL">Semua kategori</option>
                        @foreach ($categoryOptions as $opt)
                            <option value="{{ $opt }}" @selected($category === $opt)>{{ $opt }}</option>
                        @endforeach
                    </select>
                </label>

                <span class="text-sm text-gray-500 dark:text-gray-400">
                    Total unit {{ $report['year'] }}:
                    <strong class="text-gray-950 dark:text-white">{{ number_format($report['totals']['units']) }}</strong>
                    @if ($report['totals']['prevUnits'] > 0)
                        @php $delta = $report['totals']['units'] - $report['totals']['prevUnits']; @endphp
                        <span class="{{ $delta >= 0 ? 'text-success-500' : 'text-danger-500' }}">
                            ({{ $delta >= 0 ? '+' : '' }}{{ number_format($delta) }} vs {{ (int) $report['year'] - 1 }})
                        </span>
                    @endif
                </span>

                <div class="ml-auto flex gap-2">
                    <button type="button" wire:click="expandAll"
                            class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs dark:border-white/10 dark:text-gray-300">
                        Buka semua
                    </button>
                    <button type="button" wire:click="collapseAll"
                            class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs dark:border-white/10 dark:text-gray-300">
                        Tutup semua
                    </button>
                </div>
            </div>
        </x-filament::section>

        {{-- Pohon hierarki --}}
        <x-filament::section>
            <x-slot name="heading">
                Brand · Model · Type — {{ $report['year'] }}
            </x-slot>

            <div class="space-y-2">
                @forelse ($report['brands'] as $brand)
                    @php
                        $brandOpen = in_array('b'.$brand['id'], $expanded['brands'], true);
                        $brandDelta = $brand['units'] - $brand['prev_units'];
                    @endphp

                    <div class="rounded-lg border border-gray-200 dark:border-white/10">
                        <button type="button" wire:click="toggleBrand({{ $brand['id'] }})"
                                class="flex w-full items-center gap-3 px-4 py-3 text-left hover:bg-gray-50 dark:hover:bg-white/5">
                            <span class="text-xs text-gray-400">{{ $brandOpen ? '▼' : '▶' }}</span>
                            <span class="font-semibold text-gray-950 dark:text-white">{{ $brand['name'] }}</span>
                            <span class="ml-auto text-sm text-gray-500 dark:text-gray-400">
                                <strong class="text-gray-950 dark:text-white">{{ number_format($brand['units']) }}</strong> unit
                                @if ($brand['prev_units'] > 0)
                                    <span class="{{ $brandDelta >= 0 ? 'text-success-500' : 'text-danger-500' }}">
                                        {{ $brandDelta >= 0 ? '▲+' : '▼' }}{{ number_format(abs($brandDelta)) }}
                                    </span>
                                @endif
                                <span class="ml-2 text-xs">{{ count($brand['models']) }} model</span>
                            </span>
                        </button>

                        @if ($brandOpen)
                            <div class="border-t border-gray-200 px-4 pb-3 pt-2 dark:border-white/10">
                                @forelse ($brand['models'] as $model)
                                    @php
                                        $modelOpen = in_array('m'.$model['id'], $expanded['models'], true);
                                        $modelDelta = $model['units'] - $model['prev_units'];
                                    @endphp

                                    <div class="mb-1 ml-4 rounded-md {{ $model['has_issue'] ? 'border border-amber-300 dark:border-amber-500/40' : '' }}">
                                        <button type="button" wire:click="toggleModel({{ $model['id'] }})"
                                                class="flex w-full items-center gap-3 px-3 py-2 text-left text-sm hover:bg-gray-50 dark:hover:bg-white/5">
                                            <span class="text-[10px] text-gray-400">{{ $modelOpen ? '▼' : '▶' }}</span>
                                            <span class="font-medium text-gray-950 dark:text-white">{{ $model['name'] }}</span>
                                            @if ($model['category'])
                                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] text-gray-600 dark:bg-white/10 dark:text-gray-300">
                                                    {{ $model['category'] }}{{ $model['size'] ? ' · '.$model['size'] : '' }}
                                                </span>
                                            @else
                                                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] text-amber-700 dark:bg-amber-500/20 dark:text-amber-300"
                                                      title="Kategori belum diisi — perbaiki lewat CONNECTING → backfill">
                                                    ⚠ tanpa kategori
                                                </span>
                                            @endif
                                            <span class="ml-auto text-gray-500 dark:text-gray-400">
                                                <strong class="text-gray-950 dark:text-white">{{ number_format($model['units']) }}</strong> unit
                                                @if ($model['prev_units'] > 0)
                                                    <span class="{{ $modelDelta >= 0 ? 'text-success-500' : 'text-danger-500' }}">
                                                        {{ $modelDelta >= 0 ? '▲+' : '▼' }}{{ number_format(abs($modelDelta)) }}
                                                    </span>
                                                @endif
                                                <span class="ml-2 text-xs">{{ $model['type_count'] }} type</span>
                                            </span>
                                        </button>

                                        @if ($modelOpen && $model['types'] !== [])
                                            <div class="ml-10 border-l border-gray-200 pb-2 pl-3 dark:border-white/10">
                                                @foreach ($model['types'] as $type)
                                                    <div class="flex items-center gap-2 py-1 text-xs text-gray-500 dark:text-gray-400">
                                                        <span class="text-gray-300 dark:text-gray-600">└</span>
                                                        {{ $type['name'] }}
                                                        <span class="ml-auto">{{ number_format($type['units']) }} unit</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @elseif ($modelOpen)
                                            <p class="ml-10 pb-2 text-xs text-gray-400">Tidak ada type terdaftar.</p>
                                        @endif
                                    </div>
                                @empty
                                    <p class="py-2 text-sm text-gray-400">Tidak ada model pada filter ini.</p>
                                @endforelse
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="py-4 text-sm text-gray-400">Tidak ada data pada filter ini.</p>
                @endforelse
            </div>
        </x-filament::section>

        {{-- Stats tak ter-link --}}
        @if (count($report['unlinked']) > 0)
            <x-filament::section>
                <x-slot name="heading">
                    <span class="text-danger-500">Stats tak ter-link — {{ $report['year'] }}</span>
                </x-slot>

                <p class="mb-3 text-xs text-gray-500 dark:text-gray-400">
                    Raw brand ini tidak tercocokkan ke katalog (link NULL). Lihat
                    <code>vehicle-sales:preview</code>, perbaiki alias / CONNECTING, lalu re-import.
                </p>

                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-left text-gray-500 dark:border-white/10 dark:text-gray-400">
                            <th class="px-4 py-2 font-medium">Raw brand</th>
                            <th class="px-4 py-2 font-medium">Jumlah model</th>
                            <th class="px-4 py-2 font-medium">Unit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($report['unlinked'] as $row)
                            <tr class="border-b border-gray-100 last:border-0 dark:border-white/5">
                                <td class="px-4 py-2 font-mono text-xs">{{ $row['brand'] }}</td>
                                <td class="px-4 py-2">{{ $row['models'] }}</td>
                                <td class="px-4 py-2">{{ number_format($row['units']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
