<?php

namespace App\Filament\Pages;

use App\Models\BrandVehicle;
use App\Models\ModelVehicle;
use App\Models\TypeVehicle;
use App\Models\VehicleConnecting;
use Filament\Pages\Page;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\WithPagination;

/**
 * Halaman "Audit Raw Connecting" — tampilan tabel vehicle_connectings
 * sesuai format file GAIKINDO CONNECTING (BRAND MODEL TYPE, BRAND, MODEL,
 * TYPE, POWERTRAIN, CATEGORY, SIZE) plus status link katalog, untuk
 * melihat di mana masalah data berada. Read-only.
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

    protected function rows(): LengthAwarePaginator
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

        $filtered = match ($this->filter) {
            'problem' => $decorated->filter(fn ($r) => $r->audit_problems !== []),
            'no_key' => $decorated->filter(fn ($r) => $r->raw_gabungan_key === null),
            'dup' => $decorated->filter(fn ($r) => $r->raw_gabungan_key !== null && $dupKeys->has($r->raw_gabungan_key)),
            'unlinked_brand' => $decorated->filter(fn ($r) => $r->brand_vehicle_id === null),
            'unlinked_model' => $decorated->filter(fn ($r) => $r->model_vehicle_id === null),
            'unlinked_type' => $decorated->filter(fn ($r) => trim((string) $r->type_name) !== '' && $r->type_vehicle_id === null),
            'no_category' => $decorated->filter(fn ($r) => trim((string) $r->category) === ''),
            'no_powertrain' => $decorated->filter(fn ($r) => trim((string) $r->powertrain) === ''),
            default => $decorated,
        };

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

    public array $summary = [];

    protected function getViewData(): array
    {
        return array_merge(parent::getViewData(), [
            'rows' => $this->rows(),
            'summary' => $this->summary,
            'filters' => self::FILTERS,
        ]);
    }
}
