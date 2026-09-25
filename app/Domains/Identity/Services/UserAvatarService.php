<?php

namespace App\Domains\Identity\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserAvatarService
{
    public const MAX_KILOBYTES = 2048;

    protected const DIRECTORY = 'avatars';

    public function store(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'png');

        return $file->storeAs(self::DIRECTORY, 'avatar-'.Str::uuid()->toString().'.'.$extension, 'public');
    }

    public function replace(User $user, UploadedFile $file): string
    {
        $this->deleteStored($user->avatar);

        return $this->store($file);
    }

    public function forget(User $user): void
    {
        $this->deleteStored($user->avatar);
    }

    /** Externally hosted avatars are referenced, not owned, so they are never deleted. */
    protected function deleteStored(?string $avatar): void
    {
        $avatar = trim((string) $avatar);

        if ($avatar === '' || Str::startsWith($avatar, ['http://', 'https://', '/'])) {
            return;
        }

        Storage::disk('public')->delete($avatar);
    }
}
