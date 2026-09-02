<?php

namespace App\Filament\Pages;

use App\Models\BrandVehicle;
use App\Models\ModelVehicle;
use App\Models\TypeVehicle;
use App\Models\VehicleConnecting;
use Filament\Pages\Page;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\WithPagination;

/**
 * Halaman "Audit Raw Connecting" — tampilan tabel vehicle_connectings
 * sesuai format file GAIKINDO CONNECTING (BRAND MODEL TYPE, BRAND, MODEL,
 * TYPE, POWERTRAIN, CATEGORY, SIZE) plus status link katalog, untuk
 * melihat di mana masalah data berada. Read-only + unduh CSV.
 */
class VehicleConnectingAudit extends Page
{
    use WithPagination;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-m-table-cells';

    protected static string | \UnitEnum | null $navigationGroup = 'Referensi Kendaraan';

    protected static ?string $navigationLabel = 'Audit Raw Connecting';

    protected static ?string $title = 'Audit Raw Connecting (Master GAIKINDO)';

    protected static ?int $navigationSort = 7;

    protected string $view = 'filament.pages.vehicle-connecting-audit';

    public string $filter = 'all';

    public string $search = '';

    public array $summary = [];

    /** @var array<string, string> label filter masalah */
    public const FILTERS = [
        'all' => 'Semua',
        'problem' => '⚠ Bermasalah (semua)',
        'no_key' => 'Tanpa raw key',
        'dup' => 'Key duplikat',
        'unlinked_brand' => 'Brand tak ter-link',
        'unlinked_model' => 'Model tak ter-link',
        'unlinked_type' => 'Type tak ter-link',
        'no_category' => 'Kategori kosong',
        'no_powertrain' => 'Powertrain kosong',
    ];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Kumpulan baris ter-dekorasi sesuai filter aktif (tanpa paginasi) —
     * dipakai bersama oleh tampilan (dinotasikan) dan unduhan CSV.
     *
     * @return Collection<int, VehicleConnecting>
     */
    protected function buildRows(): Collection
    {
        $query = VehicleConnecting::query()->orderBy('raw_gabungan');

        if ($this->search !== '') {
            $needle = '%'.str_replace(' ', '%', trim($this->search)).'%';
            $query->where(fn ($q) => $q
                ->where('raw_gabungan', 'like', $needle)
                ->orWhere('brand_name', 'like', $needle)
                ->orWhere('model_name', 'like', $needle)
                ->orWhere('type_name', 'like', $needle));
        }

        $all = $query->get();

        // Key duplikat: squash sama dipakai >1 baris.
        $dupKeys = $all->whereNotNull('raw_gabungan_key')
            ->groupBy('raw_gabungan_key')
            ->filter(fn ($g) => $g->count() > 1)
            ->keys()
            ->flip();

        $problemOf = function (VehicleConnecting $r) use ($dupKeys): array {
            $problems = [];

            if (trim((string) $r->raw_gabungan) === '') {
                $problems[] = 'raw kosong';
            } elseif ($r->raw_gabungan_key === null) {
                $problems[] = 'tanpa raw key';
            }

            if ($r->raw_gabungan_key !== null && $dupKeys->has($r->raw_gabungan_key)) {
                $problems[] = 'key duplikat';
            }

            if ($r->brand_vehicle_id === null) {
                $problems[] = 'brand tak ter-link';
            }

            if ($r->model_vehicle_id === null) {
                $problems[] = 'model tak ter-link';
            } elseif (trim((string) $r->type_name) !== '' && $r->type_vehicle_id === null) {
                $problems[] = 'type tak ter-link';
            }

            if (trim((string) $r->category) === '') {
                $problems[] = 'kategori kosong';
            }

            if (trim((string) $r->powertrain) === '') {
                $problems[] = 'powertrain kosong';
            }

            return $problems;
        };

        // Dekorasi + hitung ringkasan sekali untuk seluruh hasil filter teks.
        $brandNames = BrandVehicle::pluck('name', 'id');
        $modelNames = ModelVehicle::pluck('name', 'id');
        $typeNames = TypeVehicle::pluck('name', 'id');

        $decorated = $all->map(function (VehicleConnecting $r) use ($problemOf, $brandNames, $modelNames, $typeNames) {
            $r->audit_problems = $problemOf($r);
            $r->audit_brand_catalog = $brandNames[$r->brand_vehicle_id] ?? null;
            $r->audit_model_catalog = $modelNames[$r->model_vehicle_id] ?? null;
            $r->audit_type_catalog = $typeNames[$r->type_vehicle_id] ?? null;

            return $r;
        });

        $this->summary = [
            'total' => $decorated->count(),
            'problem' => $decorated->filter(fn ($r) => $r->audit_problems !== [])->count(),
            'no_key' => $decorated->filter(fn ($r) => $r->raw_gabungan_key === null)->count(),
            'dup' => $decorated->filter(fn ($r) => $r->raw_gabungan_key !== null && $dupKeys->has($r->raw_gabungan_key))->count(),
            'unlinked_brand' => $decorated->filter(fn ($r) => $r->brand_vehicle_id === null)->count(),
            'unlinked_model' => $decorated->filter(fn ($r) => $r->model_vehicle_id === null)->count(),
            'unlinked_type' => $decorated->filter(fn ($r) => trim((string) $r->type_name) !== '' && $r->type_vehicle_id === null)->count(),
            'no_category' => $decorated->filter(fn ($r) => trim((string) $r->category) === '')->count(),
            'no_powertrain' => $decorated->filter(fn ($r) => trim((string) $r->powertrain) === '')->count(),
        ];

        return match ($this->filter) {
            'problem' => $decorated->filter(fn ($r) => $r->audit_problems !== [])->values(),
            'no_key' => $decorated->filter(fn ($r) => $r->raw_gabungan_key === null)->values(),
            'dup' => $decorated->filter(fn ($r) => $r->raw_gabungan_key !== null && $dupKeys->has($r->raw_gabungan_key))->values(),
            'unlinked_brand' => $decorated->filter(fn ($r) => $r->brand_vehicle_id === null)->values(),
            'unlinked_model' => $decorated->filter(fn ($r) => $r->model_vehicle_id === null)->values(),
            'unlinked_type' => $decorated->filter(fn ($r) => trim((string) $r->type_name) !== '' && $r->type_vehicle_id === null)->values(),
            'no_category' => $decorated->filter(fn ($r) => trim((string) $r->category) === '')->values(),
            'no_powertrain' => $decorated->filter(fn ($r) => trim((string) $r->powertrain) === '')->values(),
            default => $decorated->values(),
        };
    }

