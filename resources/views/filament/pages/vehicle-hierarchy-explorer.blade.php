<x-filament-panels::page>
    <style>
        :root {
            --vhe-bg-card: #ffffff;
            --vhe-bg-header: rgba(0, 0, 0, 0.015);
            --vhe-bg-sub: rgba(0, 0, 0, 0.02);
            --vhe-bg-leaf: rgba(0, 0, 0, 0.03);
            --vhe-border: rgba(156, 163, 175, 0.25);
            --vhe-border-sub: rgba(156, 163, 175, 0.18);
            --vhe-text-title: #111827;
            --vhe-text-body: #374151;
            --vhe-text-muted: #6b7280;
            --vhe-input-bg: #ffffff;
            --vhe-input-border: rgba(156, 163, 175, 0.35);
        }

        .dark, [data-theme="dark"] {
            --vhe-bg-card: rgba(255, 255, 255, 0.04);
            --vhe-bg-header: rgba(255, 255, 255, 0.02);
            --vhe-bg-sub: rgba(255, 255, 255, 0.02);
            --vhe-bg-leaf: rgba(255, 255, 255, 0.03);
            --vhe-border: rgba(255, 255, 255, 0.1);
            --vhe-border-sub: rgba(255, 255, 255, 0.07);
            --vhe-text-title: #f9fafb;
            --vhe-text-body: #e5e7eb;
            --vhe-text-muted: #9ca3af;
            --vhe-input-bg: rgba(255, 255, 255, 0.05);
            --vhe-input-border: rgba(255, 255, 255, 0.15);
        }

        .vhe-card {
            border: 1px solid var(--vhe-border);
            border-radius: 12px;
            padding: 16px;
            background: var(--vhe-bg-card);
            color: var(--vhe-text-body);
            transition: all 0.15s ease;
        }
        .vhe-card-warning {
            border-color: rgba(245, 158, 11, 0.5) !important;
            background: rgba(254, 243, 199, 0.25);
        }
        .dark .vhe-card-warning, [data-theme="dark"] .vhe-card-warning {
            background: rgba(245, 158, 11, 0.08);
            border-color: rgba(245, 158, 11, 0.3) !important;
        }
        .vhe-card-danger {
            border-color: rgba(239, 68, 68, 0.4) !important;
            background: rgba(254, 226, 226, 0.25);
        }
        .dark .vhe-card-danger, [data-theme="dark"] .vhe-card-danger {
            background: rgba(239, 68, 68, 0.08);
            border-color: rgba(239, 68, 68, 0.3) !important;
        }
        .vhe-grid-4 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
        }
        .vhe-badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            line-height: 1.3;
        }
        .vhe-badge-bev { background: rgba(16, 185, 129, 0.15); color: #059669; }
        .dark .vhe-badge-bev, [data-theme="dark"] .vhe-badge-bev { background: rgba(16, 185, 129, 0.25); color: #34d399; }
        .vhe-badge-phev { background: rgba(14, 165, 233, 0.15); color: #0284c7; }
        .dark .vhe-badge-phev, [data-theme="dark"] .vhe-badge-phev { background: rgba(14, 165, 233, 0.25); color: #38bdf8; }
        .vhe-badge-hev { background: rgba(99, 102, 241, 0.15); color: #4f46e5; }
        .dark .vhe-badge-hev, [data-theme="dark"] .vhe-badge-hev { background: rgba(99, 102, 241, 0.25); color: #818cf8; }
        .vhe-badge-ice { background: rgba(107, 114, 128, 0.15); color: #4b5563; }
        .dark .vhe-badge-ice, [data-theme="dark"] .vhe-badge-ice { background: rgba(107, 114, 128, 0.25); color: #9ca3af; }
        .vhe-badge-warn { background: rgba(245, 158, 11, 0.18); color: #b45309; }
        .dark .vhe-badge-warn, [data-theme="dark"] .vhe-badge-warn { background: rgba(245, 158, 11, 0.25); color: #fbbf24; }
        .vhe-badge-gray { background: rgba(156, 163, 175, 0.15); color: #4b5563; }
        .dark .vhe-badge-gray, [data-theme="dark"] .vhe-badge-gray { background: rgba(255, 255, 255, 0.08); color: #d1d5db; }
        .vhe-brand-avatar {
            width: 36px;
            height: 36px;
            min-width: 36px;
            border-radius: 8px;
            background: linear-gradient(135deg, #10b981 0%, #047857 100%);
            color: #ffffff;
            font-weight: 800;
            font-size: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-transform: uppercase;
        }
        .vhe-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid var(--vhe-input-border);
            background: var(--vhe-input-bg);
            color: var(--vhe-text-body);
            transition: all 0.15s ease;
        }
        .vhe-btn:hover {
            background: rgba(156, 163, 175, 0.15);
        }
        .vhe-progress-bar {
            height: 5px;
            border-radius: 9999px;
            background: rgba(156, 163, 175, 0.2);
            overflow: hidden;
            width: 80px;
        }
        .vhe-progress-fill {
            height: 100%;
            background: #10b981;
            border-radius: 9999px;
        }
        .vhe-input-control {
            background: var(--vhe-input-bg);
            color: var(--vhe-text-title);
            border: 1px solid var(--vhe-input-border);
            border-radius: 8px;
            padding: 6px 10px;
            font-size: 13px;
            outline: none;
        }
        .vhe-input-control:focus {
            border-color: #10b981;
        }
    </style>

    <div style="display: flex; flex-direction: column; gap: 24px;">
        {{-- 1. KARTU STATISTIK & KESEHATAN DATA --}}
        <div class="vhe-grid-4">
            {{-- Total Unit --}}
            <div class="vhe-card">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--vhe-text-muted);">Total Unit Terjual</span>
                    <span style="font-size: 16px;">📊</span>
                </div>
                <div style="margin-top: 10px; display: flex; align-items: baseline; gap: 6px;">
                    <span style="font-size: 24px; font-weight: 800; font-family: monospace; color: var(--vhe-text-title);">{{ number_format($report['totals']['units']) }}</span>
                    <span style="font-size: 12px; color: var(--vhe-text-muted);">unit ({{ $report['year'] }})</span>
                </div>
                @if ($report['totals']['prevUnits'] > 0)
                    @php $delta = $report['totals']['units'] - $report['totals']['prevUnits']; @endphp
                    <div style="margin-top: 6px; font-size: 12px; font-weight: 700; color: {{ $delta >= 0 ? '#10b981' : '#ef4444' }};">
                        {{ $delta >= 0 ? '▲ +' : '▼ ' }}{{ number_format($delta) }} ({{ number_format(($delta / $report['totals']['prevUnits']) * 100, 1) }}%) vs {{ (int) $report['year'] - 1 }}
                    </div>
                @endif
            </div>

            {{-- Struktur Katalog --}}
            <div class="vhe-card">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--vhe-text-muted);">Struktur Katalog</span>
                    <span style="font-size: 16px;">🚗</span>
                </div>
                <div style="margin-top: 10px; display: flex; align-items: baseline; gap: 6px;">
                    <span style="font-size: 24px; font-weight: 800; color: var(--vhe-text-title);">{{ $report['totals']['totalBrands'] }}</span>
                    <span style="font-size: 12px; color: var(--vhe-text-muted);">Brand aktif</span>
                </div>
                <div style="margin-top: 6px; font-size: 12px; color: var(--vhe-text-body); display: flex; gap: 8px;">
                    <span><strong>{{ $report['totals']['totalModels'] }}</strong> Model</span>
                    <span>·</span>
                    <span><strong>{{ $report['totals']['totalTypes'] }}</strong> Type</span>
                </div>
            </div>

            {{-- Model Tanpa Kategori --}}
            <div class="vhe-card {{ $report['modelsWithoutCategory'] > 0 ? 'vhe-card-warning' : '' }}">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: {{ $report['modelsWithoutCategory'] > 0 ? '#d97706' : 'var(--vhe-text-muted)' }};">Model tanpa kategori</span>
                    <span>{{ $report['modelsWithoutCategory'] > 0 ? '⚠️' : '✅' }}</span>
                </div>
                <div style="margin-top: 10px; display: flex; align-items: baseline; gap: 6px;">
                    <span style="font-size: 24px; font-weight: 800; color: {{ $report['modelsWithoutCategory'] > 0 ? '#f59e0b' : '#10b981' }};">
                        {{ $report['modelsWithoutCategory'] }}
                    </span>
                    <span style="font-size: 12px; color: var(--vhe-text-muted);">perlu diisi</span>
                </div>
                <div style="margin-top: 6px;">
                    @if ($report['modelsWithoutCategory'] > 0)
                        <button type="button" wire:click="toggleOnlyIssues" style="background: none; border: none; padding: 0; font-size: 12px; font-weight: 700; color: #f59e0b; text-decoration: underline; cursor: pointer;">
                            {{ $onlyIssues ? 'Tampilkan Semua' : 'Filter Model Masalah ➔' }}
                        </button>
                    @else
                        <span style="font-size: 12px; color: #10b981; font-weight: 600;">✓ Semua kategori lengkap</span>
                    @endif
                </div>
            </div>

            {{-- Stats Tak Ter-Link --}}
            <div class="vhe-card {{ count($report['unlinked']) > 0 ? 'vhe-card-danger' : '' }}">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: {{ count($report['unlinked']) > 0 ? '#ef4444' : 'var(--vhe-text-muted)' }};">Stats tak ter-link</span>
                    <span>{{ count($report['unlinked']) > 0 ? '🔗' : '✅' }}</span>
                </div>
                <div style="margin-top: 10px; display: flex; align-items: baseline; gap: 6px;">
                    <span style="font-size: 24px; font-weight: 800; color: {{ count($report['unlinked']) > 0 ? '#ef4444' : '#10b981' }};">
                        {{ count($report['unlinked']) }}
                    </span>
                    <span style="font-size: 12px; color: var(--vhe-text-muted);">brand raw ({{ $report['year'] }})</span>
                </div>
                <div style="margin-top: 6px;">
                    @if (count($report['unlinked']) > 0)
                        <a href="#unlinked-section" style="font-size: 12px; font-weight: 700; color: #ef4444; text-decoration: underline;">
                            Lihat detail {{ number_format(array_sum(array_column($report['unlinked'], 'units'))) }} unit ➔
                        </a>
                    @else
                        <span style="font-size: 12px; color: #10b981; font-weight: 600;">✓ 100% data terhubung</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- 2. FILTER & SEARCH TOOLBAR --}}
        <x-filament::section>
            <div style="display: flex; flex-wrap: wrap; gap: 16px; align-items: center; justify-content: space-between;">
                {{-- Search Box --}}
                <div style="flex: 1; min-width: 260px; max-width: 400px;">
                    <input type="text"
                           wire:model.live.debounce.300ms="search"
                           placeholder="🔍 Cari Brand, Model, Type, atau Kategori..."
                           class="vhe-input-control"
                           style="width: 100%; padding: 8px 12px;" />
                </div>

                {{-- Dropdowns & Action Buttons --}}
                <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center;">
                    {{-- Tahun --}}
                    <label style="display: flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; color: var(--vhe-text-muted);">
                        Tahun:
                        <select wire:model.live="year" class="vhe-input-control">
                            @forelse ($report['years'] as $yearOption)
                                <option value="{{ $yearOption }}" @selected((int) $year === (int) $yearOption)>{{ $yearOption }}</option>
                            @empty
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endforelse
                        </select>
                    </label>

                    {{-- Powertrain --}}
                    <label style="display: flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; color: var(--vhe-text-muted);">
                        Powertrain:
                        <select wire:model.live="powertrain" class="vhe-input-control">
                            <option value="ALL">Semua</option>
                            <option value="BEV">⚡ BEV</option>
                            <option value="PHEV">🔌 PHEV</option>
                            <option value="HEV">🔋 HEV</option>
                            <option value="ICE">⛽ ICE</option>
                        </select>
                    </label>

                    {{-- Kategori --}}
                    <label style="display: flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; color: var(--vhe-text-muted);">
                        Kategori:
                        <select wire:model.live="category" class="vhe-input-control">
                            <option value="ALL">Semua Kategori</option>
                            @foreach ($categoryOptions as $opt)
                                <option value="{{ $opt }}" @selected($category === $opt)>{{ $opt }}</option>
                            @endforeach
                        </select>
                    </label>

                    {{-- Expand / Collapse --}}
                    <div style="display: flex; gap: 6px; border-left: 1px solid var(--vhe-border-sub); padding-left: 10px;">
                        <button type="button" wire:click="expandAll" class="vhe-btn" title="Buka semua node">
                            <span>Buka</span>
                        </button>
                        <button type="button" wire:click="collapseAll" class="vhe-btn" title="Tutup semua node">
                            <span>Tutup</span>
                        </button>
                    </div>

                    {{-- Filter Masalah Toggle --}}
                    <button type="button" wire:click="toggleOnlyIssues" class="vhe-btn {{ $onlyIssues ? 'vhe-card-warning' : '' }}" style="border-color: #f59e0b;">
                        <span>⚠️ {{ $onlyIssues ? 'Hanya Masalah (Aktif)' : 'Filter Masalah' }}</span>
                    </button>

                    @if ($search || $powertrain !== 'ALL' || $category !== 'ALL' || $onlyIssues)
                        <button type="button" wire:click="resetFilters" class="vhe-btn" style="color: #ef4444; border-color: rgba(239, 68, 68, 0.4);">
                            <span>Reset</span>
                        </button>
                    @endif
                </div>
            </div>
        </x-filament::section>

        {{-- 3. POHON HIERARKI KENDARAAN (BRAND -> MODEL -> TYPE) --}}
        <x-filament::section>
            <x-slot name="heading">
                Pohon Hierarki Kendaraan — {{ $report['year'] }} ({{ count($report['brands']) }} Brand)
            </x-slot>

            <div style="display: flex; flex-direction: column; gap: 12px;">
                @php $maxBrandUnits = max(1, $report['totals']['maxBrandUnits']); @endphp

                @forelse ($report['brands'] as $brand)
                    @php
                        $brandOpen = in_array('b'.$brand['id'], $expanded['brands'], true);
                        $brandDelta = $brand['units'] - $brand['prev_units'];
                        $barFraction = min(100, max(3, ($brand['units'] / $maxBrandUnits) * 100));
                    @endphp

                    <div style="border: 1px solid {{ $brand['has_issue'] ? 'rgba(245, 158, 11, 0.5)' : 'var(--vhe-border)' }}; border-radius: 10px; overflow: hidden; background: var(--vhe-bg-card);">
                        {{-- BRAND HEADER BAR --}}
                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; background: var(--vhe-bg-header); border-bottom: {{ $brandOpen ? '1px solid var(--vhe-border-sub)' : 'none' }};">
                            <button type="button" wire:click="toggleBrand({{ $brand['id'] }})"
                                    style="background: none; border: none; display: flex; align-items: center; gap: 12px; cursor: pointer; text-align: left; flex: 1; padding: 0;">
                                <span style="font-size: 13px; color: var(--vhe-text-muted); font-family: monospace;">{{ $brandOpen ? '▼' : '▶' }}</span>
                                <div class="vhe-brand-avatar">{{ substr($brand['name'], 0, 2) }}</div>
                                <div>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <span style="font-size: 15px; font-weight: 700; color: var(--vhe-text-title);">{{ $brand['name'] }}</span>
                                        @if ($brand['has_issue'])
                                            <span class="vhe-badge vhe-badge-warn">⚠️ ada issue</span>
                                        @endif
                                    </div>
                                    <div style="font-size: 12px; color: var(--vhe-text-muted); margin-top: 2px;">
                                        {{ count($brand['models']) }} Model · {{ $brand['total_types'] }} Type
                                    </div>
                                </div>
                            </button>

                            {{-- Volume & Actions --}}
                            <div style="display: flex; align-items: center; gap: 16px;">
                                <div style="display: flex; flex-direction: column; align-items: flex-end;">
                                    <div style="font-size: 15px; font-weight: 800; font-family: monospace; color: var(--vhe-text-title);">
                                        {{ number_format($brand['units']) }} <span style="font-size: 11px; font-weight: 500; color: var(--vhe-text-muted);">unit</span>
                                    </div>
                                    <div class="vhe-progress-bar" style="margin-top: 4px;">
                                        <div class="vhe-progress-fill" style="width: {{ $barFraction }}%;"></div>
                                    </div>
                                </div>

                                @if ($brand['prev_units'] > 0)
                                    <div style="font-size: 12px; font-weight: 700; color: {{ $brandDelta >= 0 ? '#10b981' : '#ef4444' }}; min-width: 70px; text-align: right;">
                                        {{ $brandDelta >= 0 ? '▲+' : '▼' }}{{ number_format(abs($brandDelta)) }}
                                    </div>
                                @endif

                                <a href="{{ \App\Filament\Resources\Panel\BrandVehicleResource::getUrl('edit', ['record' => $brand['id']]) }}"
                                   target="_blank"
                                   title="Edit data Brand di panel"
                                   style="color: var(--vhe-text-muted); text-decoration: none; padding: 4px;">
                                    ✏️
                                </a>
                            </div>
                        </div>

                        {{-- MODEL SUB-TREE --}}
                        @if ($brandOpen)
                            <div style="padding: 12px 16px 12px 40px; background: var(--vhe-bg-sub); display: flex; flex-direction: column; gap: 8px;">
                                @forelse ($brand['models'] as $model)
                                    @php
                                        $modelOpen = in_array('m'.$model['id'], $expanded['models'], true);
                                        $modelDelta = $model['units'] - $model['prev_units'];
                                        $pt = strtoupper($model['powertrain'] ?? 'BEV');
                                    @endphp

                                    <div style="border: 1px solid {{ $model['has_issue'] ? 'rgba(245, 158, 11, 0.5)' : 'var(--vhe-border-sub)' }}; border-radius: 8px; background: var(--vhe-bg-card); overflow: hidden;">
                                        {{-- Model Row --}}
                                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 8px 12px;">
                                            <button type="button" wire:click="toggleModel({{ $model['id'] }})"
                                                    style="background: none; border: none; display: flex; align-items: center; gap: 8px; cursor: pointer; text-align: left; flex: 1; padding: 0;">
                                                <span style="font-size: 11px; color: var(--vhe-text-muted); font-family: monospace;">{{ $modelOpen ? '▼' : '▶' }}</span>
                                                <span style="font-size: 13px; font-weight: 700; color: var(--vhe-text-title);">{{ $model['name'] }}</span>

                                                {{-- Powertrain Badge --}}
                                                @if ($pt === 'BEV')
                                                    <span class="vhe-badge vhe-badge-bev">⚡ BEV</span>
                                                @elseif ($pt === 'PHEV')
                                                    <span class="vhe-badge vhe-badge-phev">🔌 PHEV</span>
                                                @elseif ($pt === 'HEV')
                                                    <span class="vhe-badge vhe-badge-hev">🔋 HEV</span>
                                                @else
                                                    <span class="vhe-badge vhe-badge-ice">⛽ ICE</span>
                                                @endif

                                                {{-- Category Badge --}}
                                                @if ($model['category'])
                                                    <span class="vhe-badge vhe-badge-gray">{{ $model['category'] }}{{ $model['size'] ? ' · '.$model['size'] : '' }}</span>
                                                @else
                                                    <span class="vhe-badge vhe-badge-warn">⚠️ tanpa kategori</span>
                                                @endif
                                            </button>

                                            {{-- Model Units & Edit --}}
                                            <div style="display: flex; align-items: center; gap: 12px;">
                                                <div style="font-size: 13px; font-weight: 700; font-family: monospace; color: var(--vhe-text-title);">
                                                    {{ number_format($model['units']) }} <span style="font-size: 10px; color: var(--vhe-text-muted);">unit</span>
                                                </div>

                                                @if ($model['prev_units'] > 0)
                                                    <span style="font-size: 11px; font-weight: 700; color: {{ $modelDelta >= 0 ? '#10b981' : '#ef4444' }};">
                                                        {{ $modelDelta >= 0 ? '▲+' : '▼' }}{{ number_format(abs($modelDelta)) }}
                                                    </span>
                                                @endif

                                                <span class="vhe-badge vhe-badge-gray">{{ $model['type_count'] }} type</span>

                                                <a href="{{ \App\Filament\Resources\Panel\ModelVehicleResource::getUrl('edit', ['record' => $model['id']]) }}"
                                                   target="_blank"
                                                   title="Edit Model ini di panel"
                                                   style="color: var(--vhe-text-muted); text-decoration: none; font-size: 12px;">
                                                    ✏️
                                                </a>
                                            </div>
                                        </div>

                                        {{-- TYPE LEAF-NODES --}}
                                        @if ($modelOpen)
                                            <div style="padding: 6px 12px 6px 28px; background: var(--vhe-bg-leaf); border-top: 1px solid var(--vhe-border-sub); display: flex; flex-direction: column; gap: 4px;">
                                                @forelse ($model['types'] as $type)
                                                    <div style="display: flex; align-items: center; justify-content: space-between; font-size: 12px; color: var(--vhe-text-body);">
                                                        <div style="display: flex; align-items: center; gap: 6px;">
                                                            <span style="color: var(--vhe-text-muted); font-family: monospace;">└─</span>
                                                            <span style="font-weight: 500; color: var(--vhe-text-body);">{{ $type['name'] }}</span>
                                                        </div>
                                                        <div style="display: flex; align-items: center; gap: 8px;">
                                                            <span style="font-family: monospace; color: var(--vhe-text-muted);">{{ number_format($type['units']) }} unit</span>
                                                            <a href="{{ \App\Filament\Resources\Panel\TypeVehicleResource::getUrl('edit', ['record' => $type['id']]) }}"
                                                               target="_blank"
                                                               title="Edit Type ini"
                                                               style="color: var(--vhe-text-muted); text-decoration: none; font-size: 11px;">
                                                                ✏️
                                                            </a>
                                                        </div>
                                                    </div>
                                                @empty
                                                    <p style="margin: 0; font-size: 11px; color: var(--vhe-text-muted); font-style: italic;">Belum ada varian type terdaftar untuk model ini.</p>
                                                @endforelse
                                            </div>
                                        @endif
                                    </div>
                                @empty
                                    <p style="margin: 0; font-size: 12px; color: var(--vhe-text-muted);">Tidak ada model yang cocok dengan filter.</p>
                                @endforelse
                            </div>
                        @endif
                    </div>
                @empty
                    <div style="text-align: center; padding: 32px 16px; color: var(--vhe-text-muted);">
                        <p style="font-size: 14px; font-weight: 600;">Tidak ada data ditemukan untuk filter ini.</p>
                        <button type="button" wire:click="resetFilters" class="vhe-btn" style="margin-top: 10px;">
                            <span>Reset Filter</span>
                        </button>
                    </div>
                @endforelse
            </div>
        </x-filament::section>

        {{-- 4. STATS TAK TER-LINK (UNLINKED GAIKINDO RAW DATA) --}}
        @if (count($report['unlinked']) > 0)
            <div id="unlinked-section">
                <x-filament::section>
                    <x-slot name="heading">
                        <span style="color: #ef4444;">⚠️ Stats Tak Ter-link — Tahun {{ $report['year'] }} ({{ count($report['unlinked']) }} Brand)</span>
                    </x-slot>

                    <p style="font-size: 12px; color: var(--vhe-text-muted); margin-bottom: 12px;">
                        Data wholesales GAIKINDO di bawah ini memiliki <code>raw_brand</code> yang belum dipetakan ke katalog master (link <code>model_vehicle_id</code> masih NULL).
                        Perbaiki alias brand / connecting lalu jalankan re-import agar data terhubung ke pohon hierarki.
                    </p>

                    <div style="overflow-x: auto;">
                        <table style="width: 100%; font-size: 13px; text-align: left; border-collapse: collapse;">
                            <thead>
                                <tr style="border-bottom: 1px solid var(--vhe-border); color: var(--vhe-text-muted);">
                                    <th style="padding: 8px 12px; font-weight: 600;">Raw Brand (GAIKINDO)</th>
                                    <th style="padding: 8px 12px; font-weight: 600;">Raw Model Terdeteksi</th>
                                    <th style="padding: 8px 12px; font-weight: 600; text-align: right;">Volume Penjualan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($report['unlinked'] as $row)
                                    <tr style="border-bottom: 1px solid var(--vhe-border-sub);">
                                        <td style="padding: 8px 12px; font-family: monospace; font-weight: 700; color: var(--vhe-text-title);">{{ $row['brand'] }}</td>
                                        <td style="padding: 8px 12px; color: var(--vhe-text-body);">{{ $row['model'] ?? ($row['models'] ?? '—') }}</td>
                                        <td style="padding: 8px 12px; text-align: right; font-family: monospace; font-weight: 800; color: #ef4444;">
                                            {{ number_format($row['units']) }} unit
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
            </div>
        @endif
    </div>
</x-filament-panels::page>
