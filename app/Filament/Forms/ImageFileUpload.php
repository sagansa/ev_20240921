<?php

namespace App\Filament\Forms;

use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ImageFileUpload extends FileUpload
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->rules(['image'])
            ->hiddenLabel()
            ->nullable()
            ->openable()
            ->image()
            ->imageEditor()
            ->saveUploadedFileUsing(function (TemporaryUploadedFile $file, self $component): ?string {
                return $this->uploadToImgService($file, $component);
            })
            ->columnSpan(['full'])
            ->imageEditorAspectRatios([null, '16:9', '4:3', '1:1']);
    }

    /**
     * Upload file to img.sagansa.id and return the URL
     */
    protected function uploadToImgService(TemporaryUploadedFile $file, self $component): ?string
    {
        $secret = config('services.image.upload_secret');
        $serviceUrl = config('services.image.service_url', 'https://img.sagansa.id');

        if (!$secret) {
            Log::error('ImageFileUpload: IMAGE_UPLOAD_SECRET not configured, falling back to local storage');
            return $this->uploadToLocalStorage($file, $component);
        }

        try {
            $directory = trim($component->getDirectory() ?? '', '/');

            // Generate signed URL parameters
            $expires = time() + 300;
            $signature = hash_hmac('sha256', "expires={$expires}", $secret);

            $uploadUrl = rtrim($serviceUrl, '/') . '/api/upload';
            $uploadUrl .= "?expires={$expires}&signature={$signature}";

            if ($directory) {
                $uploadUrl .= '&directory=' . urlencode($directory);
            }

            // Upload file to img.sagansa.id
            $response = Http::timeout(30)
                ->attach('image', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
                ->post($uploadUrl);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['url'])) {
                    return $data['url'];
                }
            }

            Log::error('ImageFileUpload: Upload to img service failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            // Fallback to local storage
            return $this->uploadToLocalStorage($file, $component);
        } catch (\Exception $e) {
            Log::error('ImageFileUpload: Exception during upload to img service', [
                'message' => $e->getMessage(),
            ]);

            // Fallback to local storage
            return $this->uploadToLocalStorage($file, $component);
        }
    }

    /**
     * Fallback: upload to local storage as webp
     */
    protected function uploadToLocalStorage(TemporaryUploadedFile $file, self $component): ?string
    {
        if (function_exists('imagewebp')) {
            $image = @imagecreatefromstring(file_get_contents($file->getRealPath()));

            if ($image !== false) {
                imagepalettetotruecolor($image);
                imagealphablending($image, true);
                imagesavealpha($image, true);

                ob_start();
                $saved = imagewebp($image, null, 85);
                $contents = ob_get_clean();
                imagedestroy($image);

                if ($saved && $contents !== false) {
                    $path = trim(($component->getDirectory() ?? '') . '/' . Str::ulid() . '.webp', '/');
                    $component->getDisk()->put($path, $contents);

                    if ($component->getVisibility() === 'public') {
                        rescue(fn() => $component->getDisk()->setVisibility($path, 'public'), report: false);
                    }

                    return $path;
                }
            }
        }

        // Final fallback: save original file
        return $file->store($component->getDirectory(), $component->getDiskName());
    }
}