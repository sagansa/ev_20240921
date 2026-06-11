<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Http\Request;
use Illuminate\Support\Facades\Log;

class ImageUploadUrlController extends Controller
{
    /**
     * Generate a signed upload URL for img.sagansa.id
     */
    public function getUploadUrl(Request $request)
    {
        $secret = config('services.image.upload_secret');
        if (!$secret) {
            Log::error('ImageUpload: IMAGE_UPLOAD_SECRET is not configured');
            return response()->json(['error' => 'Image service not configured.'], 500);
        }

        $request->validate([
            'directory' => 'nullable|string|max:255',
        ]);

        $directory = trim($request->input('directory', ''), '/');

        // Valid for 5 minutes
        $expires = time() + 300;

        // Create cryptographic signature
        $signature = hash_hmac('sha256', "expires={$expires}", $secret);

        // Build full URL to img service
        $imgServiceUrl = rtrim(config('services.image.service_url', 'https://img.sagansa.id'), '/') . '/api/upload';

        $uploadUrl = "{$imgServiceUrl}?expires={$expires}&signature={$signature}";

        if ($directory) {
            $uploadUrl .= "&directory=" . urlencode($directory);
        }

        return response()->json([
            'success' => true,
            'upload_url' => $uploadUrl,
            'expires_at' => date('Y-m-d H:i:s', $expires),
        ]);
    }
}