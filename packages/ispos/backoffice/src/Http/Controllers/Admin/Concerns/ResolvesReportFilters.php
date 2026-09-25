<?php

namespace Ispos\Backoffice\Http\Controllers\Admin\Concerns;

use Illuminate\Http\Request;

trait ResolvesReportFilters
{
    /** @return array{filters: array<string, string>, companyId: string|null} */
    protected function resolveReportFilters(Request $request): array
    {
        $user = $request->user();
        $companyId = $user->hasGlobalOrganizationAccess()
            ? ($request->string('company_id')->toString() ?: null)
            : $user->company_id;

        $dateFrom = $request->string('date_from')->toString()
            ?: now()->subDays(29)->toDateString();
        $dateTo = $request->string('date_to')->toString()
            ?: now()->toDateString();

        return [
            'companyId' => $companyId,
            'filters' => [
                'company_id' => $companyId ?? '',
                'store_id' => $request->string('store_id')->toString(),
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'status' => $request->string('status')->toString() ?: 'completed',
            ],
        ];
    }
}
