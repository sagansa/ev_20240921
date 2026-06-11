<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class CheckImageConfig extends Command
{
    protected $signature = 'img:check';
    protected $description = 'Check image service configuration and connectivity';

    public function handle(): int
    {
        $secret = config('services.image.upload_secret');
        $serviceUrl = config('services.image.service_url');

        $this->info('=== Image Service Configuration ===');
        $this->line('IMAGE_UPLOAD_SECRET: ' . ($secret ? '✅ SET (' . strlen($secret) . ' chars)' : '❌ NOT SET'));
        $this->line('IMG_SERVICE_URL: ' . ($serviceUrl ?: '❌ NOT SET'));
        $this->line('Config cached: ' . (app()->configurationIsCached() ? '⚠️ YES (run config:clear)' : 'No'));

        if ($secret && $serviceUrl) {
            $this->newLine();
            $this->info('Testing connectivity to img service...');

            try {
                $expires = time() + 300;
                $signature = hash_hmac('sha256', "expires={$expires}", $secret);
                $url = rtrim($serviceUrl, '/') . "/api/upload?expires={$expires}&signature={$signature}";

                $this->line('Upload URL: ' . $url);

                // Just test GET to see if the service is reachable
                $response = Http::timeout(10)->get($serviceUrl);
                $this->line('Service status: ' . $response->status());
                $this->info('✅ Image service is reachable');
            } catch (\Exception $e) {
                $this->error('❌ Cannot reach image service: ' . $e->getMessage());
            }
        }

        return 0;
    }
}