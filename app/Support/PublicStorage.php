<?php

namespace App\Support;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

class PublicStorage
{
    public static function url(?string $path): ?string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (str_starts_with($path, '/')) {
            return $path;
        }

        return '/storage/'.ltrim($path, '/');
    }

    public static function storeUploadedFile(\Illuminate\Http\UploadedFile $file, string $directory, string $filename): string
    {
        Storage::disk('public')->makeDirectory($directory);

        $path = $file->storeAs($directory, $filename, 'public');

        if (! is_string($path) || $path === '') {
            throw new \RuntimeException('Unable to store uploaded file.');
        }

        self::publish($path);

        return $path;
    }

    public static function publish(string $path): void
    {
        $path = ltrim($path, '/');
        $source = Storage::disk('public')->path($path);

        if (! is_file($source)) {
            throw new \RuntimeException('Stored file is missing from disk.');
        }

        if (self::isPublicStorageLinked()) {
            return;
        }

        $destination = public_path('storage/'.$path);
        $destinationDir = dirname($destination);

        if (realpath($source) === @realpath($destination)) {
            return;
        }

        if (! is_dir($destinationDir) && ! mkdir($destinationDir, 0755, true) && ! is_dir($destinationDir)) {
            throw new \RuntimeException('Unable to create public storage directory.');
        }

        if (is_file($destination) && hash_file('sha256', $source) === hash_file('sha256', $destination)) {
            return;
        }

        if (! @copy($source, $destination)) {
            throw new \RuntimeException('Unable to publish file to public storage.');
        }
    }

    public static function delete(?string $path): void
    {
        $path = trim((string) $path);

        if ($path === '' || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return;
        }

        Storage::disk('public')->delete($path);

        if (self::isPublicStorageLinked()) {
            return;
        }

        $publicCopy = public_path('storage/'.ltrim($path, '/'));

        if (is_file($publicCopy)) {
            @unlink($publicCopy);
        }
    }

    public static function ensureStorageLink(): void
    {
        if (self::isPublicStorageLinked()) {
            return;
        }

        try {
            Artisan::call('storage:link');
        } catch (\Throwable) {
            // Shared hosts may block symlinks; publish() copies files instead.
        }

        if (self::isPublicStorageLinked()) {
            return;
        }

        self::publishExistingFilesOnce();
    }

    protected static function publishExistingFilesOnce(): void
    {
        $marker = storage_path('app/.public-storage-published');

        if (is_file($marker)) {
            return;
        }

        $files = Storage::disk('public')->allFiles();

        foreach ($files as $file) {
            try {
                self::publish($file);
            } catch (\Throwable) {
                // Skip missing or unreadable legacy files.
            }
        }

        file_put_contents($marker, now()->toIso8601String());
    }

    protected static function isPublicStorageLinked(): bool
    {
        $link = public_path('storage');

        if (is_link($link)) {
            return true;
        }

        $linkReal = realpath($link);
        $targetReal = realpath(storage_path('app/public'));

        return $linkReal !== false
            && $targetReal !== false
            && $linkReal === $targetReal;
    }
}
