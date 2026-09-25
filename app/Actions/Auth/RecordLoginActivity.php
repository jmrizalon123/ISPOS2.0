<?php

namespace App\Actions\Auth;

use App\Models\LoginActivity;
use App\Models\User;
use App\Services\AuditLogger;

class RecordLoginActivity
{
    public function __construct(protected AuditLogger $auditLogger) {}

    public function success(User $user, string $ip, ?string $userAgent): void
    {
        $user->forceFill(['last_login_at' => now()])->save();

        LoginActivity::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'successful' => true,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'created_at' => now(),
        ]);

        $this->auditLogger->log('login', 'auth', User::class, $user->id, null, [
            'email' => $user->email,
            'ip_address' => $ip,
        ]);
    }

    public function failure(?string $userId, string $email, string $ip, ?string $userAgent): void
    {
        LoginActivity::create([
            'user_id' => $userId,
            'email' => $email,
            'successful' => false,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'created_at' => now(),
        ]);
    }
}
