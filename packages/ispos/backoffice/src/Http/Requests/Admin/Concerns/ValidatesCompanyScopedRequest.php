<?php

namespace Ispos\Backoffice\Http\Requests\Admin\Concerns;

use Illuminate\Foundation\Http\FormRequest;

trait ValidatesCompanyScopedRequest
{
    protected function mergeCompanyId(): void
    {
        if (! $this->user()?->hasGlobalOrganizationAccess()) {
            $this->merge(['company_id' => $this->user()->company_id]);
        }
    }
}
