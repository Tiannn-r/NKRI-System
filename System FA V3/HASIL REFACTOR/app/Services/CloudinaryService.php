<?php

namespace App\Services;

use App\Models\DoSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudinaryService
{
    protected ?string $cloudName;
    protected ?string $apiKey;
    protected ?string $apiSecret;

    public function __construct()
    {
        $settings = DoSetting::first();
        $this->cloudName = $settings?->cloudinary_cloud_name ?: config('services.cloudinary.cloud_name', env('CLOUDINARY_CLOUD_NAME'));
        $this->apiKey = $settings?->cloudinary_api_key ?: config('services.cloudinary.api_key', env('CLOUDINARY_API_KEY'));
        $this->apiSecret = $settings?->cloudinary_api_secret ?: config('services.cloudinary.api_secret', env('CLOUDINARY_API_SECRET'));
    }

    public function isConfigured(): bool
    {
        return !empty($this->cloudName) && !empty($this->apiKey) && !empty($this->apiSecret);
    }

    public function getPublicInfo(): array
    {
        return [
            'configured' => $this->isConfigured(),
            'cloud_name' => $this->cloudName ? substr($this->cloudName, 0, 3) . '***' : null,
            'api_key' => $this->apiKey ? substr($this->apiKey, 0, 4) . '***' : null,
        ];
    }

    /**
     * Upload PDF file to Cloudinary
     */
    public function uploadPdf(string $pdfBase64OrPath, string $publicId, string $folder = 'nanggala_p9_do'): ?array
    {
        if (!$this->isConfigured()) {
            Log::warning('Cloudinary credentials not configured.');
            return null;
        }

        try {
            $timestamp = time();
            $params = [
                'folder' => $folder,
                'public_id' => $publicId,
                'timestamp' => $timestamp,
            ];

            // Generate Cloudinary SHA1 signature
            ksort($params);
            $sigString = '';
            foreach ($params as $k => $v) {
                $sigString .= "{$k}={$v}&";
            }
            $sigString = rtrim($sigString, '&') . $this->apiSecret;
            $signature = sha1($sigString);

            $uploadUrl = "https://api.cloudinary.com/v1_1/{$this->cloudName}/raw/upload";

            $response = Http::asMultipart()->post($uploadUrl, [
                'file' => $pdfBase64OrPath,
                'api_key' => $this->apiKey,
                'timestamp' => $timestamp,
                'folder' => $folder,
                'public_id' => $publicId,
                'signature' => $signature,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Cloudinary upload failed: ' . $response->body());
            return null;
        } catch (\Throwable $e) {
            Log::error('Cloudinary exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Delete file from Cloudinary
     */
    public function deleteFile(string $publicId): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        try {
            $timestamp = time();
            $params = [
                'public_id' => $publicId,
                'timestamp' => $timestamp,
            ];
            ksort($params);
            $sigString = "public_id={$publicId}&timestamp={$timestamp}" . $this->apiSecret;
            $signature = sha1($sigString);

            $destroyUrl = "https://api.cloudinary.com/v1_1/{$this->cloudName}/raw/destroy";
            $response = Http::asForm()->post($destroyUrl, [
                'public_id' => $publicId,
                'api_key' => $this->apiKey,
                'timestamp' => $timestamp,
                'signature' => $signature,
            ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('Cloudinary delete exception: ' . $e->getMessage());
            return false;
        }
    }
}
