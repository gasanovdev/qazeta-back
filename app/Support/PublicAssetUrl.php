<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class PublicAssetUrl
{
    public static function fromStoragePath(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }
}
