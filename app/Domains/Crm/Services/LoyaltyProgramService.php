<?php

namespace App\Domains\Crm\Services;

use App\Domains\Catalog\Services\Concerns\AuditsCatalogChanges;
use App\Domains\Catalog\Services\Concerns\PaginatesCompanyCatalog;
use App\Models\LoyaltyProgram;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class LoyaltyProgramService
{
    use AuditsCatalogChanges, PaginatesCompanyCatalog;

    public function paginate(User $user, ?string $search = null, ?string $companyId = null, ?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        return $this->paginateCompanyCatalog(
            $user,
            LoyaltyProgram::class,
            ['program_code', 'name'],
            $search,
            $companyId,
            $perPage,
            function ($q) use ($status) {
                $this->applyCatalogStatusFilter($q, $status);
                $q->orderByDesc('is_default')->orderBy('name');
            },
        );
    }

    public function create(array $data, ?User $actor = null): LoyaltyProgram
    {
        return DB::transaction(function () use ($data, $actor) {
            $this->stampActor($data, $actor);

            if (! empty($data['is_default'])) {
                LoyaltyProgram::query()
                    ->where('company_id', $data['company_id'])
                    ->update(['is_default' => false]);
            }

            $program = LoyaltyProgram::create($data);
            $this->logCatalogCreate('loyalty_programs', LoyaltyProgram::class, $program);

            return $program;
        });
    }

    public function update(LoyaltyProgram $program, array $data, ?User $actor = null): LoyaltyProgram
    {
        return DB::transaction(function () use ($program, $data, $actor) {
            if ($actor) {
                $data['updated_by'] = $actor->id;
            }

            if (! empty($data['is_default'])) {
                LoyaltyProgram::query()
                    ->where('company_id', $program->company_id)
                    ->whereKeyNot($program->id)
                    ->update(['is_default' => false]);
            }

            $old = $program->toArray();
            $program->update($data);
            $this->logCatalogUpdate('loyalty_programs', LoyaltyProgram::class, $program, $old);

            return $program->fresh();
        });
    }

    public function delete(LoyaltyProgram $program): void
    {
        $this->logCatalogDelete('loyalty_programs', LoyaltyProgram::class, $program);
        $program->delete();
    }

    public function defaultForCompany(string $companyId): ?LoyaltyProgram
    {
        return LoyaltyProgram::query()
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->orderByDesc('is_default')
            ->first();
    }
}
