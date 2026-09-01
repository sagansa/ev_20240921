<x-filament-panels::page>
    <style>
        :root {
            --vcs-bg-card: #ffffff;
            --vcs-bg-sub: rgba(0, 0, 0, 0.02);
            --vcs-bg-header: rgba(0, 0, 0, 0.015);
            --vcs-border: rgba(156, 163, 175, 0.22);
            --vcs-border-sub: rgba(156, 163, 175, 0.12);
            --vcs-text-title: #0f172a;
            --vcs-text-body: #334155;
            --vcs-text-muted: #64748b;
            --vcs-input-bg: #ffffff;
            --vcs-input-border: rgba(156, 163, 175, 0.35);
            --vcs-step-bg: #f8fafc;
            --vcs-emerald-glow: rgba(16, 185, 129, 0.12);
        }

        .dark, [data-theme="dark"] {
            --vcs-bg-card: rgba(30, 41, 59, 0.45);
            --vcs-bg-sub: rgba(255, 255, 255, 0.02);
            --vcs-bg-header: rgba(255, 255, 255, 0.02);
            --vcs-border: rgba(255, 255, 255, 0.08);
            --vcs-border-sub: rgba(255, 255, 255, 0.05);
            --vcs-text-title: #f8fafc;
            --vcs-text-body: #cbd5e1;
            --vcs-text-muted: #94a3b8;
            --vcs-input-bg: rgba(15, 23, 42, 0.6);
            --vcs-input-border: rgba(255, 255, 255, 0.12);
            --vcs-step-bg: rgba(255, 255, 255, 0.02);
            --vcs-emerald-glow: rgba(16, 185, 129, 0.08);
        }

        .vcs-glass {
            background: var(--vcs-bg-card);
            border: 1px solid var(--vcs-border);
            border-radius: 14px;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .vcs-glass:hover {
            border-color: rgba(16, 185, 129, 0.3);
        }

        .vcs-grid-4 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 16px;
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
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            line-height: 1.2;
            letter-spacing: 0.2px;
        }

        .vcs-badge-emerald {
            background: rgba(16, 185, 129, 0.12);
            color: #059669;
            border: 1px solid rgba(16, 185, 129, 0.25);
        }
        .dark .vcs-badge-emerald, [data-theme="dark"] .vcs-badge-emerald {
            background: rgba(16, 185, 129, 0.2);
            color: #34d399;
            border-color: rgba(16, 185, 129, 0.35);
        }

        .vcs-badge-amber {
            background: rgba(245, 158, 11, 0.12);
            color: #d97706;
            border: 1px solid rgba(245, 158, 11, 0.25);
        }
        .dark .vcs-badge-amber, [data-theme="dark"] .vcs-badge-amber {
            background: rgba(245, 158, 11, 0.2);
            color: #fbbf24;
            border-color: rgba(245, 158, 11, 0.35);
        }

        .vcs-badge-rose {
            background: rgba(244, 63, 94, 0.12);
            color: #e11d48;
            border: 1px solid rgba(244, 63, 94, 0.25);
        }
        .dark .vcs-badge-rose, [data-theme="dark"] .vcs-badge-rose {
            background: rgba(244, 63, 94, 0.2);
            color: #fb7185;
            border-color: rgba(244, 63, 94, 0.35);
        }

        .vcs-badge-cyan {
            background: rgba(6, 182, 212, 0.12);
            color: #0891b2;
            border: 1px solid rgba(6, 182, 212, 0.25);
        }
        .dark .vcs-badge-cyan, [data-theme="dark"] .vcs-badge-cyan {
            background: rgba(6, 182, 212, 0.2);
            color: #22d3ee;
            border-color: rgba(6, 182, 212, 0.35);
        }

        .vcs-badge-indigo {
            background: rgba(99, 102, 241, 0.12);
            color: #4f46e5;
            border: 1px solid rgba(99, 102, 241, 0.25);
        }
        .dark .vcs-badge-indigo, [data-theme="dark"] .vcs-badge-indigo {
            background: rgba(99, 102, 241, 0.2);
            color: #818cf8;
            border-color: rgba(99, 102, 241, 0.35);
        }

        .vcs-badge-slate {
            background: rgba(100, 116, 139, 0.12);
            color: #475569;
            border: 1px solid rgba(100, 116, 139, 0.2);
        }
        .dark .vcs-badge-slate, [data-theme="dark"] .vcs-badge-slate {
            background: rgba(255, 255, 255, 0.06);
            color: #94a3b8;
            border-color: rgba(255, 255, 255, 0.1);
        }

        .vcs-btn-step {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .vcs-btn-primary {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #ffffff;
            border: none;
        }
        .vcs-btn-primary:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
            filter: brightness(1.05);
        }

        .vcs-btn-secondary {
            background: var(--vcs-input-bg);
            color: var(--vcs-text-title);
            border: 1px solid var(--vcs-input-border);
        }
        .vcs-btn-secondary:hover:not(:disabled) {
            background: rgba(16, 185, 129, 0.08);
            border-color: #10b981;
            color: #10b981;
        }

        .vcs-tab {
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid transparent;
            background: transparent;
            color: var(--vcs-text-muted);
            transition: all 0.15s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .vcs-tab:hover {
            color: var(--vcs-text-title);
            background: var(--vcs-bg-sub);
        }

        .vcs-tab.active {
            background: rgba(16, 185, 129, 0.12);
            color: #10b981;
            border-color: rgba(16, 185, 129, 0.3);
            font-weight: 700;
        }

        .vcs-step-circle {
            width: 28px;
            height: 28px;
            min-width: 28px;
            border-radius: 9999px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 12px;
        }

        .vcs-terminal {
            background: #090d16;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 14px 18px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 12.5px;
            line-height: 1.6;
            color: #e2e8f0;
            max-height: 280px;
            overflow-y: auto;
        }
    </style>

    <div style="display: flex; flex-direction: column; gap: 24px;">

        {{-- 1. HEADER & OVERVIEW STATUS COUNTER --}}
        <div class="vcs-glass" style="padding: 20px 24px; position: relative; overflow: hidden;">
            <div style="position: absolute; right: -60px; top: -60px; width: 220px; height: 220px; background: radial-gradient(circle, rgba(16, 185, 129, 0.15) 0%, rgba(16, 185, 129, 0) 70%); pointer-events: none; border-radius: 9999px;"></div>
            
            <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: flex-start; gap: 16px;">
                <div style="max-width: 700px;">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
                        <span style="font-size: 20px;">⚡</span>
                        <h2 style="margin: 0; font-size: 18px; font-weight: 800; color: var(--vcs-text-title); letter-spacing: -0.3px;">
                            Sinkronisasi CONNECTING Master ke Katalog
                        </h2>
                        <span class="vcs-badge vcs-badge-emerald">🛡️ Idempoten & Aman</span>
                    </div>
                    <p style="margin: 0; font-size: 13px; color: var(--vcs-text-muted); line-height: 1.5;">
                        Pusat sinkronisasi hierarki kendaraan (Brand ➔ Model ➔ Type) dan klasifikasi dari master CSV CONNECTING. Menjamin konsistensi data penjualan, integrasi varian powertrain EV, serta peremajaan cache Pasar EV secara otomatis.
                    </p>
                </div>

                <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                    <a href="{{ \App\Filament\Pages\VehicleHierarchyExplorer::getUrl() }}" class="vcs-btn-step vcs-btn-secondary" style="font-size: 12px; padding: 7px 13px;">
                        <span>🌳 Pohon Hierarki</span>
                    </a>
                    <a href="{{ \App\Filament\Pages\VehicleSalesPreviewImport::getUrl() }}" class="vcs-btn-step vcs-btn-secondary" style="font-size: 12px; padding: 7px 13px;">
                        <span>🔍 Preview Impor</span>
                    </a>
                </div>
            </div>

            {{-- 4 Live Database Stats Cards --}}
            <div class="vcs-grid-4" style="margin-top: 20px;">
                {{-- Total Connecting Records --}}
                <div class="vcs-glass" style="padding: 14px 16px; background: var(--vcs-step-bg);">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--vcs-text-muted);">Master Connecting</span>
                        <span class="vcs-badge {{ $dbStats['unmappedConnecting'] > 0 ? 'vcs-badge-amber' : 'vcs-badge-emerald' }}">
                            {{ $dbStats['mappedPercentage'] }}% Link
                        </span>
                    </div>
                    <div style="margin-top: 8px; display: flex; align-items: baseline; gap: 6px;">
                        <span style="font-size: 22px; font-weight: 800; font-family: monospace; color: var(--vcs-text-title);">{{ number_format($dbStats['totalConnecting']) }}</span>
                        <span style="font-size: 11.5px; color: var(--vcs-text-muted);">baris raw</span>
                    </div>
                    <div style="margin-top: 4px; font-size: 11.5px; color: var(--vcs-text-muted);">
                        <span>{{ number_format($dbStats['mappedConnecting']) }} terhubung</span>
                        @if ($dbStats['unmappedConnecting'] > 0)
                            · <span style="color: #f59e0b; font-weight: 600;">{{ number_format($dbStats['unmappedConnecting']) }} belum link</span>
                        @endif
                    </div>
                </div>

                {{-- Brands Master --}}
                <div class="vcs-glass" style="padding: 14px 16px; background: var(--vcs-step-bg);">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--vcs-text-muted);">Brand Terdaftar</span>
                        <span style="font-size: 15px;">🏷️</span>
                    </div>
                    <div style="margin-top: 8px; display: flex; align-items: baseline; gap: 6px;">
                        <span style="font-size: 22px; font-weight: 800; font-family: monospace; color: var(--vcs-text-title);">{{ number_format($dbStats['totalBrands']) }}</span>
                        <span style="font-size: 11.5px; color: var(--vcs-text-muted);">Brand aktif</span>
                    </div>
                    <div style="margin-top: 4px; font-size: 11.5px; color: var(--vcs-text-muted);">
                        Katalog brand kendaraan
                    </div>
                </div>

                {{-- Models Master --}}
                <div class="vcs-glass" style="padding: 14px 16px; background: var(--vcs-step-bg);">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--vcs-text-muted);">Model Terdaftar</span>
                        <span style="font-size: 15px;">🚗</span>
                    </div>
                    <div style="margin-top: 8px; display: flex; align-items: baseline; gap: 6px;">
                        <span style="font-size: 22px; font-weight: 800; font-family: monospace; color: var(--vcs-text-title);">{{ number_format($dbStats['totalModels']) }}</span>
                        <span style="font-size: 11.5px; color: var(--vcs-text-muted);">Model seri</span>
                    </div>
                    <div style="margin-top: 4px; font-size: 11.5px; color: var(--vcs-text-muted);">
                        Hierarki model & klasifikasi
                    </div>
                </div>

                {{-- Types Master --}}
                <div class="vcs-glass" style="padding: 14px 16px; background: var(--vcs-step-bg);">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--vcs-text-muted);">Varian / Type</span>
                        <span style="font-size: 15px;">⚙️</span>
                    </div>
                    <div style="margin-top: 8px; display: flex; align-items: baseline; gap: 6px;">
                        <span style="font-size: 22px; font-weight: 800; font-family: monospace; color: var(--vcs-text-title);">{{ number_format($dbStats['totalTypes']) }}</span>
                        <span style="font-size: 11.5px; color: var(--vcs-text-muted);">Varian spesifik</span>
                    </div>
                    <div style="margin-top: 4px; font-size: 11.5px; color: var(--vcs-text-muted);">
                        BEV, PHEV, HEV & ICE
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. WORKFLOW 3-LANGKAH INTERAKTIF --}}
        <div class="vcs-glass" style="padding: 20px 24px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; border-bottom: 1px solid var(--vcs-border-sub); padding-bottom: 12px;">
                <div>
                    <h3 style="margin: 0; font-size: 15px; font-weight: 800; color: var(--vcs-text-title);">
                        Alur Eksekusi 3 Langkah (Pipeline Sync)
                    </h3>
                    <p style="margin: 2px 0 0 0; font-size: 12.5px; color: var(--vcs-text-muted);">
                        Ikuti urutan langkah di bawah ini untuk memvalidasi dan menyinkronkan data secara aman.
                    </p>
                </div>
                @if ($csvFile)
                    <button type="button" wire:click="removeFile" class="vcs-btn-step vcs-btn-secondary" style="font-size: 11.5px; padding: 5px 10px; color: #ef4444; border-color: rgba(239, 68, 68, 0.3);">
                        ✕ Hapus File Terpilih
                    </button>
                @endif
            </div>

            {{-- File Upload Card --}}
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 12.5px; font-weight: 700; color: var(--vcs-text-title); margin-bottom: 6px;">
                    1. Pilih File Master CONNECTING (.CSV)
                </label>
                
                <div style="border: 2px dashed {{ $csvFile ? 'rgba(16, 185, 129, 0.5)' : 'var(--vcs-border)' }}; border-radius: 12px; padding: 18px 24px; background: var(--vcs-step-bg); text-align: center; transition: all 0.2s ease;">
                    <input type="file" accept=".csv" wire:model.live="csvFile" id="csvFileInput" style="display: none;" />
                    
                    @if ($csvFile)
                        <div style="display: flex; align-items: center; justify-content: center; gap: 14px;">
                            <div style="width: 42px; height: 42px; border-radius: 10px; background: rgba(16, 185, 129, 0.15); display: flex; align-items: center; justify-content: center; font-size: 20px;">
                                📄
                            </div>
                            <div style="text-align: left;">
                                <div style="font-size: 14px; font-weight: 700; color: var(--vcs-text-title);">
                                    {{ $csvFile->getClientOriginalName() }}
                                </div>
                                <div style="font-size: 11.5px; color: var(--vcs-text-muted); margin-top: 2px;">
                                    Ukuran: {{ number_format($csvFile->getSize() / 1024, 1) }} KB · Siap diproses
                                </div>
                            </div>
                            <label for="csvFileInput" class="vcs-btn-step vcs-btn-secondary" style="font-size: 11.5px; padding: 6px 12px; cursor: pointer; margin-left: 12px;">
                                🔄 Ganti File
                            </label>
                        </div>
                    @else
                        <label for="csvFileInput" style="cursor: pointer; display: flex; flex-direction: column; align-items: center; gap: 8px;">
                            <div style="width: 48px; height: 48px; border-radius: 12px; background: var(--vcs-input-bg); border: 1px solid var(--vcs-border); display: flex; align-items: center; justify-content: center; font-size: 22px;">
                                📥
                            </div>
                            <div style="font-size: 13.5px; font-weight: 700; color: var(--vcs-text-title);">
                                Klik di sini untuk memilih file CSV CONNECTING
                            </div>
                            <div style="font-size: 11.5px; color: var(--vcs-text-muted);">
                                Header wajib: <code>BRAND MODEL TYPE, BRAND, MODEL, TYPE, POWERTRAIN, CATEGORY, SIZE</code>
                            </div>
                        </label>
                    @endif
                </div>
            </div>

            {{-- 3 Step Action Cards Row --}}
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">
                {{-- STEP 1: VERIFIKASI (DRY RUN) --}}
                <div class="vcs-glass" style="padding: 16px; border-left: 4px solid #06b6d4; background: var(--vcs-step-bg); display: flex; flex-direction: column; justify-content: space-between; gap: 14px;">
                    <div>
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                            <div class="vcs-step-circle" style="background: rgba(6, 182, 212, 0.15); color: #0891b2;">1</div>
                            <span style="font-size: 13.5px; font-weight: 700; color: var(--vcs-text-title);">Verifikasi (Dry-run)</span>
                        </div>
                        <p style="margin: 0; font-size: 12px; color: var(--vcs-text-muted); line-height: 1.4;">
                            Periksa perbedaan entitas & klasifikasi antara CSV dan katalog DB tanpa menulis data apapun.
                        </p>
                    </div>
                    <button type="button" wire:click="verify" wire:loading.attr="disabled" wire:target="verify" class="vcs-btn-step vcs-btn-secondary" style="width: 100%; border-color: rgba(6, 182, 212, 0.4); color: #0891b2;">
                        <span wire:loading.remove wire:target="verify">🔍 Jalankan Verifikasi</span>
                        <span wire:loading wire:target="verify">⏳ Memeriksa Data…</span>
                    </button>
                </div>

                {{-- STEP 2: SIMPAN KE MASTER CONNECTING --}}
                <div class="vcs-glass" style="padding: 16px; border-left: 4px solid #f59e0b; background: var(--vcs-step-bg); display: flex; flex-direction: column; justify-content: space-between; gap: 14px;">
                    <div>
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                            <div class="vcs-step-circle" style="background: rgba(245, 158, 11, 0.15); color: #d97706;">2</div>
                            <span style="font-size: 13.5px; font-weight: 700; color: var(--vcs-text-title);">Simpan ke Connecting</span>
                        </div>
                        <p style="margin: 0; font-size: 12px; color: var(--vcs-text-muted); line-height: 1.4;">
                            Simpan data CSV ke tabel master <code>vehicle_connectings</code> (upsert kunci unik <code>raw_gabungan</code>).
                        </p>
                    </div>
                    <button type="button" wire:click="importConnecting" wire:loading.attr="disabled" wire:target="importConnecting"
                            wire:confirm="Simpan CSV ke tabel master Vehicle Connecting?"
                            class="vcs-btn-step vcs-btn-secondary" style="width: 100%; border-color: rgba(245, 158, 11, 0.4); color: #d97706;">
                        <span wire:loading.remove wire:target="importConnecting">💾 Simpan ke Master</span>
                        <span wire:loading wire:target="importConnecting">⏳ Menyimpan Data…</span>
                    </button>
                </div>

                {{-- STEP 3: TERAPKAN KE KATALOG & FLUSH CACHE --}}
                <div class="vcs-glass" style="padding: 16px; border-left: 4px solid #10b981; background: var(--vcs-step-bg); display: flex; flex-direction: column; justify-content: space-between; gap: 14px;">
                    <div>
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                            <div class="vcs-step-circle" style="background: rgba(16, 185, 129, 0.15); color: #059669;">3</div>
                            <span style="font-size: 13.5px; font-weight: 700; color: var(--vcs-text-title);">Terapkan ke Katalog</span>
                        </div>
                        <p style="margin: 0; font-size: 12px; color: var(--vcs-text-muted); line-height: 1.4;">
                            Daftarkan brand/model/type baru, terapkan klasifikasi seragam, lalu flush cache Pasar EV.
                        </p>
                    </div>
                    <button type="button" wire:click="applyToCatalog" wire:loading.attr="disabled" wire:target="applyToCatalog"
                            wire:confirm="Terapkan data Connecting ke Katalog (Brand, Model, Type) dan perbarui Cache Pasar EV?"
                            class="vcs-btn-step vcs-btn-primary" style="width: 100%;">
                        <span wire:loading.remove wire:target="applyToCatalog">⚡ Terapkan & Refresh Cache</span>
                        <span wire:loading wire:target="applyToCatalog">⏳ Menerapkan Katalog…</span>
                    </button>
                </div>
            </div>

            {{-- Error Alerts --}}
            @error('csvFile')
                <div style="margin-top: 14px; padding: 12px 16px; border-radius: 10px; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #ef4444; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                    <span>⚠️</span>
                    <span>{{ $message }}</span>
                </div>
            @enderror

            @if ($error)
                <div style="margin-top: 14px; padding: 12px 16px; border-radius: 10px; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #ef4444; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                    <span>❌</span>
                    <span>{{ $error }}</span>
                </div>
            @endif
        </div>

        {{-- 3. HASIL VERIFIKASI (DRY-RUN ANALYSIS PREVIEW) --}}
        @if ($report !== null)
            <div class="vcs-glass" style="padding: 20px 24px;">
                <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 18px;">
                    <div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="font-size: 18px;">📊</span>
                            <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: var(--vcs-text-title);">
                                Hasil Verifikasi CSV vs Katalog (Dry-run)
                            </h3>
                        </div>
                        <p style="margin: 3px 0 0 0; font-size: 12.5px; color: var(--vcs-text-muted);">
                            Ringkasan perbandingan data master CSV dengan kondisi katalog database terkini.
                        </p>
                    </div>

                    <div style="display: flex; gap: 8px;">
                        <button type="button" wire:click="clearReport" class="vcs-btn-step vcs-btn-secondary" style="font-size: 12px; padding: 6px 12px;">
                            ✕ Tutup Hasil
                        </button>
                    </div>
                </div>

                {{-- 6 Summary Metrics Cards --}}
                <div class="vcs-grid-6" style="margin-bottom: 24px;">
                    {{-- Match --}}
                    <div class="vcs-glass" style="padding: 12px 14px; border-color: rgba(16, 185, 129, 0.35); background: rgba(16, 185, 129, 0.04);">
                        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #10b981;">✓ Sinkron</div>
                        <div style="margin-top: 6px; font-size: 20px; font-weight: 800; font-family: monospace; color: #10b981;">
                            {{ number_format(count($report['match'])) }}
                        </div>
                        <div style="margin-top: 2px; font-size: 11px; color: var(--vcs-text-muted);">Sudah identik</div>
                    </div>

                    {{-- Brand Baru --}}
                    <div class="vcs-glass" style="padding: 12px 14px; border-color: {{ count($report['brandBaru']) > 0 ? 'rgba(245, 158, 11, 0.4)' : 'var(--vcs-border)' }};">
                        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: {{ count($report['brandBaru']) > 0 ? '#f59e0b' : 'var(--vcs-text-muted)' }};">Brand Baru</div>
                        <div style="margin-top: 6px; font-size: 20px; font-weight: 800; font-family: monospace; color: {{ count($report['brandBaru']) > 0 ? '#f59e0b' : 'var(--vcs-text-title)' }};">
                            {{ number_format(count($report['brandBaru'])) }}
                        </div>
                        <div style="margin-top: 2px; font-size: 11px; color: var(--vcs-text-muted);">Akan ditambah</div>
                    </div>

                    {{-- Model Baru --}}
                    <div class="vcs-glass" style="padding: 12px 14px; border-color: {{ count($report['modelBaru']) > 0 ? 'rgba(245, 158, 11, 0.4)' : 'var(--vcs-border)' }};">
                        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: {{ count($report['modelBaru']) > 0 ? '#f59e0b' : 'var(--vcs-text-muted)' }};">Model Baru</div>
                        <div style="margin-top: 6px; font-size: 20px; font-weight: 800; font-family: monospace; color: {{ count($report['modelBaru']) > 0 ? '#f59e0b' : 'var(--vcs-text-title)' }};">
                            {{ number_format(count($report['modelBaru'])) }}
                        </div>
                        <div style="margin-top: 2px; font-size: 11px; color: var(--vcs-text-muted);">Akan ditambah</div>
                    </div>

                    {{-- Type Baru --}}
                    <div class="vcs-glass" style="padding: 12px 14px; border-color: {{ count($report['typeBaru']) > 0 ? 'rgba(245, 158, 11, 0.4)' : 'var(--vcs-border)' }};">
                        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: {{ count($report['typeBaru']) > 0 ? '#f59e0b' : 'var(--vcs-text-muted)' }};">Type Baru</div>
                        <div style="margin-top: 6px; font-size: 20px; font-weight: 800; font-family: monospace; color: {{ count($report['typeBaru']) > 0 ? '#f59e0b' : 'var(--vcs-text-title)' }};">
                            {{ number_format(count($report['typeBaru'])) }}
                        </div>
                        <div style="margin-top: 2px; font-size: 11px; color: var(--vcs-text-muted);">Akan ditambah</div>
                    </div>

                    {{-- Klasifikasi Beda --}}
                    <div class="vcs-glass" style="padding: 12px 14px; border-color: {{ count($report['klasifikasiBeda']) > 0 ? 'rgba(6, 182, 212, 0.4)' : 'var(--vcs-border)' }};">
                        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: {{ count($report['klasifikasiBeda']) > 0 ? '#0891b2' : 'var(--vcs-text-muted)' }};">Klasifikasi Beda</div>
                        <div style="margin-top: 6px; font-size: 20px; font-weight: 800; font-family: monospace; color: {{ count($report['klasifikasiBeda']) > 0 ? '#0891b2' : 'var(--vcs-text-title)' }};">
                            {{ number_format(count($report['klasifikasiBeda'])) }}
                        </div>
                        <div style="margin-top: 2px; font-size: 11px; color: var(--vcs-text-muted);">Akan di-update</div>
                    </div>

                    {{-- DB Tak Ada di CSV --}}
                    @php $dbOrphan = count($report['dbBrandTanpaCsv']) + count($report['dbModelTanpaCsv']); @endphp
                    <div class="vcs-glass" style="padding: 12px 14px;">
                        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--vcs-text-muted);">DB Tanpa CSV</div>
                        <div style="margin-top: 6px; font-size: 20px; font-weight: 800; font-family: monospace; color: var(--vcs-text-title);">
                            {{ number_format($dbOrphan) }}
                        </div>
                        <div style="margin-top: 2px; font-size: 11px; color: var(--vcs-text-muted);">Master katalog lain</div>
                    </div>
                </div>

                {{-- Interactive Tabs & Search Toolbar --}}
                <div style="border-top: 1px solid var(--vcs-border-sub); padding-top: 16px;">
                    <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 16px;">
                        {{-- Tabs --}}
                        <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                            @php
                                $totalNew = count($report['brandBaru']) + count($report['modelBaru']) + count($report['typeBaru']);
                            @endphp

                            <button type="button" wire:click="setActiveTab('new')" class="vcs-tab {{ $activeTab === 'new' ? 'active' : '' }}">
                                <span>🆕 Entitas Baru</span>
                                <span class="vcs-badge {{ $totalNew > 0 ? 'vcs-badge-amber' : 'vcs-badge-slate' }}">{{ $totalNew }}</span>
                            </button>

                            <button type="button" wire:click="setActiveTab('diff')" class="vcs-tab {{ $activeTab === 'diff' ? 'active' : '' }}">
                                <span>🔄 Klasifikasi Berbeda</span>
                                <span class="vcs-badge {{ count($report['klasifikasiBeda']) > 0 ? 'vcs-badge-cyan' : 'vcs-badge-slate' }}">{{ count($report['klasifikasiBeda']) }}</span>
                            </button>

                            @if (count($report['csvTidakKonsisten']) > 0)
                                <button type="button" wire:click="setActiveTab('inconsistent')" class="vcs-tab {{ $activeTab === 'inconsistent' ? 'active' : '' }}">
                                    <span>⚠️ CSV Tidak Konsisten</span>
                                    <span class="vcs-badge vcs-badge-rose">{{ count($report['csvTidakKonsisten']) }}</span>
                                </button>
                            @endif

                            <button type="button" wire:click="setActiveTab('db_orphan')" class="vcs-tab {{ $activeTab === 'db_orphan' ? 'active' : '' }}">
                                <span>📦 DB Tanpa CSV</span>
                                <span class="vcs-badge vcs-badge-slate">{{ $dbOrphan }}</span>
                            </button>

                            <button type="button" wire:click="setActiveTab('match')" class="vcs-tab {{ $activeTab === 'match' ? 'active' : '' }}">
                                <span>✅ Match (Sinkron)</span>
                                <span class="vcs-badge vcs-badge-emerald">{{ count($report['match']) }}</span>
                            </button>
                        </div>

                        {{-- Search Filter --}}
                        <div style="min-width: 220px;">
                            <input type="text" wire:model.live.debounce.250ms="searchQuery"
                                   placeholder="🔍 Filter tabel..."
                                   style="background: var(--vcs-input-bg); color: var(--vcs-text-title); border: 1px solid var(--vcs-input-border); border-radius: 8px; padding: 6px 12px; font-size: 12.5px; width: 100%; outline: none;" />
                        </div>
                    </div>

                    {{-- TAB CONTENT: 1. ENTITAS BARU --}}
                    @if ($activeTab === 'new')
                        <div style="overflow-x: auto; max-height: 360px; border: 1px solid var(--vcs-border); border-radius: 10px; background: var(--vcs-bg-card);">
                            <table style="width: 100%; font-size: 13px; text-align: left; border-collapse: collapse;">
                                <thead style="position: sticky; top: 0; background: var(--vcs-step-bg); z-index: 1;">
                                    <tr style="border-bottom: 1px solid var(--vcs-border); color: var(--vcs-text-muted);">
                                        <th style="padding: 10px 14px; font-weight: 700; width: 120px;">Jenis Entitas</th>
                                        <th style="padding: 10px 14px; font-weight: 700;">Brand</th>
                                        <th style="padding: 10px 14px; font-weight: 700;">Model</th>
                                        <th style="padding: 10px 14px; font-weight: 700;">Type / Varian</th>
                                        <th style="padding: 10px 14px; font-weight: 700;">Powertrain</th>
                                        <th style="padding: 10px 14px; font-weight: 700;">Kategori / Ukuran</th>
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

                                    {{-- Brand Baru Rows --}}
                                    @foreach ($filteredBrand as $r)
                                        <tr style="border-bottom: 1px solid var(--vcs-border-sub);">
                                            <td style="padding: 9px 14px;"><span class="vcs-badge vcs-badge-amber">🏷️ Brand Baru</span></td>
                                            <td style="padding: 9px 14px; font-family: monospace; font-weight: 700; color: #f59e0b;">{{ $r['brand'] }}</td>
                                            <td style="padding: 9px 14px; font-weight: 600; color: var(--vcs-text-title);">{{ $r['model'] }}</td>
                                            <td style="padding: 9px 14px; color: var(--vcs-text-muted);">{{ $r['type'] ?: '—' }}</td>
                                            <td style="padding: 9px 14px;">
                                                @if (!empty($r['pt']))
                                                    <span class="vcs-badge {{ $r['pt'] === 'BEV' ? 'vcs-badge-emerald' : ($r['pt'] === 'PHEV' ? 'vcs-badge-cyan' : ($r['pt'] === 'HEV' ? 'vcs-badge-indigo' : 'vcs-badge-slate')) }}">{{ $r['pt'] }}</span>
                                                @else
                                                    <span style="color: var(--vcs-text-muted);">—</span>
                                                @endif
                                            </td>
                                            <td style="padding: 9px 14px; color: var(--vcs-text-body);">
                                                {{ $r['category'] ?: '—' }}{{ !empty($r['size']) ? ' · '.$r['size'] : '' }}
                                            </td>
                                        </tr>
                                    @endforeach

                                    {{-- Model Baru Rows --}}
                                    @foreach ($filteredModel as $r)
                                        <tr style="border-bottom: 1px solid var(--vcs-border-sub);">
                                            <td style="padding: 9px 14px;"><span class="vcs-badge vcs-badge-amber">🚗 Model Baru</span></td>
                                            <td style="padding: 9px 14px; font-family: monospace; font-weight: 700; color: var(--vcs-text-title);">{{ $r['brand'] }}</td>
                                            <td style="padding: 9px 14px; font-weight: 700; color: #f59e0b;">{{ $r['model'] }}</td>
                                            <td style="padding: 9px 14px; color: var(--vcs-text-muted);">{{ $r['type'] ?: '—' }}</td>
                                            <td style="padding: 9px 14px;">
                                                @if (!empty($r['pt']))
                                                    <span class="vcs-badge {{ $r['pt'] === 'BEV' ? 'vcs-badge-emerald' : ($r['pt'] === 'PHEV' ? 'vcs-badge-cyan' : ($r['pt'] === 'HEV' ? 'vcs-badge-indigo' : 'vcs-badge-slate')) }}">{{ $r['pt'] }}</span>
                                                @else
                                                    <span style="color: var(--vcs-text-muted);">—</span>
                                                @endif
                                            </td>
                                            <td style="padding: 9px 14px; color: var(--vcs-text-body);">
                                                {{ $r['category'] ?: '—' }}{{ !empty($r['size']) ? ' · '.$r['size'] : '' }}
                                            </td>
                                        </tr>
                                    @endforeach

                                    {{-- Type Baru Rows --}}
                                    @foreach ($filteredType as $r)
                                        <tr style="border-bottom: 1px solid var(--vcs-border-sub);">
                                            <td style="padding: 9px 14px;"><span class="vcs-badge vcs-badge-emerald">⚙️ Type Baru</span></td>
                                            <td style="padding: 9px 14px; font-family: monospace; font-weight: 700; color: var(--vcs-text-title);">{{ $r['brand'] }}</td>
                                            <td style="padding: 9px 14px; font-weight: 600; color: var(--vcs-text-title);">{{ $r['model'] }}</td>
                                            <td style="padding: 9px 14px; font-weight: 700; color: #10b981;">{{ $r['type'] }}</td>
                                            <td style="padding: 9px 14px;">
                                                @if (!empty($r['pt']))
                                                    <span class="vcs-badge {{ $r['pt'] === 'BEV' ? 'vcs-badge-emerald' : ($r['pt'] === 'PHEV' ? 'vcs-badge-cyan' : ($r['pt'] === 'HEV' ? 'vcs-badge-indigo' : 'vcs-badge-slate')) }}">{{ $r['pt'] }}</span>
                                                @else
                                                    <span style="color: var(--vcs-text-muted);">—</span>
                                                @endif
                                            </td>
                                            <td style="padding: 9px 14px; color: var(--vcs-text-body);">
                                                {{ ($r['category'] ?? null) ?: '—' }}
                                            </td>
                                        </tr>
                                    @endforeach

                                    @if (empty($filteredBrand) && empty($filteredModel) && empty($filteredType))
                                        <tr>
                                            <td colspan="6" style="padding: 30px; text-align: center; color: var(--vcs-text-muted);">
                                                ✓ Tidak ada entitas baru yang perlu didaftarkan.
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    @endif

                    {{-- TAB CONTENT: 2. KLASIFIKASI BERBEDA --}}
                    @if ($activeTab === 'diff')
                        <div style="overflow-x: auto; max-height: 360px; border: 1px solid var(--vcs-border); border-radius: 10px; background: var(--vcs-bg-card);">
                            <table style="width: 100%; font-size: 13px; text-align: left; border-collapse: collapse;">
                                <thead style="position: sticky; top: 0; background: var(--vcs-step-bg); z-index: 1;">
                                    <tr style="border-bottom: 1px solid var(--vcs-border); color: var(--vcs-text-muted);">
                                        <th style="padding: 10px 14px; font-weight: 700; width: 160px;">Brand</th>
                                        <th style="padding: 10px 14px; font-weight: 700; width: 200px;">Model</th>
                                        <th style="padding: 10px 14px; font-weight: 700;">Perbedaan & Penyesuaian Klasifikasi</th>
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
                                        <tr style="border-bottom: 1px solid var(--vcs-border-sub);">
                                            <td style="padding: 9px 14px; font-family: monospace; font-weight: 700; color: var(--vcs-text-title);">{{ $row['brand'] }}</td>
                                            <td style="padding: 9px 14px; font-weight: 600; color: var(--vcs-text-title);">{{ $row['model'] }}</td>
                                            <td style="padding: 9px 14px;">
                                                <span class="vcs-badge vcs-badge-cyan" style="font-family: monospace; font-size: 12px;">
                                                    🔄 {{ $row['diff'] }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" style="padding: 30px; text-align: center; color: var(--vcs-text-muted);">
                                                ✓ Semua klasifikasi model sudah sinkron dengan CSV.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @endif

                    {{-- TAB CONTENT: 3. CSV TIDAK KONSISTEN --}}
                    @if ($activeTab === 'inconsistent')
                        <div style="overflow-x: auto; max-height: 360px; border: 1px solid var(--vcs-border); border-radius: 10px; background: var(--vcs-bg-card);">
                            <table style="width: 100%; font-size: 13px; text-align: left; border-collapse: collapse;">
                                <thead style="position: sticky; top: 0; background: var(--vcs-step-bg); z-index: 1;">
                                    <tr style="border-bottom: 1px solid var(--vcs-border); color: var(--vcs-text-muted);">
                                        <th style="padding: 10px 14px; font-weight: 700; width: 160px;">Brand</th>
                                        <th style="padding: 10px 14px; font-weight: 700; width: 200px;">Model</th>
                                        <th style="padding: 10px 14px; font-weight: 700;">Detail Konflik / Varian Campuran</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($report['csvTidakKonsisten'] as $row)
                                        <tr style="border-bottom: 1px solid var(--vcs-border-sub);">
                                            <td style="padding: 9px 14px; font-family: monospace; font-weight: 700; color: var(--vcs-text-title);">{{ $row['brand'] }}</td>
                                            <td style="padding: 9px 14px; font-weight: 600; color: var(--vcs-text-title);">{{ $row['model'] }}</td>
                                            <td style="padding: 9px 14px; color: #ef4444; font-size: 12.5px;">
                                                ⚠️ {{ $row['detail'] }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" style="padding: 30px; text-align: center; color: var(--vcs-text-muted);">
                                                ✓ Tidak ada konflik nilai pada file CSV.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @endif

                    {{-- TAB CONTENT: 4. DB TANPA CSV --}}
                    @if ($activeTab === 'db_orphan')
                        <div style="overflow-x: auto; max-height: 360px; border: 1px solid var(--vcs-border); border-radius: 10px; background: var(--vcs-bg-card);">
                            <table style="width: 100%; font-size: 13px; text-align: left; border-collapse: collapse;">
                                <thead style="position: sticky; top: 0; background: var(--vcs-step-bg); z-index: 1;">
                                    <tr style="border-bottom: 1px solid var(--vcs-border); color: var(--vcs-text-muted);">
                                        <th style="padding: 10px 14px; font-weight: 700; width: 120px;">Tipe</th>
                                        <th style="padding: 10px 14px; font-weight: 700; width: 180px;">Brand Katalog</th>
                                        <th style="padding: 10px 14px; font-weight: 700;">Model Katalog</th>
                                        <th style="padding: 10px 14px; font-weight: 700;">Kategori</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($report['dbBrandTanpaCsv'] as $b)
                                        <tr style="border-bottom: 1px solid var(--vcs-border-sub);">
                                            <td style="padding: 9px 14px;"><span class="vcs-badge vcs-badge-slate">Brand</span></td>
                                            <td style="padding: 9px 14px; font-family: monospace; font-weight: 700; color: var(--vcs-text-title);">{{ $b['brand'] }}</td>
                                            <td style="padding: 9px 14px; color: var(--vcs-text-muted);">—</td>
                                            <td style="padding: 9px 14px; color: var(--vcs-text-muted);">—</td>
                                        </tr>
                                    @endforeach
                                    @foreach ($report['dbModelTanpaCsv'] as $m)
                                        <tr style="border-bottom: 1px solid var(--vcs-border-sub);">
                                            <td style="padding: 9px 14px;"><span class="vcs-badge vcs-badge-slate">Model</span></td>
                                            <td style="padding: 9px 14px; font-family: monospace; font-weight: 700; color: var(--vcs-text-title);">{{ $m['brand'] }}</td>
                                            <td style="padding: 9px 14px; font-weight: 600; color: var(--vcs-text-title);">{{ $m['model'] }}</td>
                                            <td style="padding: 9px 14px; color: var(--vcs-text-muted);">{{ $m['category'] ?: '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    {{-- TAB CONTENT: 5. MATCH (SINKRON) --}}
                    @if ($activeTab === 'match')
                        <div style="overflow-x: auto; max-height: 360px; border: 1px solid var(--vcs-border); border-radius: 10px; background: var(--vcs-bg-card);">
                            <table style="width: 100%; font-size: 13px; text-align: left; border-collapse: collapse;">
                                <thead style="position: sticky; top: 0; background: var(--vcs-step-bg); z-index: 1;">
                                    <tr style="border-bottom: 1px solid var(--vcs-border); color: var(--vcs-text-muted);">
                                        <th style="padding: 10px 14px; font-weight: 700; width: 200px;">Brand</th>
                                        <th style="padding: 10px 14px; font-weight: 700;">Model</th>
                                        <th style="padding: 10px 14px; font-weight: 700; width: 140px; text-align: right;">Status</th>
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
                                        <tr style="border-bottom: 1px solid var(--vcs-border-sub);">
                                            <td style="padding: 9px 14px; font-family: monospace; font-weight: 700; color: var(--vcs-text-title);">{{ $row['brand'] }}</td>
                                            <td style="padding: 9px 14px; font-weight: 600; color: var(--vcs-text-title);">{{ $row['model'] }}</td>
                                            <td style="padding: 9px 14px; text-align: right;">
                                                <span class="vcs-badge vcs-badge-emerald">✓ Sinkron</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" style="padding: 30px; text-align: center; color: var(--vcs-text-muted);">
                                                Tidak ada data match yang sesuai dengan pencarian.
                                            </td>
                                        </tr>
                                    @endforelse
                                    @if (count($filteredMatch) > 100)
                                        <tr>
                                            <td colspan="3" style="padding: 10px 14px; text-align: center; font-size: 12px; color: var(--vcs-text-muted); background: var(--vcs-step-bg);">
                                                Menampilkan 100 dari {{ number_format(count($filteredMatch)) }} entitas yang sinkron.
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- 4. ACTIVITY & EXECUTION LOG CONSOLE --}}
        @if (count($log) > 0)
            <div class="vcs-glass" style="padding: 20px 24px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 16px;">✅</span>
                        <h3 style="margin: 0; font-size: 15px; font-weight: 800; color: var(--vcs-text-title);">
                            Hasil Sinkronisasi & Log Aktivitas
                        </h3>
                    </div>
                    <button type="button" wire:click="clearLog" class="vcs-btn-step vcs-btn-secondary" style="font-size: 11.5px; padding: 4px 10px;">
                        Hapus Log
                    </button>
                </div>

                <div class="vcs-terminal">
                    @foreach ($log as $line)
                        <div style="display: flex; align-items: flex-start; gap: 8px; margin-bottom: 4px;">
                            <span style="color: #10b981;">❯</span>
                            <span style="white-space: pre-wrap;">{{ $line }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

    </div>
</x-filament-panels::page>
