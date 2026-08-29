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

    public function test_powertrain_is_set_on_new_model(): void
    {
        $this->invokeRow('AION', 'AION UT', 'Premium', 'BEV');

        $this->assertSame('BEV', ModelVehicle::query()->where('name', 'AION UT')->value('powertrain'));
    }

    public function test_missing_powertrain_falls_back_to_default(): void
    {
        $this->invokeRow('AION', 'AION ES');

        $this->assertSame('ICE', ModelVehicle::query()->value('powertrain'));
    }

    public function test_powertrain_overwrites_existing_model_classification(): void
    {
        $this->invokeRow('AION', 'AION UT', 'Premium', 'ICE');
        $this->invokeRow('AION', 'AION UT', 'Premium', 'BEV');

        $this->assertSame(1, ModelVehicle::count());
        $this->assertSame('BEV', ModelVehicle::query()->value('powertrain'));
    }

    public function test_lowercase_powertrain_is_normalized(): void
    {
        $this->invokeRow('BYD', 'Sealion 7', 'Premium', 'bev');

        $this->assertSame('BEV', ModelVehicle::query()->value('powertrain'));
    }

    public function test_invalid_powertrain_fails_the_row(): void
    {
        $this->expectException(ValidationException::class);

        $this->invokeRow('BYD', 'Sealion 7', 'Premium', 'GASOLINE');
    }
}
