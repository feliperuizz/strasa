<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Global Scope multi-tenant.
 *
 * Sempre que houver um usuário autenticado, todas as queries dos models que
 * usam a trait BelongsToCompany são automaticamente filtradas pelo
 * company_id desse usuário. Isso garante o isolamento de dados entre
 * empresas/agências sem precisar repetir o where em cada query.
 */
class CompanyScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (Auth::hasUser() && ($companyId = Auth::user()->company_id)) {
            $builder->where($model->getTable().'.company_id', $companyId);
        }
    }
}
