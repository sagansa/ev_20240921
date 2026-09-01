<x-filament-panels::page>
    <style>
        :root {
            --vsr-bg-card: #ffffff;
            --vsr-bg-header: rgba(0, 0, 0, 0.015);
            --vsr-bg-sub: rgba(0, 0, 0, 0.02);
            --vsr-border: rgba(156, 163, 175, 0.25);
            --vsr-border-sub: rgba(156, 163, 175, 0.15);
            --vsr-text-title: #111827;
            --vsr-text-body: #374151;
            --vsr-text-muted: #6b7280;
            --vsr-input-bg: #ffffff;
            --vsr-input-border: rgba(156, 163, 175, 0.35);
        }

        .dark, [data-theme="dark"] {
            --vsr-bg-card: rgba(255, 255, 255, 0.04);
            --vsr-bg-header: rgba(255, 255, 255, 0.02);
            --vsr-bg-sub: rgba(255, 255, 255, 0.02);
            --vsr-border: rgba(255, 255, 255, 0.1);
            --vsr-border-sub: rgba(255, 255, 255, 0.07);
            --vsr-text-title: #f9fafb;
            --vsr-text-body: #e5e7eb;
            --vsr-text-muted: #9ca3af;
            --vsr-input-bg: rgba(255, 255, 255, 0.05);
            --vsr-input-border: rgba(255, 255, 255, 0.15);
        }

        .vsr-card {
            border: 1px solid var(--vsr-border);
            border-radius: 12px;
            padding: 16px;
            background: var(--vsr-bg-card);
            color: var(--vsr-text-body);
            transition: all 0.15s ease;
        }
        .vsr-grid-4 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 14px;
        }
        .vsr-badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            line-height: 1.3;
        }
        .vsr-badge-bev { background: rgba(16, 185, 129, 0.15); color: #059669; }
        .dark .vsr-badge-bev, [data-theme="dark"] .vsr-badge-bev { background: rgba(16, 185, 129, 0.25); color: #34d399; }
        .vsr-badge-phev { background: rgba(14, 165, 233, 0.15); color: #0284c7; }
        .dark .vsr-badge-phev, [data-theme="dark"] .vsr-badge-phev { background: rgba(14, 165, 233, 0.25); color: #38bdf8; }
        .vsr-badge-hev { background: rgba(99, 102, 241, 0.15); color: #4f46e5; }
        .dark .vsr-badge-hev, [data-theme="dark"] .vsr-badge-hev { background: rgba(99, 102, 241, 0.25); color: #818cf8; }
        .vsr-badge-ice { background: rgba(107, 114, 128, 0.15); color: #4b5563; }
        .dark .vsr-badge-ice, [data-theme="dark"] .vsr-badge-ice { background: rgba(107, 114, 128, 0.25); color: #9ca3af; }
        .vsr-badge-primary { background: rgba(16, 185, 129, 0.15); color: #059669; }
        .dark .vsr-badge-primary, [data-theme="dark"] .vsr-badge-primary { background: rgba(16, 185, 129, 0.25); color: #34d399; }
        .vsr-badge-gray { background: rgba(156, 163, 175, 0.15); color: #4b5563; }
        .dark .vsr-badge-gray, [data-theme="dark"] .vsr-badge-gray { background: rgba(255, 255, 255, 0.08); color: #d1d5db; }
        .vsr-brand-avatar {
            width: 28px;
            height: 28px;
            min-width: 28px;
            border-radius: 6px;
            background: linear-gradient(135deg, #10b981 0%, #047857 100%);
            color: #ffffff;
            font-weight: 800;
            font-size: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-transform: uppercase;
        }
        .vsr-input-control {
            background: var(--vsr-input-bg);
            color: var(--vsr-text-title);
            border: 1px solid var(--vsr-input-border);
            border-radius: 8px;
            padding: 6px 12px;
            font-size: 13px;
            outline: none;
        }
        .vsr-input-control:focus {
            border-color: #10b981;
        }
        .vsr-progress-bar {
            height: 4px;
            border-radius: 9999px;
            background: rgba(156, 163, 175, 0.2);
            overflow: hidden;
            width: 70px;
        }
        .vsr-progress-fill {
            height: 100%;
            background: #10b981;
            border-radius: 9999px;
        }
    </style>

    <div style="display: flex; flex-direction: column; gap: 24px;">
        @php
            $matchedBrands = array_values(array_filter($brandRows, fn ($r) => $r['brand'] !== '(tidak ter-match)'));
            $matchedModels = array_values(array_filter($modelRows, fn ($r) => $r['model'] !== '(tidak ter-match)'));
            $topBrand = $matchedBrands[0] ?? null;
            $maxBrandUnits = !empty($matchedBrands) ? max(array_column($matchedBrands, 'total_units')) : 1;
            $maxModelUnits = !empty($matchedModels) ? max(array_column($matchedModels, 'total_units')) : 1;
        @endphp

        {{-- 1. KPI SUMMARY STATS CARDS --}}
        <div class="vsr-grid-4">
            {{-- Total Wholesales --}}
            <div class="vsr-card">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--vsr-text-muted);">Total Wholesales</span>
                    <span style="font-size: 16px;">📈</span>
                </div>
                <div style="margin-top: 8px; font-size: 24px; font-weight: 800; font-family: monospace; color: var(--vsr-text-title);">
                    {{ number_format($totalUnits) }}
                </div>
                <div style="margin-top: 4px; font-size: 12px; color: var(--vsr-text-muted);">
                    Unit terjual ({{ $year ?? '—' }}) · {{ $powertrain }}
                </div>
            </div>

            {{-- Brand Aktif --}}
            <div class="vsr-card">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--vsr-text-muted);">Brand Terdaftar</span>
                    <span style="font-size: 16px;">🏷️</span>
                </div>
                <div style="margin-top: 8px; font-size: 24px; font-weight: 800; font-family: monospace; color: var(--vsr-text-title);">
                    {{ count($matchedBrands) }}
                </div>
                <div style="margin-top: 4px; font-size: 12px; color: var(--vsr-text-muted);">Merek aktif berkatalog</div>
            </div>

            {{-- Model Aktif --}}
            <div class="vsr-card">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--vsr-text-muted);">Model Terdaftar</span>
                    <span style="font-size: 16px;">🚗</span>
                </div>
                <div style="margin-top: 8px; font-size: 24px; font-weight: 800; font-family: monospace; color: var(--vsr-text-title);">
                    {{ count($matchedModels) }}
                </div>
                <div style="margin-top: 4px; font-size: 12px; color: var(--vsr-text-muted);">Model kendaraan aktif</div>
            </div>

            {{-- Market Leader --}}
            <div class="vsr-card">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--vsr-text-muted);">#1 Market Leader</span>
                    <span style="font-size: 16px;">🏆</span>
                </div>
                <div style="margin-top: 8px; font-size: 20px; font-weight: 800; color: #10b981; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    {{ $topBrand['brand'] ?? '—' }}
                </div>
                <div style="margin-top: 4px; font-size: 12px; color: var(--vsr-text-muted);">
                    @if ($topBrand && $totalUnits > 0)
                        {{ number_format($topBrand['total_units']) }} unit ({{ number_format(($topBrand['total_units'] / $totalUnits) * 100, 1) }}% share)
                    @else
                        Belum ada data
                    @endif
                </div>
            </div>
        </div>

        {{-- 2. FILTER TOOLBAR --}}
        <x-filament::section>
            <div style="display: flex; flex-wrap: wrap; gap: 16px; align-items: center; justify-content: space-between;">
                <div style="display: flex; flex-wrap: wrap; gap: 16px; align-items: center;">
                    {{-- Filter Tahun --}}
                    <label style="display: flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; color: var(--vsr-text-muted);">
                        Tahun:
                        <select wire:model.live="year" class="vsr-input-control">
                            @forelse ($years as $yearOption)
                                <option value="{{ $yearOption }}" @selected((int) $year === (int) $yearOption)>{{ $yearOption }}</option>
                            @empty
                                <option value="">—</option>
                            @endforelse
                        </select>
                    </label>

                    {{-- Filter Powertrain --}}
                    <label style="display: flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; color: var(--vsr-text-muted);">
                        Powertrain:
                        <select wire:model.live="powertrain" class="vsr-input-control">
                            <option value="ALL">Semua Powertrain</option>
                            <option value="BEV">⚡ BEV (Battery EV)</option>
                            <option value="PHEV">🔌 PHEV (Plug-in Hybrid)</option>
                            <option value="HEV">🔋 HEV (Hybrid)</option>
                            <option value="ICE">⛽ ICE (Bensin/Diesel)</option>
                        </select>
                    </label>
                </div>

                <div style="font-size: 13px; color: var(--vsr-text-muted);">
                    Total Penjualan: <strong style="color: var(--vsr-text-title); font-size: 15px; font-family: monospace;">{{ number_format($totalUnits) }}</strong> unit
                </div>
            </div>
        </x-filament::section>

        {{-- 3. PER BRAND TABLE --}}
        <x-filament::section>
            <x-slot name="heading">
                Penjualan per Brand — {{ $year ?? '—' }} ({{ count($brandRows) }} Data)
            </x-slot>

            <div style="overflow-x: auto;">
                <table style="width: 100%; font-size: 13px; text-align: left; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--vsr-border); color: var(--vsr-text-muted);">
                            <th style="padding: 10px 12px; font-weight: 600; width: 40px;">#</th>
                            <th style="padding: 10px 12px; font-weight: 600;">Brand / Merek</th>
                            <th style="padding: 10px 12px; font-weight: 600; text-align: right;">Total Unit</th>
                            <th style="padding: 10px 12px; font-weight: 600; text-align: right; width: 100px;">Pangsa Pasar</th>
                            <th style="padding: 10px 12px; font-weight: 600; text-align: right;">Jumlah Model</th>
                            <th style="padding: 10px 12px; font-weight: 600; text-align: right;">Jumlah Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $rank = 1; @endphp
                        @forelse ($brandRows as $row)
                            @php
                                $isUnmatched = $row['brand'] === '(tidak ter-match)';
                                $barWidth = min(100, max(2, ($row['total_units'] / max(1, $maxBrandUnits)) * 100));
                                $sharePercent = $totalUnits > 0 ? ($row['total_units'] / $totalUnits) * 100 : 0;
                            @endphp
                            <tr style="border-bottom: 1px solid var(--vsr-border-sub);">
                                <td style="padding: 10px 12px; color: var(--vsr-text-muted); font-size: 12px; font-weight: 600;">
                                    {{ $isUnmatched ? '—' : $rank++ }}
                                </td>
                                <td style="padding: 10px 12px;">
                                    @if ($isUnmatched)
                                        <span style="font-style: italic; color: #ef4444; font-weight: 600;">(tidak ter-match)</span>
                                    @else
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <div class="vsr-brand-avatar">{{ substr($row['brand'], 0, 2) }}</div>
                                            <span style="font-weight: 700; color: var(--vsr-text-title);">{{ $row['brand'] }}</span>
                                        </div>
                                    @endif
                                </td>
                                <td style="padding: 10px 12px; text-align: right;">
                                    <div style="display: flex; flex-direction: column; align-items: flex-end;">
                                        <span style="font-weight: 800; font-family: monospace; color: var(--vsr-text-title);">
                                            {{ number_format($row['total_units']) }}
                                        </span>
                                        <div class="vsr-progress-bar" style="margin-top: 3px;">
                                            <div class="vsr-progress-fill" style="width: {{ $barWidth }}%;"></div>
                                        </div>
                                    </div>
                                </td>
                                <td style="padding: 10px 12px; text-align: right; font-weight: 600; font-family: monospace; color: var(--vsr-text-muted);">
                                    {{ number_format($sharePercent, 1) }}%
                                </td>
                                <td style="padding: 10px 12px; text-align: right;">
                                    <span class="vsr-badge vsr-badge-gray">{{ number_format($row['model_count']) }} model</span>
                                </td>
                                <td style="padding: 10px 12px; text-align: right;">
                                    <span class="vsr-badge vsr-badge-gray">{{ number_format($row['type_count']) }} type</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="padding: 32px 12px; text-align: center; color: var(--vsr-text-muted);">
                                    Tidak ada data penjualan untuk filter ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        {{-- 4. PER MODEL TABLE --}}
        <x-filament::section>
            <x-slot name="heading">
                Penjualan per Model — {{ $year ?? '—' }}
            </x-slot>
            <x-slot name="description">
                Ditampilkan maksimal {{ $modelRowLimit }} model teratas (urut total unit). @if (count($modelRows) >= $modelRowLimit) Tabel terpotong. @endif
            </x-slot>

            <div style="overflow-x: auto;">
                <table style="width: 100%; font-size: 13px; text-align: left; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--vsr-border); color: var(--vsr-text-muted);">
                            <th style="padding: 10px 12px; font-weight: 600; width: 40px;">#</th>
                            <th style="padding: 10px 12px; font-weight: 600;">Brand</th>
                            <th style="padding: 10px 12px; font-weight: 600;">Nama Model</th>
                            <th style="padding: 10px 12px; font-weight: 600; text-align: right;">Total Unit</th>
                            <th style="padding: 10px 12px; font-weight: 600; text-align: right; width: 100px;">Pangsa Pasar</th>
                            <th style="padding: 10px 12px; font-weight: 600; text-align: right;">Jumlah Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $mRank = 1; @endphp
                        @forelse ($modelRows as $row)
                            @php
                                $isUnmatched = $row['model'] === '(tidak ter-match)';
                                $barWidth = min(100, max(2, ($row['total_units'] / max(1, $maxModelUnits)) * 100));
                                $sharePercent = $totalUnits > 0 ? ($row['total_units'] / $totalUnits) * 100 : 0;
                            @endphp
                            <tr style="border-bottom: 1px solid var(--vsr-border-sub);">
                                <td style="padding: 10px 12px; color: var(--vhe-text-muted); font-size: 12px; font-weight: 600;">
                                    {{ $isUnmatched ? '—' : $mRank++ }}
                                </td>
                                <td style="padding: 10px 12px; font-weight: 600; color: var(--vsr-text-title);">
                                    {{ $row['brand'] }}
                                </td>
                                <td style="padding: 10px 12px;">
                                    @if ($isUnmatched)
                                        <span style="font-style: italic; color: #ef4444; font-weight: 600;">(tidak ter-match)</span>
                                    @else
                                        <span style="font-weight: 700; color: var(--vsr-text-title);">{{ $row['model'] }}</span>
                                    @endif
                                </td>
                                <td style="padding: 10px 12px; text-align: right;">
                                    <div style="display: flex; flex-direction: column; align-items: flex-end;">
                                        <span style="font-weight: 800; font-family: monospace; color: var(--vsr-text-title);">
                                            {{ number_format($row['total_units']) }}
                                        </span>
                                        <div class="vsr-progress-bar" style="margin-top: 3px;">
                                            <div class="vsr-progress-fill" style="width: {{ $barWidth }}%;"></div>
                                        </div>
                                    </div>
                                </td>
                                <td style="padding: 10px 12px; text-align: right; font-weight: 600; font-family: monospace; color: var(--vsr-text-muted);">
                                    {{ number_format($sharePercent, 1) }}%
                                </td>
                                <td style="padding: 10px 12px; text-align: right;">
                                    <span class="vsr-badge vsr-badge-gray">{{ number_format($row['type_count']) }} type</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="padding: 32px 12px; text-align: center; color: var(--vsr-text-muted);">
                                    Tidak ada data model untuk filter ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
