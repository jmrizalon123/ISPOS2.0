<?php

namespace App\Domains\Catalog\Services\Concerns;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;

trait AuditsCatalogChanges
{
    protected function auditLogger(): AuditLogger
    {
        return app(AuditLogger::class);
    }

    /** @param  class-string<Model>  $modelClass */
    protected function stampActor(array &$data, ?User $actor): void
    {
        if ($actor) {
            $data['created_by'] = $data['created_by'] ?? $actor->id;
            $data['updated_by'] = $actor->id;
        }
    }

    /** @param  class-string<Model>  $modelClass */
    protected function logCatalogCreate(string $module, string $modelClass, Model $model): void
    {
        $this->auditLogger()->log('create', $module, $modelClass, $model->id, null, $model->toArray());
    }

    /** @param  class-string<Model>  $modelClass */
    protected function logCatalogUpdate(string $module, string $modelClass, Model $model, array $old): void
    {
        $this->auditLogger()->log('update', $module, $modelClass, $model->id, $old, $model->fresh()->toArray());
    }

    /** @param  class-string<Model>  $modelClass */
    protected function logCatalogDelete(string $module, string $modelClass, Model $model): void
    {
        $this->auditLogger()->log('delete', $module, $modelClass, $model->id, $model->toArray(), null);
    }
}
