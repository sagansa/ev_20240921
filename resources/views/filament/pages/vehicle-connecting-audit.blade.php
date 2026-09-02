<x-filament-panels::page>
    <style>
        :root {
            --vac-bg-card: #ffffff;
            --vac-bg-header: rgba(0, 0, 0, 0.015);
            --vac-bg-sub: rgba(0, 0, 0, 0.02);
            --vac-border: rgba(156, 163, 175, 0.25);
            --vac-border-sub: rgba(156, 163, 175, 0.16);
            --vac-text-title: #111827;
            --vac-text-body: #374151;
            --vac-text-muted: #6b7280;
            --vac-input-bg: #ffffff;
            --vac-input-border: rgba(156, 163, 175, 0.35);
            --vac-th-bg: #f9fafb;
            --vac-row-hover: rgba(156, 163, 175, 0.06);
        }

        .dark, [data-theme="dark"] {
            --vac-bg-card: rgba(255, 255, 255, 0.04);
            --vac-bg-header: rgba(255, 255, 255, 0.02);
            --vac-bg-sub: rgba(255, 255, 255, 0.02);
            --vac-border: rgba(255, 255, 255, 0.1);
            --vac-border-sub: rgba(255, 255, 255, 0.07);
            --vac-text-title: #f9fafb;
            --vac-text-body: #e5e7eb;
            --vac-text-muted: #9ca3af;
            --vac-input-bg: rgba(255, 255, 255, 0.05);
            --vac-input-border: rgba(255, 255, 255, 0.15);
            --vac-th-bg: #111827;
            --vac-row-hover: rgba(255, 255, 255, 0.04);
        }

        .vac-card {
            border: 1px solid var(--vac-border);
            border-radius: 12px;
            padding: 16px;
            background: var(--vac-bg-card);
            color: var(--vac-text-body);
            transition: all 0.15s ease;
        }

        .vac-grid-5 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 14px;
        }

        .vac-badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            line-height: 1.3;
            white-space: nowrap;
        }

        .vac-badge-bev { background: rgba(16, 185, 129, 0.15); color: #059669; }
        .dark .vac-badge-bev, [data-theme="dark"] .vac-badge-bev { background: rgba(16, 185, 129, 0.25); color: #34d399; }
        
        .vac-badge-phev { background: rgba(14, 165, 233, 0.15); color: #0284c7; }
        .dark .vac-badge-phev, [data-theme="dark"] .vac-badge-phev { background: rgba(14, 165, 233, 0.25); color: #38bdf8; }
        
        .vac-badge-hev { background: rgba(99, 102, 241, 0.15); color: #4f46e5; }
        .dark .vac-badge-hev, [data-theme="dark"] .vac-badge-hev { background: rgba(99, 102, 241, 0.25); color: #818cf8; }
        
        .vac-badge-ice { background: rgba(107, 114, 128, 0.15); color: #4b5563; }
        .dark .vac-badge-ice, [data-theme="dark"] .vac-badge-ice { background: rgba(107, 114, 128, 0.25); color: #9ca3af; }

        .vac-badge-danger { background: rgba(239, 68, 68, 0.15); color: #dc2626; }
        .dark .vac-badge-danger, [data-theme="dark"] .vac-badge-danger { background: rgba(239, 68, 68, 0.25); color: #f87171; }

        .vac-badge-warn { background: rgba(245, 158, 11, 0.15); color: #d97706; }
        .dark .vac-badge-warn, [data-theme="dark"] .vac-badge-warn { background: rgba(245, 158, 11, 0.25); color: #fbbf24; }

        .vac-badge-ok { background: rgba(16, 185, 129, 0.15); color: #059669; }
        .dark .vac-badge-ok, [data-theme="dark"] .vac-badge-ok { background: rgba(16, 185, 129, 0.25); color: #34d399; }

        .vac-badge-gray { background: rgba(156, 163, 175, 0.15); color: #4b5563; }
        .dark .vac-badge-gray, [data-theme="dark"] .vac-badge-gray { background: rgba(255, 255, 255, 0.08); color: #d1d5db; }

        .vac-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 12.5px;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid var(--vac-input-border);
            background: var(--vac-input-bg);
            color: var(--vac-text-body);
            transition: all 0.15s ease;
            text-decoration: none;
            line-height: 1.5;
        }
        .vac-btn:hover { background: rgba(156, 163, 175, 0.12); color: var(--vac-text-title); }

        .vac-btn-primary {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-color: transparent;
            color: #ffffff !important;
            font-weight: 700;
        }
        .vac-btn-primary:hover { opacity: 0.92; color: #ffffff !important; }

        .vac-chip {
            padding: 5px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid var(--vac-border);
            background: var(--vac-input-bg);
            color: var(--vac-text-body);
            transition: all 0.15s ease;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .vac-chip:hover { border-color: rgba(16, 185, 129, 0.5); }
        .vac-chip-active {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
            color: #ffffff !important;
            border-color: transparent !important;
        }

        .vac-input-control {
            background: var(--vac-input-bg);
            color: var(--vac-text-title);
            border: 1px solid var(--vac-input-border);
            border-radius: 8px;
            padding: 7px 12px;
            font-size: 13px;
            outline: none;
            transition: border-color 0.15s ease;
        }
        .vac-input-control:focus { border-color: #10b981; }

        .vac-progress-bar {
            height: 5px;
            border-radius: 9999px;
            background: rgba(156, 163, 175, 0.2);
            overflow: hidden;
            width: 100%;
            margin-top: 8px;
        }
        .vac-progress-fill {
            height: 100%;
            border-radius: 9999px;
            transition: width 0.3s ease;
        }

        .vac-table {
            width: 100%;
            font-size: 12.5px;
            text-align: left;
            border-collapse: collapse;
        }
        .vac-table th {
            padding: 9px 12px;
            font-weight: 700;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--vac-text-muted);
            border-bottom: 1px solid var(--vac-border);
            white-space: nowrap;
            position: sticky;
            top: 0;
            background: var(--vac-th-bg);
            z-index: 2;
        }
        .vac-table td {
            padding: 8px 12px;
            border-bottom: 1px solid var(--vac-border-sub);
            vertical-align: top;
            color: var(--vac-text-body);
        }
        .vac-table tr:hover td { background: var(--vac-row-hover); }
    </style>

    <div style="display: flex; flex-direction: column; gap: 20px;">

        {{-- 1. HEADER & TOP NAV ACTIONS --}}
        <div class="vac-card" style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 16px;">
            <div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <h2 style="margin: 0; font-size: 17px; font-weight: 800; color: var(--vac-text-title);">
                        Audit Raw Connecting (Master GAIKINDO)
                    </h2>
                    <span class="vac-badge vac-badge-bev">Read-Only Diagnostic</span>
                </div>
                <p style="margin: 4px 0 0; font-size: 12.5px; color: var(--vac-text-muted);">
                    Inspeksi pemetaan <code>BRAND MODEL TYPE</code> (teks mentah laporan) terhadap katalog master (Brand, Model, Type) & kelengkapan klasifikasi.
                </p>
            </div>

            <div style="display: flex; flex-wrap: wrap; gap: 8px; align-items: center;">
                <a href="{{ \App\Filament\Pages\VehicleConnectingSync::getUrl() }}" class="vac-btn">
                    <span>⚡ Sinkronisasi CONNECTING</span>
                </a>
                <a href="{{ \App\Filament\Pages\VehicleHierarchyExplorer::getUrl() }}" class="vac-btn">
                    <span>🌳 Pohon Hierarki</span>
                </a>
                <button type="button" wire:click="download" wire:loading.attr="disabled" wire:target="download" class="vac-btn vac-btn-primary">
                    <span wire:loading.remove wire:target="download">⬇ Unduh CSV Audit</span>
                    <span wire:loading wire:target="download">⏳ Menyiapkan…</span>
                </button>
            </div>
        </div>

        {{-- 2. METRICS & HEALTH CARDS --}}
        @php
            $healthRate = $summary['health_rate'] ?? 100;
            $healthColor = $healthRate >= 95 ? '#10b981' : ($healthRate >= 80 ? '#f59e0b' : '#ef4444');
        @endphp
        <div class="vac-grid-5">
            {{-- Total Baris --}}
            <div class="vac-card">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--vac-text-muted);">Total Baris</span>
                    <span style="font-size: 16px;">🗃️</span>
                </div>
                <div style="margin-top: 8px; font-size: 22px; font-weight: 800; font-family: monospace; color: var(--vac-text-title);">
                    {{ number_format($summary['total'] ?? 0) }}
                </div>
                <div style="margin-top: 4px; font-size: 11px; color: var(--vac-text-muted);">
                    Kombinasi CONNECTING
                </div>
            </div>

            {{-- Tingkat Kesehatan --}}
            <div class="vac-card" style="border-color: {{ ($summary['problem'] ?? 0) > 0 ? 'rgba(245, 158, 11, 0.4)' : 'rgba(16, 185, 129, 0.4)' }};">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: {{ $healthColor }};">Tingkat Kesehatan</span>
                    <span style="font-size: 16px;">{{ $healthRate >= 95 ? '🛡️' : '⚠️' }}</span>
                </div>
                <div style="margin-top: 8px; display: flex; align-items: baseline; gap: 6px;">
                    <span style="font-size: 22px; font-weight: 800; font-family: monospace; color: {{ $healthColor }};">
                        {{ $healthRate }}%
                    </span>
                    <span style="font-size: 11px; color: var(--vac-text-muted);">bebas masalah</span>
                </div>
                <div class="vac-progress-bar">
                    <div class="vac-progress-fill" style="width: {{ $healthRate }}%; background: {{ $healthColor }};"></div>
                </div>
            </div>

            {{-- Bermasalah --}}
            <div class="vac-card" style="border-color: {{ ($summary['problem'] ?? 0) > 0 ? 'rgba(239, 68, 68, 0.45)' : 'rgba(16, 185, 129, 0.4)' }};">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: {{ ($summary['problem'] ?? 0) > 0 ? '#ef4444' : '#10b981' }};">Bermasalah</span>
                    <span style="font-size: 16px;">{{ ($summary['problem'] ?? 0) > 0 ? '🚨' : '✅' }}</span>
                </div>
                <div style="margin-top: 8px; font-size: 22px; font-weight: 800; font-family: monospace; color: {{ ($summary['problem'] ?? 0) > 0 ? '#ef4444' : '#10b981' }};">
                    {{ number_format($summary['problem'] ?? 0) }}
                </div>
                <div style="margin-top: 4px; font-size: 11px; color: var(--vac-text-muted);">
                    {{ ($summary['problem'] ?? 0) > 0 ? 'Perlu tindakan koreksi' : 'Seluruh baris valid' }}
                </div>
            </div>

            {{-- Masalah Kunci --}}
            <div class="vac-card">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--vac-text-muted);">Masalah Kunci</span>
                    <span style="font-size: 16px;">🔑</span>
                </div>
                <div style="margin-top: 8px; display: flex; align-items: baseline; gap: 10px;">
                    <div>
                        <span style="font-size: 11px; color: var(--vac-text-muted);">Tanpa: </span>
                        <span style="font-size: 15px; font-weight: 800; font-family: monospace; color: {{ ($summary['no_key'] ?? 0) > 0 ? '#ef4444' : '#10b981' }};">
                            {{ number_format($summary['no_key'] ?? 0) }}
                        </span>
                    </div>
                    <div>
                        <span style="font-size: 11px; color: var(--vac-text-muted);">Dup: </span>
                        <span style="font-size: 15px; font-weight: 800; font-family: monospace; color: {{ ($summary['dup'] ?? 0) > 0 ? '#ef4444' : '#10b981' }};">
                            {{ number_format($summary['dup'] ?? 0) }}
                        </span>
                    </div>
                </div>
                <div style="margin-top: 4px; font-size: 11px; color: var(--vac-text-muted);">
                    Indexing query raw key
                </div>
            </div>

            {{-- Katalog Terputus --}}
            <div class="vac-card">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--vac-text-muted);">Katalog Terputus</span>
                    <span style="font-size: 16px;">🔗</span>
                </div>
                <div style="margin-top: 8px; display: flex; align-items: center; gap: 8px; font-size: 12px; font-family: monospace;">
                    <span title="Brand tidak ter-link" style="color: {{ ($summary['unlinked_brand'] ?? 0) > 0 ? '#ef4444' : '#10b981' }}; font-weight: 700;">
                        B:{{ $summary['unlinked_brand'] ?? 0 }}
                    </span>
                    <span style="color: var(--vac-border-sub);">|</span>
                    <span title="Model tidak ter-link" style="color: {{ ($summary['unlinked_model'] ?? 0) > 0 ? '#ef4444' : '#10b981' }}; font-weight: 700;">
                        M:{{ $summary['unlinked_model'] ?? 0 }}
                    </span>
                    <span style="color: var(--vac-border-sub);">|</span>
                    <span title="Type tidak ter-link" style="color: {{ ($summary['unlinked_type'] ?? 0) > 0 ? '#ef4444' : '#10b981' }}; font-weight: 700;">
                        T:{{ $summary['unlinked_type'] ?? 0 }}
                    </span>
                </div>
                <div style="margin-top: 4px; font-size: 11px; color: var(--vac-text-muted);">
                    Brand / Model / Type unlinked
                </div>
            </div>
        </div>

        {{-- 3. FILTER & SEARCH TOOLBAR --}}
        <x-filament::section>
            <div style="display: flex; flex-direction: column; gap: 14px;">
                {{-- Row 1: Kategori Masalah Chips --}}
                <div style="display: flex; flex-wrap: wrap; gap: 8px; align-items: center;">
                    <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--vac-text-muted); margin-right: 4px;">
                        Kategori Masalah:
                    </span>
                    @foreach ($filters as $key => $label)
                        @php
                            $count = $summary[$key] ?? null;
                            $hasCount = in_array($key, ['problem', 'no_key', 'dup', 'unlinked_brand', 'unlinked_model', 'unlinked_type', 'no_category', 'no_powertrain']);
                        @endphp
                        <button type="button"
                                class="vac-chip {{ $filter === $key ? 'vac-chip-active' : '' }}"
                                wire:click="$set('filter', '{{ $key }}')">
                            <span>{{ $label }}</span>
                            @if ($hasCount && ($count ?? 0) > 0)
                                <span style="font-size: 10px; padding: 1px 6px; border-radius: 999px; background: {{ $filter === $key ? 'rgba(255,255,255,0.25)' : 'rgba(239, 68, 68, 0.15)' }}; color: {{ $filter === $key ? '#ffffff' : '#ef4444' }}; font-weight: 800;">
                                    {{ number_format($count) }}
                                </span>
                            @endif
                        </button>
                    @endforeach
                </div>

                {{-- Row 2: Search Box & Dropdown Filters --}}
                <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between; border-top: 1px solid var(--vac-border-sub); padding-top: 12px;">
                    <div style="flex: 1; min-width: 260px; max-width: 440px;">
                        <input type="text"
                               wire:model.live.debounce.400ms="search"
                               placeholder="🔍 Cari Brand, Model, Type, atau Raw Gabungan..."
                               class="vac-input-control"
                               style="width: 100%;" />
                    </div>

                    <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center;">
                        {{-- Powertrain Filter --}}
                        <label style="display: flex; align-items: center; gap: 6px; font-size: 12.5px; font-weight: 600; color: var(--vac-text-muted);">
                            Powertrain:
                            <select wire:model.live="powertrainFilter" class="vac-input-control" style="min-width: 110px;">
                                <option value="all">Semua</option>
                                <option value="BEV">⚡ BEV</option>
                                <option value="PHEV">🔋 PHEV</option>
                                <option value="HEV">🌿 HEV</option>
                                <option value="ICE">⛽ ICE</option>
                            </select>
                        </label>

                        {{-- Tampilkan Baris --}}
                        <label style="display: flex; align-items: center; gap: 6px; font-size: 12.5px; font-weight: 600; color: var(--vac-text-muted);">
                            Tampilkan:
                            <select wire:model.live="perPage" class="vac-input-control" style="min-width: 80px;">
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                                <option value="200">200</option>
                            </select>
                        </label>

                        @if ($filter !== 'all' || $search !== '' || $powertrainFilter !== 'all')
                            <button type="button" wire:click="resetFilters" class="vac-btn" style="color: #ef4444; border-color: rgba(239, 68, 68, 0.4);">
                                <span>Reset Filter</span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </x-filament::section>

        {{-- 4. TABEL AUDIT DATA --}}
        <x-filament::section>
            <div style="overflow-x: auto; margin: -16px; border-radius: 12px;">
                <table class="vac-table">
                    <thead>
                        <tr>
                            <th style="min-width: 280px;">BRAND MODEL TYPE (RAW)</th>
                            <th style="min-width: 150px;">BRAND → KATALOG</th>
                            <th style="min-width: 160px;">MODEL → KATALOG</th>
                            <th style="min-width: 170px;">TYPE → KATALOG</th>
                            <th style="min-width: 90px; text-align: center;">POWERTRAIN</th>
                            <th style="min-width: 130px;">KATEGORI & SIZE</th>
                            <th style="min-width: 180px;">STATUS AUDIT</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $r)
                            @php
                                $pt = strtoupper(trim((string) $r->powertrain));
                                $ptClass = match($pt) {
                                    'BEV' => 'vac-badge-bev',
                                    'PHEV' => 'vac-badge-phev',
                                    'HEV' => 'vac-badge-hev',
                                    'ICE' => 'vac-badge-ice',
                                    default => 'vac-badge-warn',
                                };
                            @endphp
                            <tr>
                                {{-- BRAND MODEL TYPE (RAW) --}}
                                <td>
                                    <div style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 12.5px; font-weight: 700; color: var(--vac-text-title); line-height: 1.4;">
                                        {{ $r->raw_gabungan ?: '(kosong)' }}
                                    </div>
                                    <div style="margin-top: 3px; font-size: 10.5px; color: var(--vac-text-muted); font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; display: flex; align-items: center; gap: 4px;">
                                        <span style="opacity: 0.6;">KEY:</span>
                                        @if ($r->raw_gabungan_key)
                                            <span style="background: var(--vac-bg-sub); padding: 1px 5px; border-radius: 4px; border: 1px solid var(--vac-border-sub);">
                                                {{ $r->raw_gabungan_key }}
                                            </span>
                                        @else
                                            <span class="vac-badge vac-badge-danger" style="font-size: 10px; padding: 1px 5px;">— tanpa key —</span>
                                        @endif
                                        @if ($r->fuel)
                                            <span class="vac-badge vac-badge-gray" style="font-size: 10px; padding: 0 4px; margin-left: 4px;">{{ $r->fuel }}</span>
                                        @endif
                                    </div>
                                </td>

                                {{-- BRAND --}}
                                <td>
                                    <div style="font-weight: 600; color: var(--vac-text-title);">
                                        {{ $r->brand_name ?: '—' }}
                                    </div>
                                    @if ($r->brand_vehicle_id !== null)
                                        <div style="margin-top: 3px;">
                                            <span class="vac-badge vac-badge-ok" style="font-size: 10.5px;">
                                                ✓ {{ $r->audit_brand_catalog ?? 'Tersambung' }}
                                            </span>
                                        </div>
                                    @else
                                        <div style="margin-top: 3px;">
                                            <span class="vac-badge vac-badge-danger" style="font-size: 10.5px;">
                                                ✗ tidak ter-link
                                            </span>
                                        </div>
                                    @endif
                                </td>

                                {{-- MODEL --}}
                                <td>
                                    <div style="font-weight: 600; color: var(--vac-text-title);">
                                        {{ $r->model_name ?: '—' }}
                                    </div>
                                    @if ($r->model_vehicle_id !== null)
                                        <div style="margin-top: 3px;">
                                            <span class="vac-badge vac-badge-ok" style="font-size: 10.5px;">
                                                ✓ {{ $r->audit_model_catalog ?? 'Tersambung' }}
                                            </span>
                                        </div>
                                    @elseif ($r->model_name)
                                        <div style="margin-top: 3px;">
                                            <span class="vac-badge vac-badge-danger" style="font-size: 10.5px;">
                                                ✗ tidak ter-link
                                            </span>
                                        </div>
                                    @else
                                        <div style="margin-top: 3px; font-size: 11px; color: var(--vac-text-muted);">—</div>
                                    @endif
                                </td>

                                {{-- TYPE --}}
                                <td>
                                    <div style="font-weight: 500; color: var(--vac-text-title);">
                                        {{ $r->type_name ?: '—' }}
                                    </div>
                                    @if ($r->type_vehicle_id !== null)
                                        <div style="margin-top: 3px;">
                                            <span class="vac-badge vac-badge-ok" style="font-size: 10.5px;">
                                                ✓ {{ $r->audit_type_catalog ?? 'Tersambung' }}
                                            </span>
                                        </div>
                                    @elseif (trim((string) $r->type_name) !== '')
                                        <div style="margin-top: 3px;">
                                            <span class="vac-badge vac-badge-danger" style="font-size: 10.5px;">
                                                ✗ tidak ter-link
                                            </span>
                                        </div>
                                    @else
                                        <div style="margin-top: 3px; font-size: 11px; color: var(--vac-text-muted);">—</div>
                                    @endif
                                </td>

                                {{-- POWERTRAIN --}}
                                <td style="text-align: center;">
                                    @if (trim((string) $r->powertrain) !== '')
                                        <span class="vac-badge {{ $ptClass }}">
                                            {{ $r->powertrain }}
                                        </span>
                                    @else
                                        <span class="vac-badge vac-badge-danger">
                                            kosong
                                        </span>
                                    @endif
                                </td>

                                {{-- KATEGORI & SIZE --}}
                                <td>
                                    @if (trim((string) $r->category) !== '')
                                        <div style="font-weight: 600; color: var(--vac-text-title);">
                                            {{ $r->category }}
                                        </div>
                                        @if (trim((string) $r->size_class) !== '')
                                            <div style="margin-top: 2px;">
                                                <span class="vac-badge vac-badge-gray" style="font-size: 10px;">
                                                    {{ $r->size_class }}
                                                </span>
                                            </div>
                                        @endif
                                    @else
                                        <span class="vac-badge vac-badge-danger">kategori kosong</span>
                                    @endif
                                </td>

                                {{-- MASALAH AUDIT --}}
                                <td>
                                    @if ($r->audit_problems === [])
                                        <span class="vac-badge vac-badge-ok">
                                            ✓ Aman
                                        </span>
                                    @else
                                        <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                                            @foreach ($r->audit_problems as $p)
                                                <span class="vac-badge vac-badge-danger" style="font-size: 10.5px;">
                                                    ⚠ {{ $p }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="padding: 48px 16px; text-align: center;">
                                    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px;">
                                        <span style="font-size: 32px;">🔍</span>
                                        <div style="font-size: 15px; font-weight: 700; color: var(--vac-text-title);">
                                            Tidak Ada Baris yang Sesuai
                                        </div>
                                        <div style="font-size: 12.5px; color: var(--vac-text-muted); max-width: 400px; line-height: 1.4;">
                                            Tidak ditemukan data Vehicle Connecting yang cocok dengan filter atau kata kunci saat ini.
                                        </div>
                                        @if ($filter !== 'all' || $search !== '' || $powertrainFilter !== 'all')
                                            <button type="button" wire:click="resetFilters" class="vac-btn" style="margin-top: 8px;">
                                                <span>🔄 Reset Semua Filter</span>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        {{-- 5. PAGINASI ELEGAN DENGAN GOTO --}}
        <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; padding: 0 4px;">
            <div style="font-size: 12.5px; color: var(--vac-text-muted);">
                Menampilkan <strong>{{ number_format($rows->firstItem() ?? 0) }}–{{ number_format($rows->lastItem() ?? 0) }}</strong>
                dari <strong>{{ number_format($rows->total()) }}</strong> baris
            </div>

            <div style="display: flex; flex-wrap: wrap; gap: 6px; align-items: center;">
                @if ($rows->onFirstPage())
                    <span class="vac-chip" style="opacity: 0.4; cursor: not-allowed;">‹ Prev</span>
                @else
                    <button type="button" class="vac-chip" wire:click="gotoPage({{ $rows->currentPage() - 1 }})">‹ Prev</button>
                @endif

                @php
                    $current = $rows->currentPage();
                    $last = $rows->lastPage();
                    $window = range(max(1, $current - 2), min($last, $current + 2));
                @endphp

                @if ($window[0] > 1)
                    <button type="button" class="vac-chip" wire:click="gotoPage(1)">1</button>
                    @if ($window[0] > 2) <span style="color: var(--vac-text-muted);">…</span> @endif
                @endif

                @foreach ($window as $page)
                    @if ($page === $current)
                        <span class="vac-chip vac-chip-active" style="cursor: default;">{{ $page }}</span>
                    @else
                        <button type="button" class="vac-chip" wire:click="gotoPage({{ $page }})">{{ $page }}</button>
                    @endif
                @endforeach

                @if ($window[count($window) - 1] < $last)
                    @if ($window[count($window) - 1] < $last - 1) <span style="color: var(--vac-text-muted);">…</span> @endif
                    <button type="button" class="vac-chip" wire:click="gotoPage({{ $last }})">{{ $last }}</button>
                @endif

                @if ($rows->hasMorePages())
                    <button type="button" class="vac-chip" wire:click="gotoPage({{ $rows->currentPage() + 1 }})">Next ›</button>
                @else
                    <span class="vac-chip" style="opacity: 0.4; cursor: not-allowed;">Next ›</span>
                @endif
            </div>
        </div>

    </div>
</x-filament-panels::page>
