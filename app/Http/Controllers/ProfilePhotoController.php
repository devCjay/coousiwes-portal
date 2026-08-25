<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProfilePhotoController extends Controller
{
    public function __invoke(User $user): BinaryFileResponse|Response
    {
        $path = $user->metadata['profile_photo_path'] ?? null;

        abort_if(! is_string($path) || ! str_starts_with($path, 'profile-photos/'), 404);
        abort_unless(Storage::disk('public')->exists($path), 404);

        return response()->file(Storage::disk('public')->path($path), [
            'Content-Type' => Storage::disk('public')->mimeType($path) ?: 'image/jpeg',
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }
}
