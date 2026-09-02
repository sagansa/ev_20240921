<x-filament-panels::page>
    <style>
        .vau-card {
            border: 1px solid rgba(156, 163, 175, 0.25);
            border-radius: 12px;
            padding: 12px 16px;
            background: rgba(255, 255, 255, 0.03);
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
        .vau-badge-warn { background: rgba(245, 158, 11, 0.18); color: #f59e0b; }
        .vau-badge-ok { background: rgba(16, 185, 129, 0.15); color: #10b981; }
        .vau-badge-gray { background: rgba(156, 163, 175, 0.15); color: #9ca3af; }
        .vau-input {
            background: rgba(255, 255, 255, 0.05);
            color: #f9fafb;
            border: 1px solid rgba(156, 163, 175, 0.35);
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
            border: 1px solid rgba(156, 163, 175, 0.3);
            background: transparent;
            color: #e5e7eb;
            white-space: nowrap;
        }
        .vau-chip-active {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #fff;
            border-color: transparent;
        }
        .vau-table { width: 100%; font-size: 12.5px; text-align: left; border-collapse: collapse; }
        .vau-table th {
            padding: 8px 10px;
            font-weight: 600;
            color: #9ca3af;
            border-bottom: 1px solid rgba(156, 163, 175, 0.25);
            white-space: nowrap;
            position: sticky;
            top: 0;
            background: #111827;
        }
        .vau-table td {
            padding: 7px 10px;
            border-bottom: 1px solid rgba(156, 163, 175, 0.12);
            vertical-align: top;
        }
        .vau-table tr:hover td { background: rgba(156, 163, 175, 0.06); }
    </style>

    <div style="display: flex; flex-direction: column; gap: 18px;">
        {{-- RINGKASAN --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px;">
            <div class="vau-card">
                <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #9ca3af;">Total Baris</div>
                <div style="margin-top: 6px; font-size: 20px; font-weight: 800; font-family: monospace; color: #f9fafb;">{{ number_format($summary['total'] ?? 0) }}</div>
            </div>
            <div class="vau-card" style="border-color: {{ ($summary['problem'] ?? 0) > 0 ? 'rgba(239, 68, 68, 0.45)' : 'rgba(16, 185, 129, 0.4)' }};">
                <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: {{ ($summary['problem'] ?? 0) > 0 ? '#ef4444' : '#10b981' }};">Bermasalah</div>
                <div style="margin-top: 6px; font-size: 20px; font-weight: 800; font-family: monospace; color: {{ ($summary['problem'] ?? 0) > 0 ? '#ef4444' : '#10b981' }};">{{ number_format($summary['problem'] ?? 0) }}</div>
            </div>
            @foreach (['no_key' => 'Tanpa Key', 'dup' => 'Key Duplikat', 'unlinked_brand' => 'Brand ✗', 'unlinked_model' => 'Model ✗', 'unlinked_type' => 'Type ✗', 'no_category' => 'Kategori Kosong', 'no_powertrain' => 'Powertrain Kosong'] as $k => $label)
                <div class="vau-card">
                    <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #9ca3af;">{{ $label }}</div>
                    <div style="margin-top: 6px; font-size: 20px; font-weight: 800; font-family: monospace; color: {{ ($summary[$k] ?? 0) > 0 ? '#f59e0b' : '#10b981' }};">{{ number_format($summary[$k] ?? 0) }}</div>
                </div>
            @endforeach
        </div>

        {{-- FILTER + CARI --}}
        <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
            @foreach ($filters as $key => $label)
                <button type="button" class="vau-chip {{ $filter === $key ? 'vau-chip-active' : '' }}" wire:click="$set('filter', '{{ $key }}')">
                    {{ $label }}@if (in_array($key, ['no_key', 'dup', 'unlinked_brand', 'unlinked_model', 'unlinked_type', 'no_category', 'no_powertrain']) && ($summary[$key] ?? 0) > 0)
                        ({{ number_format($summary[$key]) }})
                    @endif
                </button>
            @endforeach
            <div style="flex: 1; min-width: 220px;">
                <input type="text" class="vau-input" style="width: 100%;" placeholder="Cari nama (raw / brand / model / type)…" wire:model.live.debounce.400ms="search" />
            </div>
        </div>

        {{-- TABEL --}}
        <div class="vau-card" style="padding: 0; overflow-x: auto; max-height: 70vh; overflow-y: auto;">
            <table class="vau-table">
                <thead>
                    <tr>
                        <th>BRAND MODEL TYPE (raw)</th>
                        <th>BRAND → Katalog</th>
                        <th>MODEL → Katalog</th>
                        <th>TYPE → Katalog</th>
                        <th>POWERTRAIN</th>
                        <th>CATEGORY</th>
                        <th>SIZE</th>
                        <th>Masalah</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $r)
                        <tr>
                            <td style="font-family: monospace; font-weight: 700; color: #f9fafb; white-space: nowrap;">
                                {{ $r->raw_gabungan ?: '(kosong)' }}
                                <div style="font-size: 10px; color: #6b7280; font-family: monospace;">{{ $r->raw_gabungan_key ?? '— tanpa key —' }}</div>
                            </td>
                            <td>
                                {{ $r->brand_name ?: '—' }}
                                @if ($r->brand_vehicle_id !== null)
                                    <div style="font-size: 11px; color: #10b981;">→ {{ $r->audit_brand_catalog ?? '?' }}</div>
                                @else
                                    <div style="font-size: 11px; color: #ef4444;">→ tidak ter-link</div>
                                @endif
                            </td>
                            <td>
                                {{ $r->model_name ?: '—' }}
                                @if ($r->model_vehicle_id !== null)
                                    <div style="font-size: 11px; color: #10b981;">→ {{ $r->audit_model_catalog ?? '?' }}</div>
                                @elseif ($r->model_name)
                                    <div style="font-size: 11px; color: #ef4444;">→ tidak ter-link</div>
                                @endif
                            </td>
                            <td>
                                {{ $r->type_name ?: '—' }}
                                @if ($r->type_vehicle_id !== null)
                                    <div style="font-size: 11px; color: #10b981;">→ {{ $r->audit_type_catalog ?? '?' }}</div>
                                @elseif (trim((string) $r->type_name) !== '')
                                    <div style="font-size: 11px; color: #ef4444;">→ tidak ter-link</div>
                                @endif
                            </td>
                            <td>
                                @if (trim((string) $r->powertrain) !== '')
                                    <span class="vau-badge {{ $r->powertrain === 'BEV' ? 'vau-badge-ok' : ($r->powertrain === 'ICE' ? 'vau-badge-gray' : 'vau-badge-warn') }}">{{ $r->powertrain }}</span>
                                @else
                                    <span class="vau-badge vau-badge-danger">kosong</span>
                                @endif
                            </td>
                            <td>
                                @if (trim((string) $r->category) !== '')
                                    {{ $r->category }}
                                    @if (trim((string) $r->size_class) !== '')
                                        <div style="font-size: 11px; color: #9ca3af;">{{ $r->size_class }}</div>
                                    @endif
                                @else
                                    <span class="vau-badge vau-badge-danger">kosong</span>
                                @endif
                            </td>
                            <td>{{ $r->size_class ?: '—' }}</td>
                            <td>
                                @if ($r->audit_problems === [])
                                    <span class="vau-badge vau-badge-ok">✓ aman</span>
                                @else
                                    @foreach ($r->audit_problems as $p)
                                        <span class="vau-badge vau-badge-danger" style="margin: 1px 2px 1px 0;">{{ $p }}</span>
                                    @endforeach
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" style="padding: 24px; text-align: center; color: #9ca3af;">Tidak ada baris yang cocok dengan filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>
            {{ $rows->links() }}
        </div>
    </div>
</x-filament-panels::page>
