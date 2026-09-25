<?php

namespace App\Domains\Organization\Services;

use App\Domains\Identity\Services\BackofficeContextService;
use App\Models\Register;
use App\Models\Store;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RegisterService
{
    public function __construct(protected AuditLogger $auditLogger) {}

    public function paginate(User $user, ?string $search = null, ?string $storeId = null, ?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        $contextStoreId = app(BackofficeContextService::class)->effectiveStoreId($user);
        $effectiveStoreId = $contextStoreId ?: $storeId;

        return Register::query()
            ->with([
                'company:id,name,company_code,display_name',
                'store:id,store_name,store_code,company_id',
                'device:id,name',
                'currentShift:id,register_id,status,opened_at',
                'currentCashier:id,name',
                'creator:id,name',
                'updater:id,name',
            ])
            ->when($effectiveStoreId, fn ($q) => $q->where('store_id', $effectiveStoreId))
            ->when(! $effectiveStoreId && ! $user->hasGlobalOrganizationAccess(), function ($q) use ($user) {
                if ($user->hasRole('Company Admin') || $user->isHeadOfficeBased()) {
                    $q->whereHas('store', fn ($query) => $query->where('company_id', $user->company_id));
                } else {
                    $q->whereIn('store_id', $user->stores()->pluck('stores.id'));
                }
            })
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($search, fn ($q) => $q->where(function ($query) use ($search) {
                $query->where('register_name', 'like', "%{$search}%")
                    ->orWhere('register_code', 'like', "%{$search}%")
                    ->orWhere('device_serial', 'like', "%{$search}%")
                    ->orWhere('min', 'like', "%{$search}%")
                    ->orWhere('permit_number', 'like', "%{$search}%")
                    ->orWhere('terminal_code', 'like', "%{$search}%")
                    ->orWhere('terminal_name', 'like', "%{$search}%");
            }))
            ->orderBy('register_name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data, ?User $actor = null): Register
    {
        if ($actor) {
            $data['created_by'] = $actor->id;
            $data['updated_by'] = $actor->id;
        }

        if (! isset($data['company_id']) && isset($data['store_id'])) {
            $data['company_id'] = Store::query()->whereKey($data['store_id'])->value('company_id');
        }

        $register = Register::create($data);

        $this->auditLogger->log('create', 'registers', Register::class, $register->id, null, $register->toArray());

        return $register;
    }

    public function update(Register $register, array $data, ?User $actor = null): Register
    {
        if ($actor) {
            $data['updated_by'] = $actor->id;
        }

        if (isset($data['store_id']) && $data['store_id'] !== $register->store_id) {
            $data['company_id'] = Store::query()->whereKey($data['store_id'])->value('company_id');
        }

        $old = $register->toArray();
        $register->update($data);

        $this->auditLogger->log('update', 'registers', Register::class, $register->id, $old, $register->fresh()->toArray());

        return $register->fresh();
    }

    public function delete(Register $register): void
    {
        $old = $register->toArray();
        $register->delete();

        $this->auditLogger->log('delete', 'registers', Register::class, $register->id, $old, null);
    }
}
