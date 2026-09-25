<?php

namespace Ispos\Backoffice\Http\Controllers\Admin\Concerns;

use App\Models\Company;
use App\Models\Store;
use Illuminate\Http\Request;

trait ProvidesCompanyOptions
{
    protected function companyOptions(Request $request)
    {
        return $request->user()->hasGlobalOrganizationAccess()
            ? Company::query()->orderBy('name')->get(['id', 'name', 'company_code', 'display_name'])
            : Company::query()->whereKey($request->user()->company_id)->get(['id', 'name', 'company_code', 'display_name']);
    }

    protected function resolveCompanyId(Request $request): ?string
    {
        if ($request->user()->hasGlobalOrganizationAccess()) {
            return $request->input('company_id') ?: $request->user()->company_id;
        }

        return $request->user()->company_id;
    }

    protected function storeOptions(Request $request)
    {
        $companyId = $request->string('company_id')->toString() ?: null;

        return Store::query()
            ->when(! $request->user()->hasGlobalOrganizationAccess(), fn ($q) => $q->where('company_id', $request->user()->company_id))
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->orderBy('store_name')
            ->get(['id', 'store_name', 'store_code', 'company_id']);
    }
}
