<x-filament-panels::page>
    <div class="space-y-6" x-data="{ downloading: false }">
        <x-filament::section>
            <x-slot name="heading">
                Upload Laporan Penjualan (CSV GAIKINDO)
            </x-slot>

            <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
                Read-only: file dianalisis terhadap katalog base (brand → model) — tidak ada data yang ditulis.
                Kombinasi BEV yang belum ada di katalog dilaporkan agar bisa diputuskan dulu
                (varian existing → alias / benar-benar baru → masuk CONNECTING + kategori → impor hierarki).
            </p>

            <div class="flex flex-wrap items-end gap-4">
                <label class="flex flex-col gap-1 text-sm text-gray-500 dark:text-gray-400">
                    File CSV
                    <input type="file" accept=".csv"
                           wire:model.live="csvFile"
                           class="fi-input rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-950 dark:border-white/10 dark:bg-white/5 dark:text-white" />
                </label>

                <label class="flex flex-col gap-1 text-sm text-gray-500 dark:text-gray-400">
                    Periode
                    <select wire:model.live="month"
                            class="fi-input min-w-40 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-950 dark:border-white/10 dark:bg-white/5 dark:text-white">
                        <option value="">Tahunan (kolom JAN..DEC)</option>
                        @foreach (['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'] as $i => $name)
                            <option value="{{ $i + 1 }}" @selected($month === $i + 1)>{{ $name }} (kolom bulan / UNITS)</option>
                        @endforeach
                    </select>
                </label>

                <button type="button" wire:click="analyze" wire:loading.attr="disabled"
                        class="rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-600 disabled:opacity-50">
                    <span wire:loading.remove wire:target="analyze">Analisis</span>
                    <span wire:loading wire:target="analyze">Menganalisis…</span>
                </button>
            </div>

            @error('csvFile')
                <p class="mt-3 text-sm text-danger-500">{{ $message }}</p>
            @enderror

            @if ($error)
                <p class="mt-3 rounded-lg bg-danger-50 px-3 py-2 text-sm text-danger-600 dark:bg-danger-500/10 dark:text-danger-400">
                    {{ $error }}
                </p>
            @endif
        </x-filament::section>

        @if ($result !== null)
            @php $s = $result['summary']; @endphp

            <div class="grid grid-cols-2 gap-4 md:grid-cols-5">
                <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Baris dibaca</p>
                    <p class="text-2xl font-bold text-gray-950 dark:text-white">{{ number_format($s['rows']) }}</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Dilewati (junk)</p>
                    <p class="text-2xl font-bold text-gray-950 dark:text-white">{{ number_format($s['skipped']) }}</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Non-BEV (tanpa link, by design)</p>
                    <p class="text-2xl font-bold text-gray-950 dark:text-white">{{ number_format($s['nonBev']) }}</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Ter-match katalog</p>
                    <p class="text-2xl font-bold text-success-500">{{ number_format($s['matched']) }}</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                    <p class="text-xs text-gray-500 dark:text-gray-400">BARU (perlu keputusan)</p>
                    <p class="text-2xl font-bold {{ $s['new'] > 0 ? 'text-amber-500' : 'text-success-500' }}">{{ number_format($s['new']) }}</p>
                </div>
            </div>

            @if ($s['new'] > 0)
                <x-filament::section>
                    <x-slot name="heading">
                        Kombinasi BARU terhadap katalog base
                    </x-slot>

                    <div class="mb-4 overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 text-left text-gray-500 dark:border-white/10 dark:text-gray-400">
                                    <th class="px-4 py-2 font-medium">Brand (laporan)</th>
                                    <th class="px-4 py-2 font-medium">Model</th>
                                    <th class="px-4 py-2 font-medium">Type</th>
                                    <th class="px-4 py-2 font-medium">Powertrain</th>
                                    <th class="px-4 py-2 font-medium">Unit</th>
                                    <th class="px-4 py-2 font-medium">Status katalog</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($result['new'] as $row)
                                    <tr class="border-b border-gray-100 last:border-0 dark:border-white/5">
                                        <td class="px-4 py-2 font-mono text-xs">{{ $row['brand'] }}</td>
                                        <td class="px-4 py-2">{{ $row['model'] }}</td>
                                        <td class="px-4 py-2 text-gray-500">{{ $row['type'] ?: '—' }}</td>
                                        <td class="px-4 py-2">{{ $row['powertrain'] }}</td>
                                        <td class="px-4 py-2">{{ number_format($row['units']) }}</td>
                                        <td class="px-4 py-2">
                                            @if ($row['brand_name'])
                                                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-700 dark:bg-amber-500/20 dark:text-amber-300">
                                                    model baru di {{ $row['brand_name'] }}
                                                </span>
                                            @else
                                                <span class="rounded-full bg-danger-50 px-2 py-0.5 text-xs text-danger-600 dark:bg-danger-500/10 dark:text-danger-400">
                                                    brand baru
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <button type="button" wire:click="downloadNew"
                            class="rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-600">
                        Unduh CSV → CONNECTING ({{ count($result['new']) }} baris, kategori diisi manual)
                    </button>
                </x-filament::section>


            @else
                <x-filament::section>
                    <p class="py-2 text-sm text-success-600 dark:text-success-400">
                        ✓ Semua kombinasi BEV sudah ter-match ke katalog — file aman diimpor
                        (vehicle-sales:import-csv --require-full-link).
                    </p>
                </x-filament::section>
            @endif

                {{-- Simpan mapping eksplisit --}}
                <x-filament::section>
                    <x-slot name="heading">
                        Simpan Mapping Eksplisit
                    </x-slot>

                    <p class="mb-3 text-xs text-gray-500 dark:text-gray-400">
                        Petakan nama mentah → katalog sekali, tersimpan permanen: semua impor berikutnya
                        otomatis ter-link tanpa tebakan. Katalog tujuan harus sudah ada.
                    </p>

                    <div class="grid grid-cols-1 gap-3 md:grid-cols-5">
                        <label class="flex flex-col gap-1 text-xs text-gray-500 dark:text-gray-400">
                            Raw Brand (laporan)
                            <input type="text" wire:model="mapRawBrand" placeholder="WULING-DBG"
                                   class="fi-input rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-950 dark:border-white/10 dark:bg-white/5 dark:text-white" />
                        </label>
                        <label class="flex flex-col gap-1 text-xs text-gray-500 dark:text-gray-400">
                            Raw Model (laporan)
                            <input type="text" wire:model="mapRawModel" placeholder="Air EV Baru"
                                   class="fi-input rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-950 dark:border-white/10 dark:bg-white/5 dark:text-white" />
                        </label>
                        <label class="flex flex-col gap-1 text-xs text-gray-500 dark:text-gray-400">
                            → Brand Katalog
                            <input type="text" wire:model="mapBrandName" placeholder="Wuling"
                                   class="fi-input rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-950 dark:border-white/10 dark:bg-white/5 dark:text-white" />
                        </label>
                        <label class="flex flex-col gap-1 text-xs text-gray-500 dark:text-gray-400">
                            → Model Katalog
                            <input type="text" wire:model="mapModelName" placeholder="Air EV"
                                   class="fi-input rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-950 dark:border-white/10 dark:bg-white/5 dark:text-white" />
                        </label>
                        <button type="button" wire:click="saveMapping"
                                class="self-end rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700 dark:bg-white dark:text-gray-950">
                            Simpan Mapping
                        </button>
                    </div>

                    <label class="mt-3 flex flex-col gap-1 text-xs text-gray-500 dark:text-gray-400">
                        Catatan (opsional)
                        <input type="text" wire:model="mapCatatan"
                               class="fi-input rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-950 dark:border-white/10 dark:bg-white/5 dark:text-white" />
                    </label>

                    @if ($mapMessage)
                        <p class="mt-3 text-sm {{ str_starts_with($mapMessage, '✓') ? 'text-success-500' : 'text-danger-500' }}">
                            {{ $mapMessage }}
                        </p>
                    @endif
                    @error('mapRawBrand')<p class="mt-1 text-xs text-danger-500">{{ $message }}</p>@enderror
                    @error('mapRawModel')<p class="mt-1 text-xs text-danger-500">{{ $message }}</p>@enderror
                    @error('mapBrandName')<p class="mt-1 text-xs text-danger-500">{{ $message }}</p>@enderror
                    @error('mapModelName')<p class="mt-1 text-xs text-danger-500">{{ $message }}</p>@enderror
                </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
