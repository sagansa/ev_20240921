@php
    $statePath = $getStatePath();
    $defaultPosition = $getDefaultPosition();
    $defaultZoom = $getDefaultZoom();
    $placeholder= $getPlaceholder();
@endphp

<x-dynamic-component
        :component="$getFieldWrapperView()"
        :field="$field"
>
    <div
            x-data="{
            state: $wire.entangle('{{ $statePath }}'),
            map: null,
            marker: null,
            locating: false,
            
            init() {
                this.map = L.map(this.$refs.map).setView({{ json_encode($defaultPosition) }}, {{ $defaultZoom }});
                
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap'
                }).addTo(this.map);
                
                if (this.state) {
                    const coords = this.state.split(',');
                    const lat = parseFloat(coords[0]);
                    const lng = parseFloat(coords[1]);
                    this.marker = L.marker([lat, lng]).addTo(this.map);
                    this.map.setView([lat, lng]);
                }
                
                this.map.on('click', (e) => {
                    this.setPoint(e.latlng.lat, e.latlng.lng, false);
                });
            },

            setPoint(lat, lng, shouldZoom = true) {
                if (this.marker) {
                    this.map.removeLayer(this.marker);
                }

                this.marker = L.marker([lat, lng]).addTo(this.map);
                this.state = `${lat},${lng}`;

                if (shouldZoom) {
                    this.map.setView([lat, lng], Math.max(this.map.getZoom(), 16));
                }
            },

            locateMe() {
                if (!navigator.geolocation) {
                    alert('Geolokasi tidak didukung oleh browser Anda.');
                    return;
                }

                this.locating = true;

                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        this.setPoint(position.coords.latitude, position.coords.longitude);
                        this.locating = false;
                    },
                    () => {
                        this.locating = false;
                        alert('Lokasi saat ini tidak bisa diambil. Pastikan izin lokasi browser sudah aktif.');
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 60000,
                    },
                );
            },
        }"
            wire:ignore
    >
        <div style="position: relative;">
            <div x-ref="map" style="height: 400px; width: 100%; border-radius: 0.5rem;"></div>

            <button
                    type="button"
                    x-on:click="locateMe"
                    x-bind:disabled="locating"
                    style="position: absolute; right: 12px; bottom: 12px; z-index: 1000; display: inline-flex; align-items: center; gap: 8px; border-radius: 8px; background: white; padding: 8px 12px; font-size: 13px; font-weight: 600; color: #1f2937; border: 1px solid #d1d5db; box-shadow: 0 10px 24px -14px rgba(15, 23, 42, 0.55);"
            >
                <span x-text="locating ? 'Mengambil lokasi...' : 'Gunakan lokasi saat ini'"></span>
            </button>
        </div>

        <x-filament::input.wrapper class="mt-2">
            <input
                    type="text"
                    x-model="state"
                    readonly
                    class="fi-input"
                    placeholder="{{$placeholder}}"
            />
        </x-filament::input.wrapper>
    </div>
</x-dynamic-component>

@once
    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    @endpush

    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    @endpush
@endonce
