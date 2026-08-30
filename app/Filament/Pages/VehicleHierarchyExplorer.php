<?php

namespace App\Filament\Pages;

use App\Services\VehicleHierarchyReport;
use App\Support\VehicleCategories;
use Filament\Pages\Page;
use Livewire\Attributes\Url;

/**
 * Halaman "Hierarki Kendaraan" — pohon interaktif brand → model → type
 * dgn angka penjualan per node + indikator kesehatan hubungan (model tanpa
 * kategori, stats tak ter-link, type yatim).
 */
class VehicleHierarchyExplorer extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-m-rectangle-group';

    protected static string | \UnitEnum | null $navigationGroup = 'Referensi Kendaraan';

    protected static ?string $navigationLabel = 'Hierarki Kendaraan';

    protected static ?string $title = 'Hierarki Kendaraan — Brand · Model · Type';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.vehicle-hierarchy-explorer';

    #[Url]
    public ?int $year = null;

    #[Url]
    public string $powertrain = 'ALL';

    #[Url]
    public string $category = 'ALL';

    #[Url]
    public string $search = '';

    #[Url]
    public bool $onlyIssues = false;

    /** Node yang sedang terbuka: "b{brandId}" / "m{modelId}". */
    public array $expanded = ['brands' => [], 'models' => []];

    public function mount(): void
    {
        $this->year ??= (app(VehicleHierarchyReport::class)->build(null)['year']);
    }

    public function toggleBrand(int $id): void
    {
        $key = 'b'.$id;
        $this->expanded['brands'] = in_array($key, $this->expanded['brands'], true)
            ? array_values(array_diff($this->expanded['brands'], [$key]))
            : [...$this->expanded['brands'], $key];
    }

    public function toggleModel(int $id): void
    {
        $key = 'm'.$id;
        $this->expanded['models'] = in_array($key, $this->expanded['models'], true)
            ? array_values(array_diff($this->expanded['models'], [$key]))
            : [...$this->expanded['models'], $key];
    }

    public function expandAll(): void
    {
        $report = app(VehicleHierarchyReport::class)->build(
            $this->year,
            $this->powertrain,
            $this->category === 'ALL' ? null : $this->category,
            $this->search,
            $this->onlyIssues
        );

        $this->expanded = [
            'brands' => array_map(fn ($b) => 'b'.$b['id'], $report['brands']),
            'models' => array_values(array_reduce($report['brands'], fn ($carry, $b) => array_merge(
                $carry ?? [],
                array_map(fn ($m) => 'm'.$m['id'], $b['models']),
            ), [])),
        ];
    }

    public function collapseAll(): void
    {
        $this->expanded = ['brands' => [], 'models' => []];
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->powertrain = 'ALL';
        $this->category = 'ALL';
        $this->onlyIssues = false;
    }

    public function toggleOnlyIssues(): void
    {
        $this->onlyIssues = ! $this->onlyIssues;
        if ($this->onlyIssues) {
            $this->expandAll();
        }
    }

    protected function getViewData(): array
    {
        $report = app(VehicleHierarchyReport::class)->build(
            $this->year,
            $this->powertrain,
            $this->category === 'ALL' ? null : $this->category,
            $this->search,
            $this->onlyIssues
        );

        return array_merge(parent::getViewData(), [
            'report' => $report,
            'categoryOptions' => VehicleCategories::CATEGORIES,
        ]);
    }
}
