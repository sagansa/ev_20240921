<x-filament-panels::page>
    <form class="space-y-6">
        {{ $this->form }}
    </form>

    @if ($lastImportSummary)
        <x-filament::section class="mt-6">
            <x-slot name="heading">
                Ringkasan import terakhir
            </x-slot>

            <dl class="grid gap-4 md:grid-cols-3">
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Baris CSV</dt>
                    <dd class="text-xl font-semibold">{{ number_format($lastImportSummary['total_rows']) }}</dd>
                </div>
                @if (array_key_exists('location_rows', $lastImportSummary))
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Baris lokasi</dt>
                        <dd class="text-xl font-semibold">{{ number_format($lastImportSummary['location_rows']) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Baris detail</dt>
                        <dd class="text-xl font-semibold">{{ number_format($lastImportSummary['detail_rows']) }}</dd>
                    </div>
                @endif
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Lokasi diimport</dt>
                    <dd class="text-xl font-semibold">{{ number_format($lastImportSummary['inserted_locations']) }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Detail charger diimport</dt>
                    <dd class="text-xl font-semibold">{{ number_format($lastImportSummary['inserted_details']) }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Lokasi lama dihapus</dt>
                    <dd class="text-xl font-semibold">{{ number_format($lastImportSummary['deleted_locations']) }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Detail lama dihapus</dt>
                    <dd class="text-xl font-semibold">{{ number_format($lastImportSummary['deleted_details']) }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Baris dilewati</dt>
                    <dd class="text-xl font-semibold">{{ number_format($lastImportSummary['skipped_rows']) }}</dd>
                </div>
            </dl>
        </x-filament::section>
    @endif
</x-filament-panels::page>
