<?php

namespace Tests\Support;

use App\Domains\Identity\Services\BackofficeContextService;
use App\Models\User;

trait SetsBackofficeContext
{
    protected function setBackofficeCompanyContext(User $user): void
    {
        app(BackofficeContextService::class)->setCompanyScope($user);
    }

    protected function setBackofficeStoreContext(User $user, string $storeId): void
    {
        app(BackofficeContextService::class)->setStoreScope($user, $storeId);
    }
}
