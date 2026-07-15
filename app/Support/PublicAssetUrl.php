<?php

namespace App\Support;

class PublicAssetUrl
{
    public static function fromStoragePath(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        return '/storage/' . ltrim($path, '/');
    }
}
