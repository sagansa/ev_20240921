<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

abstract class ApiTestCase extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected User $authUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutExceptionHandling();

        $this->authUser = User::factory()->create(['email' => 'admin@admin.com']);

        Sanctum::actingAs($this->authUser, abilities: ['*']);
    }
}
