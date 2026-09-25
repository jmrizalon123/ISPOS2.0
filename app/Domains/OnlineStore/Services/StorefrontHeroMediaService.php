<?php

namespace App\Domains\OnlineStore\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StorefrontHeroMediaService
{
    public const MAX_KILOBYTES = 4096;

    protected const DIRECTORY = 'storefront/heroes';

    public function store(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');

        return $file->storeAs(self::DIRECTORY, 'hero-'.Str::uuid()->toString().'.'.$extension, 'public');
    }

    public function replace(?string $previous, UploadedFile $file): string
    {
        $this->deleteStored($previous);

        return $this->store($file);
    }

    public function forget(?string $path): void
    {
        $this->deleteStored($path);
    }

    /** Externally hosted images are referenced, not owned, so they are never deleted. */
    protected function deleteStored(?string $path): void
    {
        $path = trim((string) $path);

        if ($path === '' || Str::startsWith($path, ['http://', 'https://', '/'])) {
            return;
        }

        Storage::disk('public')->delete($path);
    }
}
