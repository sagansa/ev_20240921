<x-filament-panels::page>
    <style>
        :root {
            --vcs-bg-card: #ffffff;
            --vcs-bg-header: rgba(0, 0, 0, 0.015);
            --vcs-bg-sub: rgba(0, 0, 0, 0.02);
            --vcs-border: rgba(156, 163, 175, 0.25);
            --vcs-border-sub: rgba(156, 163, 175, 0.15);
            --vcs-text-title: #111827;
            --vcs-text-body: #374151;
            --vcs-text-muted: #6b7280;
            --vcs-input-bg: #ffffff;
            --vcs-input-border: rgba(156, 163, 175, 0.35);
        }

        .dark, [data-theme="dark"] {
            --vcs-bg-card: rgba(255, 255, 255, 0.04);
            --vcs-bg-header: rgba(255, 255, 255, 0.02);
            --vcs-bg-sub: rgba(255, 255, 255, 0.02);
            --vcs-border: rgba(255, 255, 255, 0.1);
            --vcs-border-sub: rgba(255, 255, 255, 0.07);
            --vcs-text-title: #f9fafb;
            --vcs-text-body: #e5e7eb;
            --vcs-text-muted: #9ca3af;
            --vcs-input-bg: rgba(255, 255, 255, 0.05);
            --vcs-input-border: rgba(255, 255, 255, 0.15);
        }

        .vcs-card {
            border: 1px solid var(--vcs-border);
            border-radius: 12px;
            padding: 16px;
            background: var(--vcs-bg-card);
            color: var(--vcs-text-body);
            transition: all 0.15s ease;
        }
        .vcs-grid-6 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 14px;
        }
        .vcs-badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            line-height: 1.3;
        }
        .vcs-badge-success { background: rgba(16, 185, 129, 0.15); color: #059669; }
        .dark .vcs-badge-success, [data-theme="dark"] .vcs-badge-success { background: rgba(16, 185, 129, 0.25); color: #34d399; }
        .vcs-badge-warn { background: rgba(245, 158, 11, 0.18); color: #b45309; }
        .dark .vcs-badge-warn, [data-theme="dark"] .vcs-badge-warn { background: rgba(245, 158, 11, 0.25); color: #fbbf24; }
        .vcs-badge-danger { background: rgba(239, 68, 68, 0.15); color: #dc2626; }
        .dark .vcs-badge-danger, [data-theme="dark"] .vcs-badge-danger { background: rgba(239, 68, 68, 0.25); color: #f87171; }
        .vcs-badge-gray { background: rgba(156, 163, 175, 0.15); color: #4b5563; }
        .dark .vcs-badge-gray, [data-theme="dark"] .vcs-badge-gray { background: rgba(255, 255, 255, 0.08); color: #d1d5db; }
        .vcs-input-control {
            background: var(--vcs-input-bg);
            color: var(--vcs-text-title);
            border: 1px solid var(--vcs-input-border);
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 13px;
            outline: none;
            width: 100%;
        }
        .vcs-input-control:focus {
            border-color: #10b981;
        }
        .vcs-btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 9px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            border: none;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #ffffff;
            transition: all 0.15s ease;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .vcs-btn-primary:hover {
            opacity: 0.92;
        }
        .vcs-btn-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 9px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            border: 1px solid var(--vcs-input-border);
            background: var(--vcs-input-bg);
            color: var(--vcs-text-title);
            transition: all 0.15s ease;
        }
        .vcs-btn-secondary:hover {
            background: rgba(156, 163, 175, 0.15);
        }
    </style>

    <div style="display: flex; flex-direction: column; gap: 24px;" x-data>
        {{-- 1. UPLOAD & SINKRONISASI WORKFLOW --}}
        <x-filament::section>
            <x-slot name="heading">
                Sinkronisasi Master CONNECTING (CSV ke Katalog)
            </x-slot>

            <p style="margin-top: 2px; margin-bottom: 16px; font-size: 13px; color: var(--vcs-text-muted); line-height: 1.5;">
                Alur Kerja 2 Langkah:
                <strong>1. Verifikasi</strong> (Dry-run: memeriksa perubahan entitas & klasifikasi tanpa menulis data) →
                <strong>2. Jalankan Sinkronisasi</strong> (Mengimpor tabel connecting, mendaftarkan brand/model/type baru, backfill kategori/ukuran, dan me-refresh cache pasar).
                Semua operasi bersifat <em>idempoten</em> dan aman diulang kapan saja.
            </p>

            <div style="display: flex; flex-wrap: wrap; align-items: flex-end; gap: 16px;">
                <div style="flex: 1; min-width: 280px;">
                    <label style="display: flex; flex-direction: column; gap: 4px; font-size: 12px; font-weight: 600; color: var(--vcs-text-muted);">
                        Pilih File CONNECTING (CSV):
                        <input type="file" accept=".csv" wire:model.live="csvFile"
                               class="vcs-input-control" style="padding: 6px 10px;" />
                    </label>
                </div>

                <div>
                    <button type="button" wire:click="verify" wire:loading.attr="disabled" wire:target="verify" class="vcs-btn-secondary">
                        <span wire:loading.remove wire:target="verify">1 · 🔍 Verifikasi (Dry-run)</span>
                        <span wire:loading wire:target="verify">⏳ Memeriksa Data…</span>
                    </button>
                </div>

                <div>
                    <button type="button" wire:click="sync" wire:loading.attr="disabled" wire:target="sync"
                            wire:confirm="Sinkronisasi akan MENULIS ke katalog master & tabel connecting. Lanjutkan proses?"
                            class="vcs-btn-primary">
                        <span wire:loading.remove wire:target="sync">2 · ⚡ Jalankan Sinkronisasi</span>
                        <span wire:loading wire:target="sync">⏳ Menyinkronkan Data…</span>
                    </button>
                </div>
            </div>

            @error('csvFile')
                <div style="margin-top: 12px; padding: 10px 14px; border-radius: 8px; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #ef4444; font-size: 13px; font-weight: 600;">
                    ⚠️ {{ $message }}
                </div>
            @enderror

            @if ($error)
                <div style="margin-top: 12px; padding: 10px 14px; border-radius: 8px; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #ef4444; font-size: 13px; font-weight: 600;">
                    ❌ {{ $error }}
                </div>
            @endif
        </x-filament::section>

        {{-- 2. HASIL VERIFIKASI (DRY-RUN) --}}
        @if ($report !== null)
            <x-filament::section>
                <x-slot name="heading">
                    Hasil Verifikasi (Dry-run Preview)
                </x-slot>

                <div class="vcs-grid-6">
                    {{-- Match --}}
                    <div class="vcs-card" style="border-color: rgba(16, 185, 129, 0.4);">
                        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #10b981;">✓ Sama</div>
                        <div style="margin-top: 8px; font-size: 22px; font-weight: 800; font-family: monospace; color: #10b981;">
                            {{ number_format($report['match'] !== [] ? count($report['match']) : 0) }}
                        </div>
                        <div style="margin-top: 4px; font-size: 11px; color: var(--vcs-text-muted);">Sudah sinkron</div>
                    </div>

                    {{-- Brand Baru --}}
                    <div class="vcs-card" style="border-color: {{ count($report['brandBaru']) > 0 ? 'rgba(245, 158, 11, 0.5)' : 'var(--vcs-border)' }};">
                        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: {{ count($report['brandBaru']) > 0 ? '#f59e0b' : 'var(--vcs-text-muted)' }};">Brand Baru</div>
                        <div style="margin-top: 8px; font-size: 22px; font-weight: 800; font-family: monospace; color: {{ count($report['brandBaru']) > 0 ? '#f59e0b' : 'var(--vcs-text-title)' }};">
                            {{ number_format(count($report['brandBaru'])) }}
                        </div>
                        <div style="margin-top: 4px; font-size: 11px; color: var(--vcs-text-muted);">Akan didaftarkan</div>
                    </div>

                    {{-- Model Baru --}}
                    <div class="vcs-card" style="border-color: {{ count($report['modelBaru']) > 0 ? 'rgba(245, 158, 11, 0.5)' : 'var(--vcs-border)' }};">
                        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: {{ count($report['modelBaru']) > 0 ? '#f59e0b' : 'var(--vcs-text-muted)' }};">Model Baru</div>
                        <div style="margin-top: 8px; font-size: 22px; font-weight: 800; font-family: monospace; color: {{ count($report['modelBaru']) > 0 ? '#f59e0b' : 'var(--vcs-text-title)' }};">
                            {{ number_format(count($report['modelBaru'])) }}
                        </div>
                        <div style="margin-top: 4px; font-size: 11px; color: var(--vcs-text-muted);">Akan didaftarkan</div>
                    </div>

                    {{-- Type Baru --}}
                    <div class="vcs-card" style="border-color: {{ count($report['typeBaru']) > 0 ? 'rgba(245, 158, 11, 0.5)' : 'var(--vcs-border)' }};">
                        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: {{ count($report['typeBaru']) > 0 ? '#f59e0b' : 'var(--vcs-text-muted)' }};">Type Baru</div>
                        <div style="margin-top: 8px; font-size: 22px; font-weight: 800; font-family: monospace; color: {{ count($report['typeBaru']) > 0 ? '#f59e0b' : 'var(--vcs-text-title)' }};">
                            {{ number_format(count($report['typeBaru'])) }}
                        </div>
                        <div style="margin-top: 4px; font-size: 11px; color: var(--vcs-text-muted);">Akan didaftarkan</div>
                    </div>

                    {{-- Klasifikasi Beda --}}
                    <div class="vcs-card" style="border-color: {{ count($report['klasifikasiBeda']) > 0 ? 'rgba(245, 158, 11, 0.5)' : 'var(--vcs-border)' }};">
                        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: {{ count($report['klasifikasiBeda']) > 0 ? '#f59e0b' : 'var(--vcs-text-muted)' }};">Klasifikasi Beda</div>
                        <div style="margin-top: 8px; font-size: 22px; font-weight: 800; font-family: monospace; color: {{ count($report['klasifikasiBeda']) > 0 ? '#f59e0b' : 'var(--vcs-text-title)' }};">
                            {{ number_format(count($report['klasifikasiBeda'])) }}
                        </div>
                        <div style="margin-top: 4px; font-size: 11px; color: var(--vcs-text-muted);">Akan disesuaikan</div>
                    </div>

                    {{-- DB tak ada di CSV --}}
                    <div class="vcs-card">
                        @php $dbOrphan = count($report['dbBrandTanpaCsv']) + count($report['dbModelTanpaCsv']); @endphp
                        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--vcs-text-muted);">DB tak ada di CSV</div>
                        <div style="margin-top: 8px; font-size: 22px; font-weight: 800; font-family: monospace; color: var(--vcs-text-title);">
                            {{ number_format($dbOrphan) }}
                        </div>
                        <div style="margin-top: 4px; font-size: 11px; color: var(--vcs-text-muted);">Data master lain</div>
                    </div>
                </div>

                {{-- Detail Entitas Baru --}}
                @if (count($report['brandBaru']) > 0 || count($report['modelBaru']) > 0 || count($report['typeBaru']) > 0)
                    <div style="margin-top: 20px;">
                        <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #f59e0b; margin-bottom: 8px;">
                            🆕 Entitas Baru yang Akan Didaftarkan ke Katalog
                        </div>
                        <div style="overflow-x: auto; max-height: 240px; border: 1px solid var(--vcs-border); border-radius: 8px;">
                            <table style="width: 100%; font-size: 13px; text-align: left; border-collapse: collapse;">
                                <thead style="position: sticky; top: 0; background: var(--vcs-bg-card); z-index: 1;">
                                    <tr style="border-bottom: 1px solid var(--vcs-border); color: var(--vcs-text-muted);">
                                        <th style="padding: 8px 12px; font-weight: 600;">Brand</th>
                                        <th style="padding: 8px 12px; font-weight: 600;">Model</th>
                                        <th style="padding: 8px 12px; font-weight: 600;">Type</th>
                                        <th style="padding: 8px 12px; font-weight: 600;">Kategori</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($report['brandBaru'] as $r)
                                        <tr style="border-bottom: 1px solid var(--vcs-border-sub);">
                                            <td style="padding: 8px 12px; font-family: monospace; font-weight: 700; color: var(--vcs-text-title);">{{ $r['brand'] }}</td>
                                            <td style="padding: 8px 12px; font-weight: 600; color: var(--vcs-text-title);">{{ $r['model'] }}</td>
                                            <td style="padding: 8px 12px; color: var(--vcs-text-muted);">{{ $r['type'] ?: '—' }}</td>
                                            <td style="padding: 8px 12px;">{{ $r['category'] ?: '—' }}</td>
                                        </tr>
                                    @endforeach
                                    @foreach ($report['modelBaru'] as $r)
                                        <tr style="border-bottom: 1px solid var(--vcs-border-sub);">
                                            <td style="padding: 8px 12px; font-family: monospace; font-weight: 700; color: var(--vcs-text-title);">{{ $r['brand'] }}</td>
                                            <td style="padding: 8px 12px; font-weight: 600; color: var(--vcs-text-title);">{{ $r['model'] }}</td>
                                            <td style="padding: 8px 12px; color: var(--vcs-text-muted);">{{ $r['type'] ?: '—' }}</td>
                                            <td style="padding: 8px 12px;">{{ $r['category'] ?: '—' }}</td>
                                        </tr>
                                    @endforeach
                                    @foreach ($report['typeBaru'] as $r)
                                        <tr style="border-bottom: 1px solid var(--vcs-border-sub);">
                                            <td style="padding: 8px 12px; font-family: monospace; font-weight: 700; color: var(--vcs-text-title);">{{ $r['brand'] }}</td>
                                            <td style="padding: 8px 12px; font-weight: 600; color: var(--vcs-text-title);">{{ $r['model'] }}</td>
                                            <td style="padding: 8px 12px; font-weight: 600; color: #10b981;">{{ $r['type'] }}</td>
                                            <td style="padding: 8px 12px;">{{ ($r['category'] ?? '—') ?: '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                {{-- Detail Perbedaan Klasifikasi --}}
                @if (count($report['klasifikasiBeda']) > 0)
                    <div style="margin-top: 20px;">
                        <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #f59e0b; margin-bottom: 8px;">
                            ⚙️ Perbedaan Klasifikasi yang Akan Diperbarui
                        </div>
                        <div style="overflow-x: auto; max-height: 240px; border: 1px solid var(--vcs-border); border-radius: 8px;">
                            <table style="width: 100%; font-size: 13px; text-align: left; border-collapse: collapse;">
                                <thead style="position: sticky; top: 0; background: var(--vcs-bg-card); z-index: 1;">
                                    <tr style="border-bottom: 1px solid var(--vcs-border); color: var(--vcs-text-muted);">
                                        <th style="padding: 8px 12px; font-weight: 600;">Brand</th>
                                        <th style="padding: 8px 12px; font-weight: 600;">Model</th>
                                        <th style="padding: 8px 12px; font-weight: 600;">Perbedaan / Detail Update</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($report['klasifikasiBeda'] as $row)
                                        <tr style="border-bottom: 1px solid var(--vcs-border-sub);">
                                            <td style="padding: 8px 12px; font-family: monospace; font-weight: 700; color: var(--vcs-text-title);">{{ $row['brand'] }}</td>
                                            <td style="padding: 8px 12px; font-weight: 600; color: var(--vcs-text-title);">{{ $row['model'] }}</td>
                                            <td style="padding: 8px 12px; color: #f59e0b; font-weight: 600;">{{ $row['diff'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </x-filament::section>

            {{-- CSV tidak konsisten (informasi) --}}
            @if (count($report['csvTidakKonsisten']) > 0)
                <div class="mt-3 rounded-lg border border-gray-200 p-3 dark:border-white/10">
                    <p class="mb-2 text-xs font-bold uppercase text-gray-400">CSV tidak konsisten (informasi — varian campuran, bukan aksi)</p>
                    <div class="max-h-40 overflow-y-auto">
                        <table class="w-full text-sm">
                            <tbody>
                            @foreach ($report['csvTidakKonsisten'] as $row)
                                <tr class="border-b border-gray-100 last:border-0 dark:border-white/5">
                                    <td class="py-1 font-mono text-xs">{{ $row['brand'] }}</td>
                                    <td class="py-1">{{ $row['model'] }}</td>
                                    <td class="py-1 text-xs text-gray-500">{{ $row['detail'] }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        @endif

        {{-- 3. LOG HASIL SINKRONISASI --}}
        @if (count($log) > 0)
            <x-filament::section>
                <x-slot name="heading">
                    <span style="color: #10b981;">✅ Hasil Sinkronisasi</span>
                </x-slot>

                <div style="display: flex; flex-direction: column; gap: 8px; padding: 4px 0;">
                    @foreach ($log as $line)
                        <div style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--vcs-text-title); font-weight: 600;">
                            <span style="color: #10b981;">✓</span>
                            <span>{{ $line }}</span>
                        </div>
                    @endforeach
                </div>

            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
