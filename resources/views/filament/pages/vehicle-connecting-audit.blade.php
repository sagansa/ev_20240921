<x-filament-panels::page>
    <style>
    :root {
        --vau-bg-card: #ffffff;
        --vau-border: rgba(156, 163, 175, 0.25);
        --vau-title: #111827;
        --vau-body: #374151;
        --vau-muted: #6b7280;
        --vau-th-bg: #f9fafb;
        --vau-row-hover: rgba(156, 163, 175, 0.08);
    }

    .dark, [data-theme="dark"] {
        --vau-bg-card: rgba(255, 255, 255, 0.04);
        --vau-border: rgba(255, 255, 255, 0.1);
        --vau-title: #f9fafb;
        --vau-body: #e5e7eb;
        --vau-muted: #9ca3af;
        --vau-th-bg: #111827;
        --vau-row-hover: rgba(156, 163, 175, 0.06);
    }

    .vau-card {
        border: 1px solid var(--vau-border);
        border-radius: 12px;
        padding: 12px 16px;
        background: var(--vau-bg-card);
    }
    .vau-badge {
        display: inline-flex;
        align-items: center;
        padding: 2px 8px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
        line-height: 1.3;
        white-space: nowrap;
    }
    .vau-badge-danger { background: rgba(239, 68, 68, 0.15); color: #ef4444; }
    .vau-badge-warn { background: rgba(245, 158, 11, 0.18); color: #b45309; }
    .vau-badge-ok { background: rgba(16, 185, 129, 0.15); color: #059669; }
    .vau-badge-gray { background: rgba(156, 163, 175, 0.15); color: #4b5563; }
    .dark .vau-badge-warn { color: #fbbf24; }
    .dark .vau-badge-ok { color: #34d399; }
    .dark .vau-badge-gray { color: #d1d5db; }
    .vau-input {
        background: transparent;
        color: var(--vau-title);
        border: 1px solid var(--vau-border);
        border-radius: 8px;
        padding: 8px 12px;
        font-size: 13px;
        outline: none;
    }
    .vau-input:focus { border-color: #10b981; }
    .vau-chip {
        padding: 6px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        border: 1px solid var(--vau-border);
        background: transparent;
        color: var(--vau-body);
        white-space: nowrap;
    }
    .vau-chip:hover { background: rgba(156, 163, 175, 0.12); }
    .vau-chip-active {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: #ffffff;
        border-color: transparent;
    }
    .vau-table { width: 100%; font-size: 12.5px; text-align: left; border-collapse: collapse; }
    .vau-table th {
        padding: 8px 10px;
        font-weight: 600;
        color: var(--vau-muted);
        border-bottom: 1px solid var(--vau-border);
        white-space: nowrap;
        position: sticky;
        top: 0;
        background: var(--vau-th-bg);
        z-index: 1;
    }
    .vau-table td {
        padding: 7px 10px;
        border-bottom: 1px solid rgba(156, 163, 175, 0.12);
        vertical-align: top;
        color: var(--vau-body);
    }
    .vau-table tr:hover td { background: var(--vau-row-hover); }
</style>

    <div style="display: flex; flex-direction: column; gap: 20px;">

        {{-- 1. HERO HEADER & SHORTCUT ACTIONS --}}
        <div class="vca-glass" style="padding: 20px 24px; position: relative; overflow: hidden;">
            <div style="position: absolute; right: -50px; top: -50px; width: 200px; height: 200px; background: radial-gradient(circle, rgba(16, 185, 129, 0.16) 0%, rgba(16, 185, 129, 0) 70%); pointer-events: none; border-radius: 9999px;"></div>

            <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: flex-start; gap: 16px;">
                <div style="max-width: 680px;">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
                        <span style="font-size: 20px;">📋</span>
                        <h2 style="margin: 0; font-size: 18px; font-weight: 800; color: var(--vca-text-title); letter-spacing: -0.3px;">
                            Audit Master Connecting GAIKINDO
                        </h2>
                        <span class="vca-badge vca-badge-emerald">🛡️ Read-Only Diagnostic</span>
                    </div>
                    <p style="margin: 0; font-size: 13px; color: var(--vca-text-muted); line-height: 1.5;">
                        Inspeksi integritas baris pemetaan <code>BRAND MODEL TYPE</code> (teks mentah laporan) terhadap master katalog kendaraan (Brand, Model, Type) serta verifikasi kelengkapan klasifikasi Powertrain, Kategori, dan Ukuran.
                    </p>
                </div>

                <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                    <a href="{{ \App\Filament\Pages\VehicleConnectingSync::getUrl() }}" class="vca-btn vca-btn-secondary" style="font-size: 12px; padding: 7px 13px;">
                        <span>⚡ Sinkronisasi CONNECTING</span>
                    </a>
                    <a href="{{ \App\Filament\Pages\VehicleHierarchyExplorer::getUrl() }}" class="vca-btn vca-btn-secondary" style="font-size: 12px; padding: 7px 13px;">
                        <span>🌳 Pohon Hierarki</span>
                    </a>
                    <button type="button" wire:click="download" wire:loading.attr="disabled" wire:target="download"
                            class="vca-btn vca-btn-primary" style="font-size: 12px; padding: 7px 14px;"
                            title="Unduh hasil audit sesuai filter & pencarian aktif (CSV format CONNECTING)">
                        <span wire:loading.remove wire:target="download">⬇ Unduh CSV Audit</span>
                        <span wire:loading wire:target="download">⏳ Menyiapkan…</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- 2. KPI HEALTH & SUMMARY METRICS --}}
        @php
            $healthRate = $summary['health_rate'] ?? 100;
            $healthColor = $healthRate >= 95 ? '#10b981' : ($healthRate >= 80 ? '#f59e0b' : '#ef4444');
        @endphp
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px;">
            {{-- Total Baris --}}
            <div class="vca-kpi-card">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--vca-text-muted);">Total Baris Master</span>
                    <span style="font-size: 16px;">🗃️</span>
                </div>
                <div style="margin-top: 8px; font-size: 24px; font-weight: 800; font-family: monospace; color: var(--vca-text-title);">
                    {{ number_format($summary['total'] ?? 0) }}
                </div>
                <div style="margin-top: 4px; font-size: 11.5px; color: var(--vca-text-muted);">
                    Kombinasi CONNECTING
                </div>
            </div>

            {{-- Health Rate Card --}}
            <div class="vca-kpi-card" style="border-color: {{ ($summary['problem'] ?? 0) > 0 ? 'rgba(245, 158, 11, 0.35)' : 'rgba(16, 185, 129, 0.35)' }};">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: {{ $healthColor }};">Tingkat Kesehatan</span>
                    <span style="font-size: 16px;">{{ $healthRate >= 95 ? '🛡️' : '⚠️' }}</span>
                </div>
                <div style="margin-top: 8px; display: flex; align-items: baseline; gap: 6px;">
                    <span style="font-size: 24px; font-weight: 800; font-family: monospace; color: {{ $healthColor }};">
                        {{ $healthRate }}%
                    </span>
                    <span style="font-size: 11.5px; color: var(--vca-text-muted);">bebas masalah</span>
                </div>
                <div class="vca-progress-container">
                    <div class="vca-progress-fill" style="width: {{ $healthRate }}%; background: {{ $healthColor }};"></div>
                </div>
            </div>

            {{-- Total Bermasalah --}}
            <div class="vca-kpi-card" style="border-color: {{ ($summary['problem'] ?? 0) > 0 ? 'rgba(239, 68, 68, 0.4)' : 'rgba(16, 185, 129, 0.3)' }};">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: {{ ($summary['problem'] ?? 0) > 0 ? '#ef4444' : '#10b981' }};">Bermasalah</span>
                    <span style="font-size: 16px;">{{ ($summary['problem'] ?? 0) > 0 ? '🚨' : '✅' }}</span>
                </div>
                <div style="margin-top: 8px; font-size: 24px; font-weight: 800; font-family: monospace; color: {{ ($summary['problem'] ?? 0) > 0 ? '#ef4444' : '#10b981' }};">
                    {{ number_format($summary['problem'] ?? 0) }}
                </div>
                <div style="margin-top: 4px; font-size: 11.5px; color: var(--vca-text-muted);">
                    {{ ($summary['problem'] ?? 0) > 0 ? 'Perlu tindakan koreksi' : 'Seluruh baris valid' }}
                </div>
            </div>

            {{-- Tanpa Raw Key & Duplikat --}}
            <div class="vca-kpi-card">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--vca-text-muted);">Masalah Kunci</span>
                    <span style="font-size: 16px;">🔑</span>
                </div>
                <div style="margin-top: 8px; display: flex; align-items: baseline; gap: 12px;">
                    <div>
                        <span style="font-size: 11px; color: var(--vca-text-muted);">Tanpa Key: </span>
                        <span style="font-size: 16px; font-weight: 800; font-family: monospace; color: {{ ($summary['no_key'] ?? 0) > 0 ? '#f59e0b' : '#10b981' }};">
                            {{ number_format($summary['no_key'] ?? 0) }}
                        </span>
                    </div>
                    <div>
                        <span style="font-size: 11px; color: var(--vca-text-muted);">Duplikat: </span>
                        <span style="font-size: 16px; font-weight: 800; font-family: monospace; color: {{ ($summary['dup'] ?? 0) > 0 ? '#f59e0b' : '#10b981' }};">
                            {{ number_format($summary['dup'] ?? 0) }}
                        </span>
                    </div>
                </div>
                <div style="margin-top: 4px; font-size: 11.5px; color: var(--vca-text-muted);">
                    Integritas indexing query
                </div>
            </div>

            {{-- Unlinked Entities --}}
            <div class="vca-kpi-card">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--vca-text-muted);">Katalog Terputus</span>
                    <span style="font-size: 16px;">🔗</span>
                </div>
                <div style="margin-top: 8px; display: flex; align-items: center; gap: 10px; font-size: 12px; font-family: monospace;">
                    <span title="Brand tidak ter-link" style="color: {{ ($summary['unlinked_brand'] ?? 0) > 0 ? '#ef4444' : '#10b981' }}; font-weight: 700;">
                        B:{{ $summary['unlinked_brand'] ?? 0 }}
                    </span>
                    <span style="color: var(--vca-border-sub);">|</span>
                    <span title="Model tidak ter-link" style="color: {{ ($summary['unlinked_model'] ?? 0) > 0 ? '#ef4444' : '#10b981' }}; font-weight: 700;">
                        M:{{ $summary['unlinked_model'] ?? 0 }}
                    </span>
                    <span style="color: var(--vca-border-sub);">|</span>
                    <span title="Type tidak ter-link" style="color: {{ ($summary['unlinked_type'] ?? 0) > 0 ? '#ef4444' : '#10b981' }}; font-weight: 700;">
                        T:{{ $summary['unlinked_type'] ?? 0 }}
                    </span>
                </div>
                <div style="margin-top: 4px; font-size: 11.5px; color: var(--vca-text-muted);">
                    Brand / Model / Type unlinked
                </div>
            </div>
        </div>

        {{-- 3. FILTER CHIPS & SEARCH TOOLBAR --}}
        <div class="vca-glass" style="padding: 16px 20px;">
            <div style="display: flex; flex-direction: column; gap: 14px;">
                {{-- Row 1: Problem filter chips --}}
                <div style="display: flex; flex-wrap: wrap; gap: 8px; align-items: center;">
                    <span style="font-size: 11.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--vca-text-muted); margin-right: 4px;">
                        Kategori Masalah:
                    </span>
                    @foreach ($filters as $key => $label)
                        @php
                            $count = $summary[$key] ?? null;
                            $hasCount = in_array($key, ['problem', 'no_key', 'dup', 'unlinked_brand', 'unlinked_model', 'unlinked_type', 'no_category', 'no_powertrain']);
                        @endphp
                        <button type="button"
                                class="vca-chip {{ $filter === $key ? 'vca-chip-active' : '' }}"
                                wire:click="$set('filter', '{{ $key }}')">
                            <span>{{ $label }}</span>
                            @if ($hasCount && ($count ?? 0) > 0)
                                <span style="font-size: 10.5px; padding: 1px 6px; border-radius: 999px; background: {{ $filter === $key ? 'rgba(255,255,255,0.25)' : 'rgba(244, 63, 94, 0.15)' }}; color: {{ $filter === $key ? '#fff' : '#f43f5e' }}; font-weight: 800;">
                                    {{ number_format($count) }}
                                </span>
                            @endif
                        </button>
                    @endforeach
                </div>

                <div style="height: 1px; background: var(--vca-border-sub);"></div>

                {{-- Row 2: Search Box & Additional Select Filters --}}
                <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between;">
                    {{-- Search Input --}}
                    <div style="flex: 1; min-width: 260px; position: relative;">
                        <span style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-size: 14px; color: var(--vca-text-muted); pointer-events: none;">
                            🔍
                        </span>
                        <input type="text"
                               class="vca-input-control"
                               style="width: 100%; padding-left: 36px; padding-right: 28px;"
                               placeholder="Cari teks (raw gabungan, brand, model, atau type)…"
                               wire:model.live.debounce.400ms="search" />
                        @if ($search !== '')
                            <button type="button"
                                    wire:click="$set('search', '')"
                                    style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; font-size: 14px; color: var(--vca-text-muted); cursor: pointer;"
                                    title="Bersihkan pencarian">
                                ✕
                            </button>
                        @endif
                    </div>

                    {{-- Powertrain Filter --}}
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <span style="font-size: 12px; font-weight: 600; color: var(--vca-text-muted);">Powertrain:</span>
                        <select wire:model.live="powertrainFilter" class="vca-input-control" style="min-width: 110px;">
                            <option value="all">Semua</option>
                            <option value="BEV">⚡ BEV</option>
                            <option value="PHEV">🔋 PHEV</option>
                            <option value="HEV">🌿 HEV</option>
                            <option value="ICE">⛽ ICE</option>
                        </select>
                    </div>

                    {{-- Baris per Halaman --}}
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <span style="font-size: 12px; font-weight: 600; color: var(--vca-text-muted);">Tampilkan:</span>
                        <select wire:model.live="perPage" class="vca-input-control" style="min-width: 80px;">
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="200">200</option>
                        </select>
                    </div>

                    {{-- Reset Filter Button --}}
                    @if ($filter !== 'all' || $search !== '' || $powertrainFilter !== 'all')
                        <button type="button" wire:click="resetFilters" class="vca-btn vca-btn-secondary" style="font-size: 12px; padding: 6px 12px;">
                            <span>🔄 Reset Filter</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>

        {{-- INFO BAR --}}
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; font-size: 12px; color: var(--vca-text-muted); padding: 0 4px;">
            <div>
                Menampilkan <strong>{{ number_format($rows->firstItem() ?: 0) }}</strong> sampai <strong>{{ number_format($rows->lastItem() ?: 0) }}</strong> dari total <strong>{{ number_format($rows->total()) }}</strong> baris yang sesuai kriteria.
            </div>
            <div style="display: flex; align-items: center; gap: 6px;">
                <span class="vca-badge vca-badge-slate">Tip</span>
                <span>Unduhan CSV menyertakan 8 kolom pertama standar CONNECTING untuk perbaikan data.</span>
            </div>
        </div>

        {{-- 4. EXECUTIVE AUDIT TABLE --}}
        <div class="vca-glass" style="padding: 0; overflow: hidden; border-radius: 12px;">
            <div style="overflow-x: auto; max-height: 72vh; overflow-y: auto;">
                <table class="vca-table">
                    <thead>
                        <tr>
                            <th style="min-width: 280px;">BRAND MODEL TYPE (RAW)</th>
                            <th style="min-width: 140px;">BRAND → KATALOG</th>
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
                                $ptBadgeClass = match($pt) {
                                    'BEV' => 'vca-badge-emerald',
                                    'PHEV' => 'vca-badge-sky',
                                    'HEV' => 'vca-badge-indigo',
                                    'ICE' => 'vca-badge-slate',
                                    default => 'vca-badge-amber',
                                };
                            @endphp
                            <tr>
                                {{-- BRAND MODEL TYPE RAW --}}
                                <td>
                                    <div style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 12.5px; font-weight: 700; color: var(--vca-text-title); line-height: 1.4;">
                                        {{ $r->raw_gabungan ?: '(kosong)' }}
                                    </div>
                                    <div style="margin-top: 3px; font-size: 10.5px; color: var(--vca-text-muted); font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; display: flex; align-items: center; gap: 4px;">
                                        <span style="opacity: 0.6;">KEY:</span>
                                        @if ($r->raw_gabungan_key)
                                            <span style="background: var(--vca-bg-sub); padding: 1px 5px; border-radius: 4px; border: 1px solid var(--vca-border-sub);">
                                                {{ $r->raw_gabungan_key }}
                                            </span>
                                        @else
                                            <span class="vca-badge vca-badge-rose" style="font-size: 10px; padding: 1px 5px;">— tanpa key —</span>
                                        @endif
                                        @if ($r->fuel)
                                            <span class="vca-badge vca-badge-slate" style="font-size: 10px; padding: 0 4px; margin-left: 4px;">{{ $r->fuel }}</span>
                                        @endif
                                    </div>
                                </td>

                                {{-- BRAND --}}
                                <td>
                                    <div style="font-weight: 600; color: var(--vca-text-title); font-size: 12px;">
                                        {{ $r->brand_name ?: '—' }}
                                    </div>
                                    @if ($r->brand_vehicle_id !== null)
                                        <div style="margin-top: 3px; display: inline-flex; align-items: center; gap: 3px;" title="ID: {{ $r->brand_vehicle_id }}">
                                            <span class="vca-badge vca-badge-emerald" style="font-size: 10.5px;">
                                                ✓ {{ $r->audit_brand_catalog ?? 'Tersambung' }}
                                            </span>
                                        </div>
                                    @else
                                        <div style="margin-top: 3px;">
                                            <span class="vca-badge vca-badge-rose" style="font-size: 10.5px;">
                                                ✗ tidak ter-link
                                            </span>
                                        </div>
                                    @endif
                                </td>

                                {{-- MODEL --}}
                                <td>
                                    <div style="font-weight: 600; color: var(--vca-text-title); font-size: 12px;">
                                        {{ $r->model_name ?: '—' }}
                                    </div>
                                    @if ($r->model_vehicle_id !== null)
                                        <div style="margin-top: 3px; display: inline-flex; align-items: center; gap: 3px;" title="ID: {{ $r->model_vehicle_id }}">
                                            <span class="vca-badge vca-badge-emerald" style="font-size: 10.5px;">
                                                ✓ {{ $r->audit_model_catalog ?? 'Tersambung' }}
                                            </span>
                                        </div>
                                    @elseif ($r->model_name)
                                        <div style="margin-top: 3px;">
                                            <span class="vca-badge vca-badge-rose" style="font-size: 10.5px;">
                                                ✗ tidak ter-link
                                            </span>
                                        </div>
                                    @else
                                        <div style="margin-top: 3px; font-size: 11px; color: var(--vca-text-muted);">—</div>
                                    @endif
                                </td>

                                {{-- TYPE --}}
                                <td>
                                    <div style="font-weight: 500; color: var(--vca-text-title); font-size: 12px;">
                                        {{ $r->type_name ?: '—' }}
                                    </div>
                                    @if ($r->type_vehicle_id !== null)
                                        <div style="margin-top: 3px; display: inline-flex; align-items: center; gap: 3px;" title="ID: {{ $r->type_vehicle_id }}">
                                            <span class="vca-badge vca-badge-emerald" style="font-size: 10.5px;">
                                                ✓ {{ $r->audit_type_catalog ?? 'Tersambung' }}
                                            </span>
                                        </div>
                                    @elseif (trim((string) $r->type_name) !== '')
                                        <div style="margin-top: 3px;">
                                            <span class="vca-badge vca-badge-rose" style="font-size: 10.5px;">
                                                ✗ tidak ter-link
                                            </span>
                                        </div>
                                    @else
                                        <div style="margin-top: 3px; font-size: 11px; color: var(--vca-text-muted);">—</div>
                                    @endif
                                </td>

                                {{-- POWERTRAIN --}}
                                <td style="text-align: center;">
                                    @if (trim((string) $r->powertrain) !== '')
                                        <span class="vca-badge {{ $ptBadgeClass }}">
                                            {{ $r->powertrain }}
                                        </span>
                                    @else
                                        <span class="vca-badge vca-badge-rose">
                                            kosong
                                        </span>
                                    @endif
                                </td>

                                {{-- KATEGORI & SIZE --}}
                                <td>
                                    @if (trim((string) $r->category) !== '')
                                        <div style="font-weight: 600; color: var(--vca-text-title);">
                                            {{ $r->category }}
                                        </div>
                                        @if (trim((string) $r->size_class) !== '')
                                            <div style="margin-top: 2px;">
                                                <span class="vca-badge vca-badge-slate" style="font-size: 10px;">
                                                    {{ $r->size_class }}
                                                </span>
                                            </div>
                                        @endif
                                    @else
                                        <span class="vca-badge vca-badge-rose">kategori kosong</span>
                                    @endif
                                </td>

                                {{-- MASALAH AUDIT --}}
                                <td>
                                    @if ($r->audit_problems === [])
                                        <span class="vca-badge vca-badge-emerald">
                                            ✓ Aman & Ter-link
                                        </span>
                                    @else
                                        <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                                            @foreach ($r->audit_problems as $p)
                                                <span class="vca-badge vca-badge-rose" style="font-size: 10.5px;">
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
                                        <div style="font-size: 15px; font-weight: 700; color: var(--vca-text-title);">
                                            Tidak Ada Baris yang Sesuai
                                        </div>
                                        <div style="font-size: 12.5px; color: var(--vca-text-muted); max-width: 400px; line-height: 1.4;">
                                            Tidak ditemukan data Vehicle Connecting yang cocok dengan filter atau kata kunci pencarian saat ini.
                                        </div>
                                        @if ($filter !== 'all' || $search !== '' || $powertrainFilter !== 'all')
                                            <button type="button" wire:click="resetFilters" class="vca-btn vca-btn-secondary" style="margin-top: 8px; font-size: 12px;">
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
        </div>

        {{-- 5. PAGINATION CONTROLS --}}
        <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px;">
            <div style="font-size: 12.5px; color: var(--vau-muted);">
                Menampilkan <strong style="color: #e5e7eb;">{{ number_format($rows->firstItem() ?? 0) }}–{{ number_format($rows->lastItem() ?? 0) }}</strong>
                dari <strong style="color: #e5e7eb;">{{ number_format($rows->total()) }}</strong> baris
            </div>

            <div style="display: flex; flex-wrap: wrap; gap: 6px; align-items: center;">
                @if ($rows->onFirstPage())
                    <span class="vau-chip" style="opacity: 0.4; cursor: default;">‹ Prev</span>
                @else
                    <button type="button" class="vau-chip" wire:click="gotoPage({{ $rows->currentPage() - 1 }})">‹ Prev</button>
                @endif

                @php
                    $current = $rows->currentPage();
                    $last = $rows->lastPage();
                    $window = range(max(1, $current - 2), min($last, $current + 2));
                @endphp

                @if ($window[0] > 1)
                    <button type="button" class="vau-chip" wire:click="gotoPage(1)">1</button>
                    @if ($window[0] > 2) <span style="color: var(--vau-muted);">…</span> @endif
                @endif

                @foreach ($window as $page)
                    @if ($page === $current)
                        <span class="vau-chip vau-chip-active" style="cursor: default;">{{ $page }}</span>
                    @else
                        <button type="button" class="vau-chip" wire:click="gotoPage({{ $page }})">{{ $page }}</button>
                    @endif
                @endforeach

                @if ($window[count($window) - 1] < $last)
                    @if ($window[count($window) - 1] < $last - 1) <span style="color: var(--vau-muted);">…</span> @endif
                    <button type="button" class="vau-chip" wire:click="gotoPage({{ $last }})">{{ $last }}</button>
                @endif

                @if ($rows->hasMorePages())
                    <button type="button" class="vau-chip" wire:click="gotoPage({{ $rows->currentPage() + 1 }})">Next ›</button>
                @else
                    <span class="vau-chip" style="opacity: 0.4; cursor: default;">Next ›</span>
                @endif
            </div>
        </div>
    </div>
</x-filament-panels::page>
