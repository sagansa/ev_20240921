<x-filament-panels::page>
    <style>
        :root {
            --vcs-bg-card: #ffffff;
            --vcs-bg-header: rgba(0, 0, 0, 0.015);
            --vcs-bg-sub: rgba(0, 0, 0, 0.02);
            --vcs-border: rgba(156, 163, 175, 0.25);
            --vcs-border-sub: rgba(156, 163, 175, 0.16);
            --vcs-text-title: #111827;
            --vcs-text-body: #374151;
            --vcs-text-muted: #6b7280;
            --vcs-input-bg: #ffffff;
            --vcs-input-border: rgba(156, 163, 175, 0.35);
            --vcs-th-bg: #f9fafb;
            --vcs-row-hover: rgba(156, 163, 175, 0.06);
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
            --vcs-th-bg: #111827;
            --vcs-row-hover: rgba(255, 255, 255, 0.04);
        }

        .vcs-card {
            border: 1px solid var(--vcs-border);
            border-radius: 12px;
            padding: 16px;
            background: var(--vcs-bg-card);
            color: var(--vcs-text-body);
            transition: all 0.15s ease;
        }

        .vcs-grid-4 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 14px;
        }

        .vcs-grid-5 {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 12px;
        }

        @media (max-width: 1280px) {
            .vcs-grid-5 {
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            }
        }

        .vcs-grid-6 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
        }

        .vcs-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            line-height: 1.3;
            white-space: nowrap;
        }

        .vcs-badge-bev { background: rgba(16, 185, 129, 0.15); color: #059669; }
        .dark .vcs-badge-bev, [data-theme="dark"] .vcs-badge-bev { background: rgba(16, 185, 129, 0.25); color: #34d399; }
        
        .vcs-badge-phev { background: rgba(14, 165, 233, 0.15); color: #0284c7; }
        .dark .vcs-badge-phev, [data-theme="dark"] .vcs-badge-phev { background: rgba(14, 165, 233, 0.25); color: #38bdf8; }
        
        .vcs-badge-hev { background: rgba(99, 102, 241, 0.15); color: #4f46e5; }
        .dark .vcs-badge-hev, [data-theme="dark"] .vcs-badge-hev { background: rgba(99, 102, 241, 0.25); color: #818cf8; }
        
        .vcs-badge-ice { background: rgba(107, 114, 128, 0.15); color: #4b5563; }
        .dark .vcs-badge-ice, [data-theme="dark"] .vcs-badge-ice { background: rgba(107, 114, 128, 0.25); color: #9ca3af; }

        .vcs-badge-danger { background: rgba(239, 68, 68, 0.15); color: #dc2626; }
        .dark .vcs-badge-danger, [data-theme="dark"] .vcs-badge-danger { background: rgba(239, 68, 68, 0.25); color: #f87171; }

        .vcs-badge-warn { background: rgba(245, 158, 11, 0.15); color: #d97706; }
        .dark .vcs-badge-warn, [data-theme="dark"] .vcs-badge-warn { background: rgba(245, 158, 11, 0.25); color: #fbbf24; }

        .vcs-badge-ok { background: rgba(16, 185, 129, 0.15); color: #059669; }
        .dark .vcs-badge-ok, [data-theme="dark"] .vcs-badge-ok { background: rgba(16, 185, 129, 0.25); color: #34d399; }

        .vcs-badge-gray { background: rgba(156, 163, 175, 0.15); color: #4b5563; }
        .dark .vcs-badge-gray, [data-theme="dark"] .vcs-badge-gray { background: rgba(255, 255, 255, 0.08); color: #d1d5db; }

        .vcs-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: 8px;
            font-size: 12.5px;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid var(--vcs-input-border);
            background: var(--vcs-input-bg);
            color: var(--vcs-text-body);
            transition: all 0.15s ease;
            text-decoration: none;
            line-height: 1.5;
        }
        .vcs-btn:hover:not(:disabled) { background: rgba(156, 163, 175, 0.12); color: var(--vcs-text-title); }
        .vcs-btn:disabled { opacity: 0.6; cursor: not-allowed; }

        .vcs-btn-primary {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-color: transparent;
            color: #ffffff !important;
            font-weight: 700;
        }
        .vcs-btn-primary:hover:not(:disabled) { opacity: 0.92; color: #ffffff !important; }

        .vcs-btn-danger {
            color: #ef4444;
            border-color: rgba(239, 68, 68, 0.4);
        }
        .vcs-btn-danger:hover:not(:disabled) {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
        }

        .vcs-chip {
            padding: 5px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid var(--vcs-border);
            background: var(--vcs-input-bg);
            color: var(--vcs-text-body);
            transition: all 0.15s ease;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .vcs-chip:hover { border-color: rgba(16, 185, 129, 0.5); }
        .vcs-chip-active {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
            color: #ffffff !important;
            border-color: transparent !important;
        }

        .vcs-input-control {
            background: var(--vcs-input-bg);
            color: var(--vcs-text-title);
            border: 1px solid var(--vcs-input-border);
            border-radius: 8px;
            padding: 7px 12px;
            font-size: 13px;
            outline: none;
            transition: border-color 0.15s ease;
        }
        .vcs-input-control:focus { border-color: #10b981; }

        .vcs-progress-bar {
            height: 5px;
            border-radius: 9999px;
            background: rgba(156, 163, 175, 0.2);
            overflow: hidden;
            width: 100%;
            margin-top: 8px;
        }
        .vcs-progress-fill {
            height: 100%;
            border-radius: 9999px;
            transition: width 0.3s ease;
        }

        .vcs-step-box {
            border: 1px solid var(--vcs-border);
            border-radius: 10px;
            background: var(--vcs-bg-card);
            padding: 16px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 14px;
        }

        .vcs-step-num {
            width: 26px;
            height: 26px;
            border-radius: 9999px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 12px;
        }

        .vcs-table {
            width: 100%;
            font-size: 12.5px;
            text-align: left;
            border-collapse: collapse;
        }
        .vcs-table th {
            padding: 9px 12px;
            font-weight: 700;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--vcs-text-muted);
            border-bottom: 1px solid var(--vcs-border);
            white-space: nowrap;
            position: sticky;
            top: 0;
            background: var(--vcs-th-bg);
            z-index: 2;
        }
        .vcs-table td {
            padding: 8px 12px;
            border-bottom: 1px solid var(--vcs-border-sub);
            vertical-align: top;
            color: var(--vcs-text-body);
        }
        .vcs-table tr:hover td { background: var(--vcs-row-hover); }

        .vcs-console {
            background: #0f172a;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 10px;
            padding: 14px 16px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 12.5px;
            line-height: 1.6;
            color: #e2e8f0;
            max-height: 280px;
            overflow-y: auto;
        }
        .dark .vcs-console {
            background: #090d16;
        }
    </style>

    <div style="display: flex; flex-direction: column; gap: 20px;">

        {{-- 1. HEADER & TOP SHORTCUTS --}}
        <div class="vcs-card" style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 16px;">
            <div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <h2 style="margin: 0; font-size: 17px; font-weight: 800; color: var(--vcs-text-title);">
                        Sinkronisasi CONNECTING ke Master Katalog
                    </h2>
                    <span class="vcs-badge vcs-badge-ok">🛡️ Idempoten & Aman</span>
                </div>
                <p style="margin: 4px 0 0; font-size: 12.5px; color: var(--vcs-text-muted);">
                    Alur 3-langkah pembaruan master kendaraan (Brand ➔ Model ➔ Type) dari file CSV CONNECTING & pembaruan otomatis cache Pasar EV.
                </p>
            </div>

            <div style="display: flex; flex-wrap: wrap; gap: 8px; align-items: center;">
                <a href="{{ \App\Filament\Pages\VehicleConnectingAudit::getUrl() }}" class="vcs-btn">
                    <span>📋 Audit Raw Connecting</span>
                </a>
                <a href="{{ \App\Filament\Pages\VehicleHierarchyExplorer::getUrl() }}" class="vcs-btn">
                    <span>🌳 Pohon Hierarki</span>
                </a>
                <a href="{{ \App\Filament\Pages\VehicleSalesPreviewImport::getUrl() }}" class="vcs-btn">
                    <span>🔍 Preview Impor</span>
                </a>
            </div>
        </div>

        {{-- 2. METRICS CARDS (LIVE DATABASE STATUS) --}}
        <div class="vcs-grid-4">
            {{-- Master Connecting --}}
            <div class="vcs-card">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--vcs-text-muted);">Master Connecting</span>
                    <span class="vcs-badge {{ $dbStats['unmappedConnecting'] > 0 ? 'vcs-badge-warn' : 'vcs-badge-ok' }}">
                        {{ $dbStats['mappedPercentage'] }}% Link
                    </span>
                </div>
                <div style="margin-top: 8px; font-size: 22px; font-weight: 800; font-family: monospace; color: var(--vcs-text-title);">
                    {{ number_format($dbStats['totalConnecting']) }}
                </div>
                <div style="margin-top: 4px; font-size: 11.5px; color: var(--vcs-text-muted);">
                    {{ number_format($dbStats['mappedConnecting']) }} terhubung
                    @if ($dbStats['unmappedConnecting'] > 0)
                        · <span style="color: #f59e0b; font-weight: 600;">{{ number_format($dbStats['unmappedConnecting']) }} belum</span>
                    @endif
                </div>
                <div class="vcs-progress-bar">
                    <div class="vcs-progress-fill" style="width: {{ $dbStats['mappedPercentage'] }}%; background: {{ $dbStats['unmappedConnecting'] > 0 ? '#f59e0b' : '#10b981' }};"></div>
                </div>
            </div>

            {{-- Brand Master --}}
            <div class="vcs-card">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--vcs-text-muted);">Brand Terdaftar</span>
                    <span style="font-size: 16px;">🏷️</span>
                </div>
                <div style="margin-top: 8px; font-size: 22px; font-weight: 800; font-family: monospace; color: var(--vcs-text-title);">
                    {{ number_format($dbStats['totalBrands']) }}
                </div>
                <div style="margin-top: 4px; font-size: 11.5px; color: var(--vcs-text-muted);">
                    Brand katalog aktif
                </div>
            </div>

            {{-- Model Master --}}
            <div class="vcs-card">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--vcs-text-muted);">Model Terdaftar</span>
                    <span style="font-size: 16px;">🚗</span>
                </div>
                <div style="margin-top: 8px; font-size: 22px; font-weight: 800; font-family: monospace; color: var(--vcs-text-title);">
                    {{ number_format($dbStats['totalModels']) }}
                </div>
                <div style="margin-top: 4px; font-size: 11.5px; color: var(--vcs-text-muted);">
                    Hierarki model & klasifikasi
                </div>
            </div>

            {{-- Type Master --}}
            <div class="vcs-card">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--vcs-text-muted);">Varian / Type</span>
                    <span style="font-size: 16px;">⚙️</span>
                </div>
                <div style="margin-top: 8px; font-size: 22px; font-weight: 800; font-family: monospace; color: var(--vcs-text-title);">
                    {{ number_format($dbStats['totalTypes']) }}
                </div>
                <div style="margin-top: 4px; font-size: 11.5px; color: var(--vcs-text-muted);">
                    BEV, PHEV, HEV & ICE
                </div>
            </div>
        </div>

        {{-- 3. WORKFLOW PIPELINE SYNC (UPLOAD & LANGKAH-LANGKAH) --}}
        <x-filament::section>
            <x-slot name="heading">
                Pipeline Eksekusi Sinkronisasi (3 Langkah)
            </x-slot>
            <x-slot name="description">
                Unggah file master GAIKINDO CONNECTING lalu jalankan tahapan verifikasi dan sinkronisasi.
            </x-slot>

            <div style="display: flex; flex-direction: column; gap: 16px;">

                {{-- Upload Area --}}
                <div style="border: 2px dashed {{ $csvFile ? 'rgba(16, 185, 129, 0.5)' : 'var(--vcs-border)' }}; border-radius: 10px; padding: 18px 24px; background: var(--vcs-bg-sub); text-align: center; transition: all 0.2s ease;">
                    <input type="file" accept=".csv" wire:model.live="csvFile" id="csvFileInput" style="display: none;" />

                    @if ($csvFile)
                        <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: 14px;">
                            <div style="width: 40px; height: 40px; border-radius: 8px; background: rgba(16, 185, 129, 0.15); display: flex; align-items: center; justify-content: center; font-size: 20px;">
                                📄
                            </div>
                            <div style="text-align: left;">
                                <div style="font-size: 13.5px; font-weight: 700; color: var(--vcs-text-title);">
                                    {{ $csvFile->getClientOriginalName() }}
                                </div>
                                <div style="font-size: 11.5px; color: var(--vcs-text-muted); margin-top: 2px;">
                                    Ukuran: {{ number_format($csvFile->getSize() / 1024, 1) }} KB · Siap diproses
                                </div>
                            </div>
                            <label for="csvFileInput" class="vcs-btn" style="cursor: pointer; margin-left: 10px;">
                                🔄 Ganti File
                            </label>
                            <button type="button" wire:click="removeFile" class="vcs-btn vcs-btn-danger">
                                ✕ Hapus
                            </button>
                        </div>
                    @else
                        <label for="csvFileInput" style="cursor: pointer; display: flex; flex-direction: column; align-items: center; gap: 8px;">
                            <div style="width: 44px; height: 44px; border-radius: 10px; background: var(--vcs-input-bg); border: 1px solid var(--vcs-border); display: flex; align-items: center; justify-content: center; font-size: 20px;">
                                📥
                            </div>
                            <div style="font-size: 13.5px; font-weight: 700; color: var(--vcs-text-title);">
                                Klik untuk memilih file CSV CONNECTING
                            </div>
                            <div style="font-size: 11.5px; color: var(--vcs-text-muted);">
                                Header: <code>BRAND MODEL TYPE, BRAND, MODEL, TYPE, POWERTRAIN, CATEGORY, SIZE</code>
                            </div>
                        </label>
                    @endif
                </div>

                {{-- Action Cards Grid (5 sebaris) --}}
                <div class="vcs-grid-5">
                    {{-- LANGKAH 1: VERIFIKASI (DRY-RUN) --}}
                    <div class="vcs-step-box" style="border-top: 3px solid #06b6d4;">
                        <div>
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                                <div class="vcs-step-num" style="background: rgba(6, 182, 212, 0.15); color: #0891b2;">1</div>
                                <span style="font-size: 13.5px; font-weight: 700; color: var(--vcs-text-title);">Verifikasi (Dry-run)</span>
                            </div>
                            <p style="margin: 0; font-size: 12px; color: var(--vcs-text-muted); line-height: 1.4;">
                                Bandingkan data CSV vs katalog saat ini tanpa mengubah database apapun.
                            </p>
                        </div>
                        <button type="button" wire:click="verify" wire:loading.attr="disabled" wire:target="verify"
                                class="vcs-btn" style="width: 100%; justify-content: center; border-color: rgba(6, 182, 212, 0.5); color: #0891b2;">
                            <span wire:loading.remove wire:target="verify">🔍 Jalankan Verifikasi</span>
                            <span wire:loading wire:target="verify">⏳ Memeriksa Data…</span>
                        </button>
                    </div>

                    {{-- LANGKAH 2: SIMPAN KE MASTER CONNECTING --}}
                    <div class="vcs-step-box" style="border-top: 3px solid #f59e0b;">
                        <div>
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                                <div class="vcs-step-num" style="background: rgba(245, 158, 11, 0.15); color: #d97706;">2</div>
                                <span style="font-size: 13.5px; font-weight: 700; color: var(--vcs-text-title);">Simpan ke Connecting</span>
                            </div>
                            <p style="margin: 0; font-size: 12px; color: var(--vcs-text-muted); line-height: 1.4;">
                                Simpan baris CSV ke tabel <code>vehicle_connectings</code> (upsert kunci unik).
                            </p>
                            <label style="display: flex; align-items: center; gap: 6px; margin-top: 8px; font-size: 11.5px; color: var(--vcs-text-body); cursor: pointer;">
                                <input type="checkbox" wire:model="pruneConnecting" />
                                <span>Prune baris lama tak ada di CSV</span>
                            </label>
                        </div>
                        <button type="button" wire:click="importConnecting" wire:loading.attr="disabled" wire:target="importConnecting"
                                wire:confirm="Simpan CSV ke tabel master Vehicle Connecting?"
                                class="vcs-btn" style="width: 100%; justify-content: center; border-color: rgba(245, 158, 11, 0.5); color: #d97706;">
                            <span wire:loading.remove wire:target="importConnecting">💾 Simpan ke Master</span>
                            <span wire:loading wire:target="importConnecting">⏳ Menyimpan…</span>
                        </button>
                    </div>

                    {{-- LANGKAH 3: TERAPKAN KE KATALOG --}}
                    <div class="vcs-step-box" style="border-top: 3px solid #10b981;">
                        <div>
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                                <div class="vcs-step-num" style="background: rgba(16, 185, 129, 0.15); color: #059669;">3</div>
                                <span style="font-size: 13.5px; font-weight: 700; color: var(--vcs-text-title);">Terapkan ke Katalog</span>
                            </div>
                            <p style="margin: 0; font-size: 12px; color: var(--vcs-text-muted); line-height: 1.4;">
                                Daftarkan brand/model/type baru, terapkan klasifikasi, dan flush cache Pasar EV.
                            </p>
                        </div>
                        <button type="button" wire:click="applyToCatalog" wire:loading.attr="disabled" wire:target="applyToCatalog"
                                wire:confirm="Terapkan data Connecting ke Katalog (Brand, Model, Type) dan perbarui Cache Pasar EV?"
                                class="vcs-btn vcs-btn-primary" style="width: 100%; justify-content: center;">
                            <span wire:loading.remove wire:target="applyToCatalog">⚡ Terapkan & Flush Cache</span>
                            <span wire:loading wire:target="applyToCatalog">⏳ Menerapkan…</span>
                        </button>
                    </div>

                    {{-- UTILITY 1: PULIHKAN KEY (MAINTENANCE) --}}
                    <div class="vcs-step-box" style="border-top: 3px solid #6366f1;">
                        <div>
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                                <div class="vcs-step-num" style="background: rgba(99, 102, 241, 0.15); color: #4f46e5;">🔧</div>
                                <span style="font-size: 13.5px; font-weight: 700; color: var(--vcs-text-title);">Pulihkan Key Raw</span>
                            </div>
                            <p style="margin: 0; font-size: 12px; color: var(--vcs-text-muted); line-height: 1.4;">
                                Isi <code>raw_gabungan_key</code> yang masih kosong untuk menjaga keakuratan query index.
                            </p>
                        </div>
                        <button type="button" wire:click="backfillKeys" wire:loading.attr="disabled" wire:target="backfillKeys"
                                class="vcs-btn" style="width: 100%; justify-content: center; border-color: rgba(99, 102, 241, 0.4); color: #6366f1;">
                            <span wire:loading.remove wire:target="backfillKeys">🔑 Backfill Key</span>
                            <span wire:loading wire:target="backfillKeys">⏳ Memproses…</span>
                        </button>
                    </div>

                    {{-- UTILITY 2: PRUNE KATALOG (DESTRUCTIVE) --}}
                    <div class="vcs-step-box" style="border-top: 3px solid #ef4444;">
                        <div>
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                                <div class="vcs-step-num" style="background: rgba(239, 68, 68, 0.15); color: #ef4444;">🗑</div>
                                <span style="font-size: 13.5px; font-weight: 700; color: var(--vcs-text-title);">Prune Katalog</span>
                            </div>
                            <p style="margin: 0; font-size: 12px; color: var(--vcs-text-muted); line-height: 1.4;">
                                Hapus brand/model katalog yang tidak ada di CSV ini (kecuali yang dipakai user/stats).
                            </p>
                        </div>
                        <button type="button" wire:click="pruneCatalog" wire:loading.attr="disabled" wire:target="pruneCatalog"
                                wire:confirm="Prune akan MENGHAPUS PERMANEN brand/model katalog yang tidak ada di CSV ini. Lanjutkan?"
                                class="vcs-btn vcs-btn-danger" style="width: 100%; justify-content: center;">
                            <span wire:loading.remove wire:target="pruneCatalog">🗑 Jalankan Prune</span>
                            <span wire:loading wire:target="pruneCatalog">⏳ Memangkas…</span>
                        </button>
                    </div>
                </div>

                {{-- Alert Error Messages --}}
                @error('csvFile')
                    <div style="padding: 10px 14px; border-radius: 8px; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #ef4444; font-size: 12.5px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                        <span>⚠️</span>
                        <span>{{ $message }}</span>
                    </div>
                @enderror

                @if ($error)
                    <div style="padding: 10px 14px; border-radius: 8px; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #ef4444; font-size: 12.5px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                        <span>❌</span>
                        <span>{{ $error }}</span>
                    </div>
                @endif
            </div>
        </x-filament::section>

        {{-- 4. HASIL VERIFIKASI (DRY-RUN ANALYSIS) --}}
        @if ($report !== null)
            <x-filament::section>
                <x-slot name="heading">
                    Hasil Verifikasi CSV vs Katalog (Dry-run)
                </x-slot>
                <x-slot name="headerEnd">
                    <button type="button" wire:click="clearReport" class="vcs-btn" style="font-size: 12px; padding: 4px 10px;">
                        ✕ Tutup Hasil
                    </button>
                </x-slot>

                {{-- 6 Summary Indicator Cards --}}
                @php $dbOrphan = count($report['dbBrandTanpaCsv']) + count($report['dbModelTanpaCsv']); @endphp
                <div class="vcs-grid-6" style="margin-bottom: 20px;">
                    {{-- Match --}}
                    <div class="vcs-card" style="border-color: rgba(16, 185, 129, 0.4); padding: 12px 14px;">
                        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #10b981;">✓ Sinkron</div>
                        <div style="margin-top: 4px; font-size: 20px; font-weight: 800; font-family: monospace; color: #10b981;">
                            {{ number_format(count($report['match'])) }}
                        </div>
                        <div style="margin-top: 2px; font-size: 11px; color: var(--vcs-text-muted);">Sudah identik</div>
                    </div>

                    {{-- Brand Baru --}}
                    <div class="vcs-card" style="border-color: {{ count($report['brandBaru']) > 0 ? 'rgba(245, 158, 11, 0.4)' : 'var(--vcs-border)' }}; padding: 12px 14px;">
                        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: {{ count($report['brandBaru']) > 0 ? '#f59e0b' : 'var(--vcs-text-muted)' }};">Brand Baru</div>
                        <div style="margin-top: 4px; font-size: 20px; font-weight: 800; font-family: monospace; color: {{ count($report['brandBaru']) > 0 ? '#f59e0b' : 'var(--vcs-text-title)' }};">
                            {{ number_format(count($report['brandBaru'])) }}
                        </div>
                        <div style="margin-top: 2px; font-size: 11px; color: var(--vcs-text-muted);">Akan ditambah</div>
                    </div>

                    {{-- Model Baru --}}
                    <div class="vcs-card" style="border-color: {{ count($report['modelBaru']) > 0 ? 'rgba(245, 158, 11, 0.4)' : 'var(--vcs-border)' }}; padding: 12px 14px;">
                        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: {{ count($report['modelBaru']) > 0 ? '#f59e0b' : 'var(--vcs-text-muted)' }};">Model Baru</div>
                        <div style="margin-top: 4px; font-size: 20px; font-weight: 800; font-family: monospace; color: {{ count($report['modelBaru']) > 0 ? '#f59e0b' : 'var(--vcs-text-title)' }};">
                            {{ number_format(count($report['modelBaru'])) }}
                        </div>
                        <div style="margin-top: 2px; font-size: 11px; color: var(--vcs-text-muted);">Akan ditambah</div>
                    </div>

                    {{-- Type Baru --}}
                    <div class="vcs-card" style="border-color: {{ count($report['typeBaru']) > 0 ? 'rgba(245, 158, 11, 0.4)' : 'var(--vcs-border)' }}; padding: 12px 14px;">
                        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: {{ count($report['typeBaru']) > 0 ? '#f59e0b' : 'var(--vcs-text-muted)' }};">Type Baru</div>
                        <div style="margin-top: 4px; font-size: 20px; font-weight: 800; font-family: monospace; color: {{ count($report['typeBaru']) > 0 ? '#f59e0b' : 'var(--vcs-text-title)' }};">
                            {{ number_format(count($report['typeBaru'])) }}
                        </div>
                        <div style="margin-top: 2px; font-size: 11px; color: var(--vcs-text-muted);">Akan ditambah</div>
                    </div>

                    {{-- Klasifikasi Beda --}}
                    <div class="vcs-card" style="border-color: {{ count($report['klasifikasiBeda']) > 0 ? 'rgba(14, 165, 233, 0.4)' : 'var(--vcs-border)' }}; padding: 12px 14px;">
                        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: {{ count($report['klasifikasiBeda']) > 0 ? '#0284c7' : 'var(--vcs-text-muted)' }};">Klasifikasi Beda</div>
                        <div style="margin-top: 4px; font-size: 20px; font-weight: 800; font-family: monospace; color: {{ count($report['klasifikasiBeda']) > 0 ? '#0284c7' : 'var(--vcs-text-title)' }};">
                            {{ number_format(count($report['klasifikasiBeda'])) }}
                        </div>
                        <div style="margin-top: 2px; font-size: 11px; color: var(--vcs-text-muted);">Akan di-update</div>
                    </div>

                    {{-- DB Tanpa CSV --}}
                    <div class="vcs-card" style="padding: 12px 14px;">
                        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--vcs-text-muted);">DB Tanpa CSV</div>
                        <div style="margin-top: 4px; font-size: 20px; font-weight: 800; font-family: monospace; color: var(--vcs-text-title);">
                            {{ number_format($dbOrphan) }}
                        </div>
                        <div style="margin-top: 2px; font-size: 11px; color: var(--vcs-text-muted);">Katalog eksklusif</div>
                    </div>
                </div>

                {{-- Interactive Tabs & Search Toolbar --}}
                <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 14px; border-top: 1px solid var(--vcs-border-sub); padding-top: 14px;">
                    <div style="display: flex; flex-wrap: wrap; gap: 6px; align-items: center;">
                        @php
                            $totalNew = count($report['brandBaru']) + count($report['modelBaru']) + count($report['typeBaru']);
                        @endphp

                        <button type="button" wire:click="setActiveTab('new')" class="vcs-chip {{ $activeTab === 'new' ? 'vcs-chip-active' : '' }}">
                            <span>🆕 Entitas Baru</span>
                            <span style="font-size: 10.5px; padding: 1px 6px; border-radius: 999px; background: {{ $activeTab === 'new' ? 'rgba(255,255,255,0.25)' : 'rgba(245, 158, 11, 0.15)' }}; color: {{ $activeTab === 'new' ? '#fff' : '#f59e0b' }};">
                                {{ $totalNew }}
                            </span>
                        </button>

                        <button type="button" wire:click="setActiveTab('diff')" class="vcs-chip {{ $activeTab === 'diff' ? 'vcs-chip-active' : '' }}">
                            <span>🔄 Klasifikasi Beda</span>
                            <span style="font-size: 10.5px; padding: 1px 6px; border-radius: 999px; background: {{ $activeTab === 'diff' ? 'rgba(255,255,255,0.25)' : 'rgba(14, 165, 233, 0.15)' }}; color: {{ $activeTab === 'diff' ? '#fff' : '#0284c7' }};">
                                {{ count($report['klasifikasiBeda']) }}
                            </span>
                        </button>

                        @if (count($report['csvTidakKonsisten']) > 0)
                            <button type="button" wire:click="setActiveTab('inconsistent')" class="vcs-chip {{ $activeTab === 'inconsistent' ? 'vcs-chip-active' : '' }}">
                                <span>⚠️ CSV Tidak Konsisten</span>
                                <span style="font-size: 10.5px; padding: 1px 6px; border-radius: 999px; background: {{ $activeTab === 'inconsistent' ? 'rgba(255,255,255,0.25)' : 'rgba(239, 68, 68, 0.15)' }}; color: {{ $activeTab === 'inconsistent' ? '#fff' : '#ef4444' }};">
                                    {{ count($report['csvTidakKonsisten']) }}
                                </span>
                            </button>
                        @endif

                        <button type="button" wire:click="setActiveTab('db_orphan')" class="vcs-chip {{ $activeTab === 'db_orphan' ? 'vcs-chip-active' : '' }}">
                            <span>📦 DB Tanpa CSV</span>
                            <span style="font-size: 10.5px; padding: 1px 6px; border-radius: 999px; background: {{ $activeTab === 'db_orphan' ? 'rgba(255,255,255,0.25)' : 'rgba(156, 163, 175, 0.15)' }}; color: {{ $activeTab === 'db_orphan' ? '#fff' : 'inherit' }};">
                                {{ $dbOrphan }}
                            </span>
                        </button>

                        <button type="button" wire:click="setActiveTab('match')" class="vcs-chip {{ $activeTab === 'match' ? 'vcs-chip-active' : '' }}">
                            <span>✅ Match (Sinkron)</span>
                            <span style="font-size: 10.5px; padding: 1px 6px; border-radius: 999px; background: {{ $activeTab === 'match' ? 'rgba(255,255,255,0.25)' : 'rgba(16, 185, 129, 0.15)' }}; color: {{ $activeTab === 'match' ? '#fff' : '#059669' }};">
                                {{ count($report['match']) }}
                            </span>
                        </button>
                    </div>

                    <div style="min-width: 220px;">
                        <input type="text" wire:model.live.debounce.250ms="searchQuery"
                               placeholder="🔍 Filter tabel..."
                               class="vcs-input-control" style="width: 100%;" />
                    </div>
                </div>

                {{-- TAB CONTENT 1: ENTITAS BARU --}}
                @if ($activeTab === 'new')
                    <div style="overflow-x: auto; max-height: 380px; border: 1px solid var(--vcs-border); border-radius: 10px;">
                        <table class="vcs-table">
                            <thead>
                                <tr>
                                    <th style="width: 120px;">Jenis Entitas</th>
                                    <th>Brand</th>
                                    <th>Model</th>
                                    <th>Type / Varian</th>
                                    <th>Powertrain</th>
                                    <th>Kategori / Ukuran</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $filteredBrand = array_filter($report['brandBaru'], function ($r) {
                                        if (!$this->searchQuery) return true;
                                        $q = mb_strtolower($this->searchQuery);
                                        return str_contains(mb_strtolower($r['brand']), $q) || str_contains(mb_strtolower($r['model']), $q) || str_contains(mb_strtolower($r['type']), $q);
                                    });
                                    $filteredModel = array_filter($report['modelBaru'], function ($r) {
                                        if (!$this->searchQuery) return true;
                                        $q = mb_strtolower($this->searchQuery);
                                        return str_contains(mb_strtolower($r['brand']), $q) || str_contains(mb_strtolower($r['model']), $q) || str_contains(mb_strtolower($r['type']), $q);
                                    });
                                    $filteredType = array_filter($report['typeBaru'], function ($r) {
                                        if (!$this->searchQuery) return true;
                                        $q = mb_strtolower($this->searchQuery);
                                        return str_contains(mb_strtolower($r['brand']), $q) || str_contains(mb_strtolower($r['model']), $q) || str_contains(mb_strtolower($r['type']), $q);
                                    });
                                @endphp

                                @foreach ($filteredBrand as $r)
                                    <tr>
                                        <td><span class="vcs-badge vcs-badge-warn">🏷️ Brand Baru</span></td>
                                        <td style="font-family: monospace; font-weight: 700; color: #f59e0b;">{{ $r['brand'] }}</td>
                                        <td style="font-weight: 600; color: var(--vcs-text-title);">{{ $r['model'] }}</td>
                                        <td style="color: var(--vcs-text-muted);">{{ $r['type'] ?: '—' }}</td>
                                        <td>
                                            @if (!empty($r['pt']))
                                                @php $pt = strtoupper(trim($r['pt'])); @endphp
                                                <span class="vcs-badge {{ $pt === 'BEV' ? 'vcs-badge-bev' : ($pt === 'PHEV' ? 'vcs-badge-phev' : ($pt === 'HEV' ? 'vcs-badge-hev' : 'vcs-badge-ice')) }}">{{ $pt }}</span>
                                            @else
                                                <span style="color: var(--vcs-text-muted);">—</span>
                                            @endif
                                        </td>
                                        <td>{{ $r['category'] ?: '—' }}{{ !empty($r['size']) ? ' · '.$r['size'] : '' }}</td>
                                    </tr>
                                @endforeach

                                @foreach ($filteredModel as $r)
                                    <tr>
                                        <td><span class="vcs-badge vcs-badge-warn">🚗 Model Baru</span></td>
                                        <td style="font-family: monospace; font-weight: 700; color: var(--vcs-text-title);">{{ $r['brand'] }}</td>
                                        <td style="font-weight: 700; color: #f59e0b;">{{ $r['model'] }}</td>
                                        <td style="color: var(--vcs-text-muted);">{{ $r['type'] ?: '—' }}</td>
                                        <td>
                                            @if (!empty($r['pt']))
                                                @php $pt = strtoupper(trim($r['pt'])); @endphp
                                                <span class="vcs-badge {{ $pt === 'BEV' ? 'vcs-badge-bev' : ($pt === 'PHEV' ? 'vcs-badge-phev' : ($pt === 'HEV' ? 'vcs-badge-hev' : 'vcs-badge-ice')) }}">{{ $pt }}</span>
                                            @else
                                                <span style="color: var(--vcs-text-muted);">—</span>
                                            @endif
                                        </td>
                                        <td>{{ $r['category'] ?: '—' }}{{ !empty($r['size']) ? ' · '.$r['size'] : '' }}</td>
                                    </tr>
                                @endforeach

                                @foreach ($filteredType as $r)
                                    <tr>
                                        <td><span class="vcs-badge vcs-badge-ok">⚙️ Type Baru</span></td>
                                        <td style="font-family: monospace; font-weight: 700; color: var(--vcs-text-title);">{{ $r['brand'] }}</td>
                                        <td style="font-weight: 600; color: var(--vcs-text-title);">{{ $r['model'] }}</td>
                                        <td style="font-weight: 700; color: #10b981;">{{ $r['type'] }}</td>
                                        <td>
                                            @if (!empty($r['pt']))
                                                @php $pt = strtoupper(trim($r['pt'])); @endphp
                                                <span class="vcs-badge {{ $pt === 'BEV' ? 'vcs-badge-bev' : ($pt === 'PHEV' ? 'vcs-badge-phev' : ($pt === 'HEV' ? 'vcs-badge-hev' : 'vcs-badge-ice')) }}">{{ $pt }}</span>
                                            @else
                                                <span style="color: var(--vcs-text-muted);">—</span>
                                            @endif
                                        </td>
                                        <td>{{ ($r['category'] ?? null) ?: '—' }}</td>
                                    </tr>
                                @endforeach

                                @if (empty($filteredBrand) && empty($filteredModel) && empty($filteredType))
                                    <tr>
                                        <td colspan="6" style="padding: 32px; text-align: center; color: var(--vcs-text-muted);">
                                            ✓ Tidak ada entitas baru yang perlu didaftarkan.
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                @endif

                {{-- TAB CONTENT 2: KLASIFIKASI BEDA --}}
                @if ($activeTab === 'diff')
                    <div style="overflow-x: auto; max-height: 380px; border: 1px solid var(--vcs-border); border-radius: 10px;">
                        <table class="vcs-table">
                            <thead>
                                <tr>
                                    <th style="width: 160px;">Brand</th>
                                    <th style="width: 200px;">Model</th>
                                    <th>Perbedaan & Penyesuaian Klasifikasi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $filteredDiff = array_filter($report['klasifikasiBeda'], function ($r) {
                                        if (!$this->searchQuery) return true;
                                        $q = mb_strtolower($this->searchQuery);
                                        return str_contains(mb_strtolower($r['brand']), $q) || str_contains(mb_strtolower($r['model']), $q) || str_contains(mb_strtolower($r['diff']), $q);
                                    });
                                @endphp

                                @forelse ($filteredDiff as $row)
                                    <tr>
                                        <td style="font-family: monospace; font-weight: 700; color: var(--vcs-text-title);">{{ $row['brand'] }}</td>
                                        <td style="font-weight: 600; color: var(--vcs-text-title);">{{ $row['model'] }}</td>
                                        <td>
                                            <span class="vcs-badge vcs-badge-phev" style="font-family: monospace; font-size: 11.5px;">
                                                🔄 {{ $row['diff'] }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" style="padding: 32px; text-align: center; color: var(--vcs-text-muted);">
                                            ✓ Semua klasifikasi model sudah sinkron dengan CSV.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endif

                {{-- TAB CONTENT 3: CSV TIDAK KONSISTEN --}}
                @if ($activeTab === 'inconsistent')
                    <div style="overflow-x: auto; max-height: 380px; border: 1px solid var(--vcs-border); border-radius: 10px;">
                        <table class="vcs-table">
                            <thead>
                                <tr>
                                    <th style="width: 160px;">Brand</th>
                                    <th style="width: 200px;">Model</th>
                                    <th>Detail Konflik / Varian Campuran</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($report['csvTidakKonsisten'] as $row)
                                    <tr>
                                        <td style="font-family: monospace; font-weight: 700; color: var(--vcs-text-title);">{{ $row['brand'] }}</td>
                                        <td style="font-weight: 600; color: var(--vcs-text-title);">{{ $row['model'] }}</td>
                                        <td style="color: #ef4444; font-size: 12.5px;">
                                            ⚠️ {{ $row['detail'] }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" style="padding: 32px; text-align: center; color: var(--vcs-text-muted);">
                                            ✓ Tidak ada konflik nilai pada file CSV.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endif

                {{-- TAB CONTENT 4: DB TANPA CSV --}}
                @if ($activeTab === 'db_orphan')
                    <div style="overflow-x: auto; max-height: 380px; border: 1px solid var(--vcs-border); border-radius: 10px;">
                        <table class="vcs-table">
                            <thead>
                                <tr>
                                    <th style="width: 120px;">Tipe</th>
                                    <th style="width: 180px;">Brand Katalog</th>
                                    <th>Model Katalog</th>
                                    <th>Kategori</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($report['dbBrandTanpaCsv'] as $b)
                                    <tr>
                                        <td><span class="vcs-badge vcs-badge-gray">Brand</span></td>
                                        <td style="font-family: monospace; font-weight: 700; color: var(--vcs-text-title);">{{ $b['brand'] }}</td>
                                        <td style="color: var(--vcs-text-muted);">—</td>
                                        <td style="color: var(--vcs-text-muted);">—</td>
                                    </tr>
                                @endforeach
                                @foreach ($report['dbModelTanpaCsv'] as $m)
                                    <tr>
                                        <td><span class="vcs-badge vcs-badge-gray">Model</span></td>
                                        <td style="font-family: monospace; font-weight: 700; color: var(--vcs-text-title);">{{ $m['brand'] }}</td>
                                        <td style="font-weight: 600; color: var(--vcs-text-title);">{{ $m['model'] }}</td>
                                        <td style="color: var(--vcs-text-muted);">{{ $m['category'] ?: '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                {{-- TAB CONTENT 5: MATCH (SINKRON) --}}
                @if ($activeTab === 'match')
                    <div style="overflow-x: auto; max-height: 380px; border: 1px solid var(--vcs-border); border-radius: 10px;">
                        <table class="vcs-table">
                            <thead>
                                <tr>
                                    <th style="width: 200px;">Brand</th>
                                    <th>Model</th>
                                    <th style="width: 140px; text-align: right;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $filteredMatch = array_filter($report['match'], function ($r) {
                                        if (!$this->searchQuery) return true;
                                        $q = mb_strtolower($this->searchQuery);
                                        return str_contains(mb_strtolower($r['brand']), $q) || str_contains(mb_strtolower($r['model']), $q);
                                    });
                                @endphp

                                @forelse (array_slice($filteredMatch, 0, 100) as $row)
                                    <tr>
                                        <td style="font-family: monospace; font-weight: 700; color: var(--vcs-text-title);">{{ $row['brand'] }}</td>
                                        <td style="font-weight: 600; color: var(--vcs-text-title);">{{ $row['model'] }}</td>
                                        <td style="text-align: right;">
                                            <span class="vcs-badge vcs-badge-ok">✓ Sinkron</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" style="padding: 32px; text-align: center; color: var(--vcs-text-muted);">
                                            Tidak ada data match yang sesuai dengan pencarian.
                                        </td>
                                    </tr>
                                @endforelse
                                @if (count($filteredMatch) > 100)
                                    <tr>
                                        <td colspan="3" style="padding: 10px 14px; text-align: center; font-size: 12px; color: var(--vcs-text-muted); background: var(--vcs-bg-sub);">
                                            Menampilkan 100 dari {{ number_format(count($filteredMatch)) }} entitas yang sinkron.
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                @endif

            </x-filament::section>
        @endif

        {{-- 5. LOG AKTIVITAS TERMINAL CONSOLE --}}
        @if (count($log) > 0)
            <x-filament::section>
                <x-slot name="heading">
                    Log Aktivitas & Eksekusi Sinkronisasi
                </x-slot>
                <x-slot name="headerEnd">
                    <button type="button" wire:click="clearLog" class="vcs-btn" style="font-size: 11.5px; padding: 4px 10px;">
                        Hapus Log
                    </button>
                </x-slot>

                <div class="vcs-console">
                    @foreach ($log as $line)
                        <div style="display: flex; align-items: flex-start; gap: 8px; margin-bottom: 4px;">
                            <span style="color: #10b981;">❯</span>
                            <span style="white-space: pre-wrap;">{{ $line }}</span>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        @endif

    </div>
</x-filament-panels::page>
