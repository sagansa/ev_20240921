<x-filament-panels::page>
    <div class="space-y-6" x-data>
        {{-- Upload --}}
        <x-filament::section>
            <x-slot name="heading">File CONNECTING (CSV)</x-slot>

            <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
                Alur: <strong>Verifikasi</strong> (dry-run, tidak menulis apa pun) →
                <strong>Jalankan Sinkronisasi</strong> (tabel connecting → katalog → backfill kategori → flush cache).
                Semua langkah idempoten — aman diulang.
            </p>

            <div class="flex flex-wrap items-end gap-4">
                <label class="flex flex-col gap-1 text-sm text-gray-500 dark:text-gray-400">
                    File CSV
                    <input type="file" accept=".csv" wire:model.live="csvFile"
                           class="fi-input rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-950 dark:border-white/10 dark:bg-white/5 dark:text-white" />
                </label>

                <button type="button" wire:click="verify" wire:loading.attr="disabled" wire:target="verify"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-950 hover:bg-gray-50 disabled:opacity-50 dark:border-white/10 dark:text-white dark:hover:bg-white/5">
                    <span wire:loading.remove wire:target="verify">1 · Verifikasi</span>
                    <span wire:loading wire:target="verify">Memeriksa…</span>
                </button>

                <button type="button" wire:click="sync" wire:loading.attr="disabled" wire:target="sync"
                        wire:confirm="Sinkronisasi akan MENULIS ke katalog & tabel connecting. Lanjutkan?"
                        class="rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-600 disabled:opacity-50">
                    <span wire:loading.remove wire:target="sync">2 · Jalankan Sinkronisasi</span>
                    <span wire:loading wire:target="sync">Menyinkronkan…</span>
                </button>
            </div>

            @error('csvFile')<p class="mt-3 text-sm text-danger-500">{{ $message }}</p>@enderror
            @if ($error)
                <p class="mt-3 rounded-lg bg-danger-50 px-3 py-2 text-sm text-danger-600 dark:bg-danger-500/10 dark:text-danger-400">{{ $error }}</p>
            @endif
        </x-filament::section>

        {{-- Hasil verifikasi --}}
        @if ($report !== null)
            <x-filament::section>
                <x-slot name="heading">Hasil Verifikasi (dry-run)</x-slot>

                <div class="grid grid-cols-2 gap-4 md:grid-cols-6">
                    @foreach ([
                        ['✓ Sama', $report['match'] !== [] ? count($report['match']) : 0, 'text-success-500'],
                        ['Brand Baru', count($report['brandBaru']), 'text-amber-500'],
                        ['Model Baru', count($report['modelBaru']), 'text-amber-500'],
                        ['Type Baru', count($report['typeBaru']), 'text-amber-500'],
                        ['Klasifikasi Beda', count($report['klasifikasiBeda']), 'text-warning-500'],
                        ['DB tak ada di CSV', count($report['dbBrandTanpaCsv']) + count($report['dbModelTanpaCsv']), 'text-danger-500'],
                    ] as [$label, $value, $color])
                        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $label }}</p>
                            <p class="text-2xl font-bold {{ $color }}">{{ number_format($value) }}</p>
                        </div>
                    @endforeach
                </div>

                @if (count($report['brandBaru']) > 0 || count($report['modelBaru']) > 0 || count($report['typeBaru']) > 0)
                    <div class="mt-4">
                        <p class="mb-2 text-xs font-bold uppercase text-gray-400">Entitas baru (akan dibuat saat sinkronisasi)</p>
                        <div class="max-h-56 overflow-y-auto rounded-lg border border-gray-200 dark:border-white/10">
                            <table class="w-full text-sm">
                                <thead><tr class="border-b border-gray-200 text-left text-gray-500 dark:border-white/10 dark:text-gray-400">
                                    <th class="px-3 py-2">Brand</th><th class="px-3 py-2">Model</th><th class="px-3 py-2">Type</th><th class="px-3 py-2">Kategori</th>
                                </tr></thead>
                                <tbody>
                                @foreach ($report['brandBaru'] as $r)
                                    <tr class="border-b border-gray-100 last:border-0 dark:border-white/5">
                                        <td class="px-3 py-1.5 font-mono text-xs">{{ $r['brand'] }}</td>
                                        <td class="px-3 py-1.5">{{ $r['model'] }}</td>
                                        <td class="px-3 py-1.5 text-gray-400">{{ $r['type'] ?: '—' }}</td>
                                        <td class="px-3 py-1.5">{{ $r['category'] ?: '—' }}</td>
                                    </tr>
                                @endforeach
                                @foreach ($report['modelBaru'] as $r)
                                    <tr class="border-b border-gray-100 last:border-0 dark:border-white/5">
                                        <td class="px-3 py-1.5 font-mono text-xs">{{ $r['brand'] }}</td>
                                        <td class="px-3 py-1.5">{{ $r['model'] }}</td>
                                        <td class="px-3 py-1.5 text-gray-400">{{ $r['type'] ?: '—' }}</td>
                                        <td class="px-3 py-1.5">{{ $r['category'] ?: '—' }}</td>
                                    </tr>
                                @endforeach
                                @foreach ($report['typeBaru'] as $r)
                                    <tr class="border-b border-gray-100 last:border-0 dark:border-white/5">
                                        <td class="px-3 py-1.5 font-mono text-xs">{{ $r['brand'] }}</td>
                                        <td class="px-3 py-1.5">{{ $r['model'] }}</td>
                                        <td class="px-3 py-1.5">{{ $r['type'] }}</td>
                                        <td class="px-3 py-1.5">{{ ($r['category'] ?? '—') ?: '—' }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                @if (count($report['klasifikasiBeda']) > 0)
                    <div class="mt-4">
                        <p class="mb-2 text-xs font-bold uppercase text-gray-400">Klasifikasi beda (akan disamakan saat sinkronisasi)</p>
                        <div class="max-h-56 overflow-y-auto rounded-lg border border-gray-200 dark:border-white/10">
                            <table class="w-full text-sm">
                                <tbody>
                                @foreach ($report['klasifikasiBeda'] as $row)
                                    <tr class="border-b border-gray-100 last:border-0 dark:border-white/5">
                                        <td class="px-3 py-1.5 font-mono text-xs">{{ $row['brand'] }}</td>
                                        <td class="px-3 py-1.5">{{ $row['model'] }}</td>
                                        <td class="px-3 py-1.5 text-xs text-gray-500">{{ $row['diff'] }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </x-filament::section>
        @endif

        {{-- Log sinkronisasi --}}
        @if (count($log) > 0)
            <x-filament::section>
                <x-slot name="heading">Hasil Sinkronisasi</x-slot>
                <ul class="space-y-1 text-sm text-gray-700 dark:text-gray-300">
                    @foreach ($log as $line)
                        <li>• {{ $line }}</li>
                    @endforeach
                </ul>
                @if (($syncResult['catalog']['failed'] ?? []) !== [])
                    <p class="mt-3 text-xs text-danger-500">Baris gagal: {{ count($syncResult['catalog']['failed']) }} — cek laporan impor.</p>
                @endif
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
