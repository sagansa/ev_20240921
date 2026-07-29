<?php

namespace Tests\Unit;

use App\Models\Provider;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProviderImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_provider_image_accessor_and_mutator_behavior()
    {
        $provider = new Provider();

        // 1. Setting null or empty string should result in null in DB
        $provider->image = null;
        $this->assertNull($provider->getAttributes()['image'] ?? null);
        $this->assertNull($provider->image);

        $provider->image = '';
        $this->assertNull($provider->getAttributes()['image'] ?? null);
        $this->assertNull($provider->image);

        // 2. Setting fallback image URL should result in null in DB
        $provider->image = '/images/ev-charging.png';
        $this->assertNull($provider->getAttributes()['image'] ?? null);

        // 3. Setting storage relative path
        $provider->image = 'images/provider/test.webp';
        $this->assertEquals('images/provider/test.webp', $provider->getAttributes()['image']);

        // 4. Setting storage prefixed path (/storage/images/provider/test.webp) should sanitize to relative
        $provider->image = '/storage/images/provider/test.webp';
        $this->assertEquals('images/provider/test.webp', $provider->getAttributes()['image']);

        // 5. Setting full HTTP/HTTPS URL should preserve full URL
        $provider->image = 'https://img.sagansa.id/images/provider/test.webp';
        $this->assertEquals('https://img.sagansa.id/images/provider/test.webp', $provider->getAttributes()['image']);

        // 6. Accessing image getter for full URL
        $this->assertEquals('https://img.sagansa.id/images/provider/test.webp', $provider->image);

        // 7. Accessing image getter for relative path
        $provider->image = 'images/provider/test.webp';
        $this->assertEquals('/storage/images/provider/test.webp', $provider->image);
    }
}
