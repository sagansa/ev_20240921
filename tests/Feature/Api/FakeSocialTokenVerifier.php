<?php

namespace Tests\Feature\Api;

use App\Services\SocialTokenVerifier;

class FakeSocialTokenVerifier extends SocialTokenVerifier
{
    private string $provider;
    private ?string $platform;
    private ?array $returnPayload;

    public function __construct(string $provider, ?string $platform, ?array $returnPayload = null)
    {
        $this->provider = $provider;
        $this->platform = $platform;
        $this->returnPayload = $returnPayload;
    }

    public function verifyGoogleToken(string $idToken): ?array
    {
        if ($this->provider !== 'google') {
            return null;
        }

        return $this->returnPayload;
    }

    public function verifyAppleToken(string $idToken): ?array
    {
        if ($this->provider !== 'apple') {
            return null;
        }

        return $this->returnPayload;
    }

    public function resolvePlatform(string $provider, array $payload): ?string
    {
        return $this->platform;
    }
}
