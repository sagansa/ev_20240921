<?php

namespace Tests\Feature;

use App\Filament\Imports\VehicleHierarchyImporter;
use App\Models\BrandVehicle;
use App\Models\ModelVehicle;
use App\Models\TypeVehicle;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class VehicleHierarchyImporterTest extends TestCase
{
    use RefreshDatabase;

    private function invokeRow(string $brand, string $model, string $type = '', ?string $powertrain = null): void
    {
        $importer = new VehicleHierarchyImporter(
            import: new Import(),
            columnMap: ['BRAND' => 'BRAND', 'MODEL' => 'MODEL', 'TYPE' => 'TYPE', 'POWERTRAIN' => 'POWERTRAIN'],
            options: [],
        );

        $row = ['BRAND' => $brand, 'MODEL' => $model, 'TYPE' => $type];

        if ($powertrain !== null) {
            $row['POWERTRAIN'] = $powertrain;
        }

        ($importer)($row);
    }

    public function test_creates_full_hierarchy_from_one_row(): void
    {
        $this->invokeRow('AION', 'AION UT', 'Premium');

        $this->assertSame(1, BrandVehicle::count());
        $this->assertSame(1, ModelVehicle::count());
        $this->assertSame(1, TypeVehicle::count());

        $type = TypeVehicle::query()->firstOrFail();
        $this->assertSame('Premium', $type->name);
        $this->assertSame('AION UT', $type->modelVehicle->name);
        $this->assertSame('AION', $type->modelVehicle->brandVehicle->name);
        $this->assertSame([], $type->type_charger);
    }

    public function test_matches_brand_case_insensitively_and_keeps_first_casing(): void
    {
        $this->invokeRow('CHANGAN', 'DEEPAL S07');
        $this->invokeRow('Changan', 'Lumin');

        $this->assertSame(1, BrandVehicle::count());
        $this->assertSame('CHANGAN', BrandVehicle::query()->value('name'));
        $this->assertSame(2, ModelVehicle::count());
    }

    public function test_empty_type_creates_brand_and_model_without_type(): void
    {
        $this->invokeRow('AION', 'AION ES');

        $this->assertSame(1, BrandVehicle::count());
        $this->assertSame(1, ModelVehicle::count());
        $this->assertSame(0, TypeVehicle::count());
    }

    public function test_reimport_is_idempotent(): void
    {
        $this->invokeRow('BYD', 'Sealion 7', 'Premium');
        $this->invokeRow('BYD', 'Sealion 7', 'Premium');

        $this->assertSame(1, BrandVehicle::count());
        $this->assertSame(1, ModelVehicle::count());
        $this->assertSame(1, TypeVehicle::count());
    }

    public function test_blank_brand_or_model_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        $this->invokeRow('', 'Sealion 7');
    }

    public function test_blank_model_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        $this->invokeRow('BYD', '');
    }

    public function test_whitespace_only_inputs_are_rejected(): void
    {
        $this->expectException(ValidationException::class);

        $this->invokeRow('   ', '   ');
    }

    public function test_reimport_with_different_type_casing_keeps_first_casing(): void
    {
        $this->invokeRow('AION', 'AION UT', 'Premium');
        $this->invokeRow('AION', 'AION UT', 'PREMIUM');

        $this->assertSame(1, TypeVehicle::count());
        $this->assertSame('Premium', TypeVehicle::query()->value('name'));
    }

    public function test_powertrain_type_terisi_saat_type_baru_dan_diisi_bila_kosong(): void
    {
        $this->invokeRow('AION', 'AION UT', 'Premium', 'BEV');

        // Type baru → powertrain type ikut dari CSV.
        $this->assertSame('BEV', TypeVehicle::query()->where('name', 'Premium')->value('powertrain'));

        // Type existing dgn powertrain kosong → diisi dari CSV.
        $this->invokeRow('AION', 'AION UT', 'Standard', 'BEV');

        $this->assertSame('BEV', TypeVehicle::query()->where('name', 'Standard')->value('powertrain'));
    }

    public function test_invalid_powertrain_fails_the_row(): void
    {
        $this->expectException(ValidationException::class);

        $this->invokeRow('BYD', 'Sealion 7', 'Premium', 'GASOLINE');
    }

    private function invokeClassifiedRow(
        string $brand,
        string $model,
        ?string $category = null,
        ?string $size = null,
        string $type = '',
    ): void {
        $importer = new VehicleHierarchyImporter(
            import: new Import(),
            columnMap: [
                'BRAND' => 'BRAND', 'MODEL' => 'MODEL', 'TYPE' => 'TYPE',
                'POWERTRAIN' => 'POWERTRAIN', 'CATEGORY' => 'CATEGORY', 'SIZE' => 'SIZE',
            ],
            options: [],
        );

        ($importer)(array_filter([
            'BRAND' => $brand,
            'MODEL' => $model,
            'TYPE' => $type,
            'CATEGORY' => $category,
            'SIZE' => $size,
        ], fn ($v) => $v !== null && $v !== ''));
    }

    public function test_category_and_size_are_set_on_new_model(): void
    {
        $this->invokeClassifiedRow('TOYOTA', 'Avanza', 'mpv', 'small', '1.5 G');

        $model = ModelVehicle::query()->where('name', 'Avanza')->firstOrFail();
        $this->assertSame('MPV', $model->category);
        $this->assertSame('Small', $model->size_class);
    }

    public function test_category_without_size_leaves_size_null(): void
    {
        $this->invokeClassifiedRow('SUZUKI', 'Jimny', 'off-road');

        $model = ModelVehicle::query()->where('name', 'Jimny')->firstOrFail();
        $this->assertSame('Off-Road', $model->category);
        $this->assertNull($model->size_class);
    }

    public function test_category_overwrites_existing_model_classification(): void
    {
        $this->invokeClassifiedRow('HONDA', 'CR-V', 'MPV');
        $this->invokeClassifiedRow('HONDA', 'CR-V', 'SUV', 'Medium');

        $this->assertSame(1, ModelVehicle::count());
        $this->assertSame('SUV', ModelVehicle::query()->value('category'));
        $this->assertSame('Medium', ModelVehicle::query()->value('size_class'));
    }

    public function test_invalid_category_fails_the_row(): void
    {
        $this->expectException(ValidationException::class);

        // TYPE harus terisi — baris tanpa type selesai di resolveRecord
        // sebelum validateData (perilaku Filament Importer).
        $this->invokeClassifiedRow('TOYOTA', 'Avanza', 'Kapal Selam', null, '1.5 G');
    }

    public function test_size_without_sizable_category_fails_the_row(): void
    {
        $this->expectException(ValidationException::class);

        $this->invokeClassifiedRow('SUZUKI', 'Jimny', 'Off-Road', 'Small');
    }

    public function test_size_without_category_fails_the_row(): void
    {
        $this->expectException(ValidationException::class);

        $this->invokeClassifiedRow('SUZUKI', 'Jimny', null, 'Small');
    }
}
