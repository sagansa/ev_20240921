<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()
            ->count(1)
            ->create([
                'email' => 'admin@admin.com',
                'password' => \Hash::make('admin'),
            ]);

        $this->call(ProvinceSeeder::class);
        $this->call(CitySeeder::class);
        $this->call(DistrictSeeder::class);
        $this->call(SubdistrictSeeder::class);
        $this->call(PostalCodeSeeder::class);
        $this->call(ProviderSeeder::class);
        $this->call(ChargerLocationSeeder::class);
        $this->call(PaymentSeeder::class);
        $this->call(BrandVehicleSeeder::class);
        $this->call(ModelVehicleSeeder::class);
        $this->call(TypeVehicleSeeder::class);
        $this->call(VehicleSeeder::class);
        $this->call(ChargerSeeder::class);
        $this->call(PowerChargerSeeder::class);
        $this->call(CurrentChargerSeeder::class);
        $this->call(TypeChargerSeeder::class);
        $this->call(ChargeSeeder::class);
        $this->call(StateOfHealthSeeder::class);
        $this->call(DiscountHomeChargingSeeder::class);
        $this->call(MerkChargerSeeder::class);
    }
}
