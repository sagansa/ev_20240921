<?php

namespace Tests\Feature;

use App\Filament\Pages\VehicleConnectingSync;
use App\Models\BrandVehicle;
use App\Models\ModelVehicle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VehicleConnectingSyncPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('super_admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $this->actingAs($admin);

        $gac = BrandVehicle::create(['name' => 'GAC']);
        ModelVehicle::create(['name' => 'AION', 'brand_vehicle_id' => $gac->id, 'powertrain' => 'BEV', 'category' => 'Sedan']);
    }

    private function csv(): UploadedFile
    {
        $content = implode("\n", [
            'BRAND MODEL TYPE,FUEL,BRAND,MODEL,TYPE,POWERTRAIN,CATEGORY,SIZE',
            'GAC AION ES,EV,GAC,AION,ES,BEV,Sedan,Medium',
            'GAC AION V,EV,GAC,AION V,,BEV,SUV,Small',
        ]);

        return UploadedFile::fake()->createWithContent('connecting.csv', $content);
    }

    public function test_verifikasi_tanpa_menulis_db(): void
    {
        $before = ModelVehicle::count();

        Livewire::test(VehicleConnectingSync::class)
            ->set('csvFile', $this->csv())
            ->call('verify')
            ->assertSee('Hasil Verifikasi')
            ->assertSee('Model Baru');

        $this->assertSame($before, ModelVehicle::count());
    }

    public function test_sinkronisasi_membuat_model_baru_dan_kategorinya(): void
    {
        Livewire::test(VehicleConnectingSync::class)
            ->set('csvFile', $this->csv())
            ->call('sync')
            ->assertSee('Hasil Sinkronisasi');

        $aionV = ModelVehicle::where('name', 'AION V')->first();
        $this->assertNotNull($aionV);
        $this->assertSame('SUV', $aionV->category);
        $this->assertSame('Small', $aionV->size_class);
    }
}
