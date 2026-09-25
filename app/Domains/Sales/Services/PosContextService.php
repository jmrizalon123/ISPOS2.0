<?php

namespace App\Domains\Sales\Services;

use App\Models\PosShift;
use App\Models\Register;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class PosContextService
{
    public const SESSION_KEY = 'pos_context';

    /** @return array{store_id: string|null, register_id: string|null, shift_id: string|null} */
    public function get(): array
    {
        $ctx = Session::get(self::SESSION_KEY, []);

        return [
            'store_id' => $ctx['store_id'] ?? null,
            'register_id' => $ctx['register_id'] ?? null,
            'shift_id' => $ctx['shift_id'] ?? null,
        ];
    }

    public function setStoreAndRegister(User $user, string $storeId, string $registerId): void
    {
        $store = Store::query()->findOrFail($storeId);
        $register = Register::query()->with('store')->findOrFail($registerId);

        if ($register->store_id !== $store->id) {
            throw ValidationException::withMessages(['register_id' => 'Register does not belong to the selected store.']);
        }

        if (! $user->canAccessStore($store)) {
            abort(403);
        }

        if ($register->status !== 'active' || $store->status !== 'active') {
            throw ValidationException::withMessages(['register_id' => 'Store or register is not active.']);
        }

        Session::put(self::SESSION_KEY, [
            'store_id' => $store->id,
            'register_id' => $register->id,
            'shift_id' => null,
        ]);
    }

    public function setShift(PosShift $shift): void
    {
        $ctx = $this->get();
        Session::put(self::SESSION_KEY, [
            'store_id' => $ctx['store_id'],
            'register_id' => $ctx['register_id'],
            'shift_id' => $shift->id,
        ]);
    }

    public function clearShift(): void
    {
        $ctx = $this->get();
        Session::put(self::SESSION_KEY, [
            'store_id' => $ctx['store_id'],
            'register_id' => $ctx['register_id'],
            'shift_id' => null,
        ]);
    }

    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    public function isReady(): bool
    {
        $ctx = $this->get();

        return $ctx['store_id'] && $ctx['register_id'] && $ctx['shift_id'];
    }

    public function resolveStore(User $user): ?Store
    {
        $storeId = $this->get()['store_id'];
        if (! $storeId) {
            return null;
        }

        $store = Store::query()->with('priceGroup')->find($storeId);
        if (! $store || ! $user->canAccessStore($store)) {
            return null;
        }

        return $store;
    }

    public function resolveRegister(): ?Register
    {
        $registerId = $this->get()['register_id'];

        return $registerId ? Register::query()->find($registerId) : null;
    }

    public function resolveShift(): ?PosShift
    {
        $shiftId = $this->get()['shift_id'];
        if (! $shiftId) {
            return null;
        }

        return PosShift::query()->find($shiftId);
    }

    /** @return list<array{id: string, store_name: string, store_code: string}> */
    public function accessibleStores(User $user): array
    {
        $query = Store::query()->where('status', 'active')->orderBy('store_name');

        if (! $user->hasGlobalOrganizationAccess()) {
            if ($user->hasRole('Company Admin')) {
                $query->where('company_id', $user->company_id);
            } else {
                $query->whereIn('id', $user->stores()->pluck('stores.id'));
            }
        }

        return $query->get(['id', 'store_name', 'store_code'])->map(fn ($s) => [
            'id' => $s->id,
            'store_name' => $s->store_name,
            'store_code' => $s->store_code,
        ])->all();
    }

    /** @return list<array{id: string, name: string, code: string}> */
    public function registersForStore(string $storeId): array
    {
        return Register::query()
            ->where('store_id', $storeId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn ($r) => ['id' => $r->id, 'name' => $r->name, 'code' => $r->code])
            ->all();
    }
}
