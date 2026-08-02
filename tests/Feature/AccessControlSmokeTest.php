<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccessControlSmokeTest extends TestCase
{
    use RefreshDatabase;

    private function panel(string $id): Panel
    {
        return Filament::getPanel($id);
    }

    public function test_super_admin_can_access_all_panels(): void
    {
        Role::findOrCreate('super_admin', 'web');
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->assertTrue($user->canAccessPanel($this->panel('admin')));
        $this->assertTrue($user->canAccessPanel($this->panel('user')));
    }

    public function test_admin_role_accesses_admin_panel(): void
    {
        Role::findOrCreate('admin', 'web');
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->assertTrue($user->canAccessPanel($this->panel('admin')));
    }

    public function test_regular_user_cannot_access_admin_panel(): void
    {
        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('user', 'web');
        $user = User::factory()->create();
        $user->syncRoles(['user']);

        $this->assertFalse($user->canAccessPanel($this->panel('admin')));
        $this->assertTrue($user->canAccessPanel($this->panel('user')));
    }

    public function test_user_without_any_role_cannot_access_any_panel(): void
    {
        $user = User::factory()->create();
        $user->syncRoles([]);

        $this->assertFalse($user->canAccessPanel($this->panel('admin')));
        $this->assertFalse($user->canAccessPanel($this->panel('user')));
    }
}
