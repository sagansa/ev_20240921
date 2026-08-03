<x-filament-panels::page>
    {{-- Leaflet CSS + JS + init didaftarkan via render hook (AdminPanelProvider)
         karena <script> inline di halaman Filament dibuang oleh Livewire
         wire:navigate (SPA) — tanpa render hook, map tidak pernah tampil. --}}

    <style>
        #cs-map { background: #e5e7eb; }
        .leaflet-pane, .leaflet-top, .leaflet-bottom { z-index: 1 !important; }
        .leaflet-popup-pane { z-index: 2 !important; }
        .cs-spinner {
            display: inline-block;
            width: 32px; height: 32px;
            border: 4px solid #d1d5db;
            border-top-color: #3b82f6;
            border-radius: 50%;
            animation: cs-spin 0.8s linear infinite;
        }
        @keyframes cs-spin { to { transform: rotate(360deg); } }
    </style>

    <div class="mb-4 flex flex-wrap gap-2 text-sm">
        <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded-full inline-block" style="background:#10B981"></span> Available</span>
        <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded-full inline-block" style="background:#EF4444"></span> Occupied</span>
        <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded-full inline-block" style="background:#6B7280"></span> Offline</span>
        <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded-full inline-block" style="background:#D1D5DB"></span> Unknown</span>
        <span class="ml-auto text-gray-500">Total: <strong>{{ $totalCount }}</strong> stasiun</span>
    </div>

    <div id="cs-map-loading" style="height: 70vh; border-radius: 12px; background: #e5e7eb; display: flex; align-items: center; justify-content: center;">
        <div style="text-align: center; color: #6b7280;">
            <div class="cs-spinner" style="margin-bottom: 12px;"></div>
            <div>Memuat {{ $totalCount }} stasiun...</div>
        </div>
    </div>

    <div id="cs-map" style="height: 70vh; border-radius: 12px; overflow: hidden; z-index: 0; position: relative; display: none;"></div>
</x-filament-panels::page>