    protected function rows(): LengthAwarePaginator
    {
        $filtered = $this->buildRows();
        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 50;

        return new LengthAwarePaginator(
            $filtered->forPage($page, $perPage)->values(),
            $filtered->count(),
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath()],
        );
    }

    /** Unduh hasil audit (sesuai filter aktif) sebagai CSV format CONNECTING. */
    public function download(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $rows = $this->buildRows();
        $filename = 'audit-connecting-'.($this->filter !== 'all' ? $this->filter.'-' : '').now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            // BOM agar Excel membaca UTF-8 dengan benar.
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'BRAND MODEL TYPE', 'FUEL', 'BRAND', 'MODEL', 'TYPE',
                'POWERTRAIN', 'CATEGORY', 'SIZE',
                'RAW KEY', 'BRAND KATALOG', 'MODEL KATALOG', 'TYPE KATALOG', 'MASALAH',
            ]);

            /** @var VehicleConnecting $r */
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->raw_gabungan,
                    $r->fuel,
                    $r->brand_name,
                    $r->model_name,
                    $r->type_name,
                    $r->powertrain,
                    $r->category,
                    $r->size_class,
                    $r->raw_gabungan_key,
                    $r->audit_brand_catalog,
                    $r->audit_model_catalog,
                    $r->audit_type_catalog,
                    implode('; ', $r->audit_problems ?: []),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    protected function getViewData(): array
    {
        return array_merge(parent::getViewData(), [
            'rows' => $this->rows(),
            'summary' => $this->summary,
            'filters' => self::FILTERS,
        ]);
    }
}
