<?php

namespace Ispos\Backoffice\Http\Controllers\Admin\Concerns;

use App\Models\Store;
use Illuminate\Http\Request;

trait ResolvesCatalogIndexFilters
{
    /** @return array{companyId: ?string, storeId: ?string, status: ?string, filters: array<string, string>} */
    protected function resolveCatalogIndexFilters(Request $request): array
    {
        $companyId = $request->string('company_id')->toString() ?: null;
        $storeId = $request->string('store_id')->toString() ?: null;
        $status = $request->string('status')->toString() ?: null;

        if ($storeId) {
            $store = Store::query()->find($storeId);
            if ($store) {
                $this->authorize('view', $store);
                $companyId = $store->company_id;
            }
        }

        return [
            'companyId' => $companyId,
            'storeId' => $storeId,
            'status' => $status,
            'filters' => [
                'search' => $request->string('search')->toString(),
                'company_id' => $request->string('company_id')->toString(),
                'store_id' => $storeId ?? '',
                'status' => $status ?? '',
            ],
        ];
    }
}
