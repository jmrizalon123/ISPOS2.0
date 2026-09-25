<?php

namespace App\Domains\Crm\Services;

use App\Domains\Catalog\Services\Concerns\AuditsCatalogChanges;
use App\Domains\Catalog\Services\Concerns\PaginatesCompanyCatalog;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PromotionService
{
    use AuditsCatalogChanges, PaginatesCompanyCatalog;

    public function paginate(User $user, ?string $search = null, ?string $companyId = null, ?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        return $this->paginateCompanyCatalog(
            $user,
            Promotion::class,
            ['promo_code', 'name'],
            $search,
            $companyId,
            $perPage,
            function ($q) use ($status) {
                $this->applyCatalogStatusFilter($q, $status);
                $q->orderByDesc('starts_at')->orderBy('name');
            },
        );
    }

    /** @param  list<string>  $productIds
     * @param  list<string>  $categoryIds
     */
    public function create(array $data, array $productIds, array $categoryIds, ?User $actor = null): Promotion
    {
        return DB::transaction(function () use ($data, $productIds, $categoryIds, $actor) {
            $this->stampActor($data, $actor);
            $promotion = Promotion::create($data);
            $this->syncTargets($promotion, $productIds, $categoryIds);
            $this->logCatalogCreate('promotions', Promotion::class, $promotion);

            return $promotion->fresh(['products', 'categories']);
        });
    }

    /** @param  list<string>  $productIds
     * @param  list<string>  $categoryIds
     */
    public function update(Promotion $promotion, array $data, array $productIds, array $categoryIds, ?User $actor = null): Promotion
    {
        return DB::transaction(function () use ($promotion, $data, $productIds, $categoryIds, $actor) {
            if ($actor) {
                $data['updated_by'] = $actor->id;
            }

            $old = $promotion->toArray();
            $promotion->update($data);
            $this->syncTargets($promotion, $productIds, $categoryIds);
            $this->logCatalogUpdate('promotions', Promotion::class, $promotion, $old);

            return $promotion->fresh(['products', 'categories']);
        });
    }

    public function activate(Promotion $promotion, User $actor): Promotion
    {
        if ($promotion->status === 'cancelled') {
            throw ValidationException::withMessages(['promotion' => 'Cancelled promotions cannot be activated.']);
        }

        $promotion->update([
            'status' => 'active',
            'updated_by' => $actor->id,
        ]);

        return $promotion->fresh();
    }

    public function cancel(Promotion $promotion, User $actor): Promotion
    {
        $promotion->update([
            'status' => 'cancelled',
            'updated_by' => $actor->id,
        ]);

        return $promotion->fresh();
    }

    public function delete(Promotion $promotion): void
    {
        $this->logCatalogDelete('promotions', Promotion::class, $promotion);
        $promotion->delete();
    }

    public function incrementUsage(Promotion $promotion): void
    {
        $promotion->increment('usage_count');
    }

    /** @param  list<string>  $productIds
     * @param  list<string>  $categoryIds
     */
    protected function syncTargets(Promotion $promotion, array $productIds, array $categoryIds): void
    {
        if ($promotion->applies_to === 'products') {
            $promotion->products()->sync($productIds);
            $promotion->categories()->sync([]);
        } elseif ($promotion->applies_to === 'categories') {
            $promotion->categories()->sync($categoryIds);
            $promotion->products()->sync([]);
        } else {
            $promotion->products()->sync([]);
            $promotion->categories()->sync([]);
        }
    }
}
