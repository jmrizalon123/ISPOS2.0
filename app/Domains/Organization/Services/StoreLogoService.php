<?php

namespace App\Domains\Organization\Services;

use App\Models\Store;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StoreLogoService
{
    public const MAX_KILOBYTES = 2048;

    protected const DIRECTORY = 'stores/logos';

    public function store(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'png');

        return $file->storeAs(self::DIRECTORY, 'logo-'.Str::uuid()->toString().'.'.$extension, 'public');
    }

    public function replace(Store $store, UploadedFile $file): string
    {
        $this->deleteStored($store->logo);

        return $this->store($file);
    }

    public function forget(Store $store): void
    {
        $this->deleteStored($store->logo);
    }

    /** Externally hosted logos are referenced, not owned, so they are never deleted. */
    protected function deleteStored(?string $logo): void
    {
        $logo = trim((string) $logo);

        if ($logo === '' || Str::startsWith($logo, ['http://', 'https://', '/'])) {
            return;
        }

        Storage::disk('public')->delete($logo);
    }
}
