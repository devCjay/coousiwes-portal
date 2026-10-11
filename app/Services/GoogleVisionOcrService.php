<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

class GoogleVisionOcrService
{
    public function extractText(UploadedFile $image): string
    {
        $apiKey = trim((string) AppSetting::value('ocr.google_vision_api_key', ''));

        if ($apiKey === '') {
            throw new \RuntimeException('Google Vision API key is not configured.');
        }

        $response = Http::timeout(45)
            ->acceptJson()
            ->post("https://vision.googleapis.com/v1/images:annotate?key={$apiKey}", [
                'requests' => [[
                    'image' => [
                        'content' => base64_encode((string) file_get_contents($image->getRealPath())),
                    ],
                    'features' => [[
                        'type' => 'DOCUMENT_TEXT_DETECTION',
                        'maxResults' => 1,
                    ]],
                ]],
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Google Vision OCR request failed.');
        }

        $payload = $response->json();
        $error = $payload['responses'][0]['error']['message'] ?? null;

        if ($error) {
            throw new \RuntimeException((string) $error);
        }

        return (string) ($payload['responses'][0]['fullTextAnnotation']['text']
            ?? $payload['responses'][0]['textAnnotations'][0]['description']
            ?? '');
    }
}
