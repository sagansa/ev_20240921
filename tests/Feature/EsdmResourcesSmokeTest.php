<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EsdmResourcesSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_esdm_resources_render_for_super_admin(): void
    {
        Role::findOrCreate('super_admin', 'web');
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $urls = [
            '/admin/panel/charging-stations',
            '/admin/panel/esdm-spklu-stations',
            '/admin/panel/esdm-station-statuses',
            '/admin/panel/esdm-spbklu-stations',
        ];

        foreach ($urls as $url) {
            $response = $this->actingAs($user)->get($url);
            $this->assertEquals(200, $response->getStatusCode(), "URL gagal: {$url}");
        }
    }

    public function test_esdm_resources_forbidden_for_regular_user(): void
    {
        Role::findOrCreate('user', 'web');
        $user = User::factory()->create();
        $user->assignRole('user');

        $this->actingAs($user)
            ->get('/admin/panel/charging-stations')
            ->assertForbidden();
    }
}
