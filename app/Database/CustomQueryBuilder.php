<?php

namespace App\Database;

use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;

class CustomQueryBuilder extends QueryBuilder
{
    /**
     * Add a basic where clause to the query.
     *
     * @param  \Closure|string|array  $column
     * @param  mixed  $operator
     * @param  mixed  $value
     * @param  string  $boolean
     * @return $this
     */
    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        // Check if the query is filtering by tenant_id
        $isTenantId = false;
        if (is_string($column)) {
            $isTenantId = ($column === 'tenant_id' || str_ends_with($column, '.tenant_id'));
        }

        if ($isTenantId) {
            // Get actual operator and value
            $actualValue = $value;
            $actualOperator = $operator;
            if (func_num_args() === 2) {
                $actualValue = $operator;
                $actualOperator = '=';
            }

            // If user is authenticated and belongs to Tenant ID 1 (or is super-admin)
            if (Auth::check()) {
                $user = Auth::user();
                $isSuperTenant = ($user->role === 'super-admin' || ($user->attributes['tenant_id'] ?? null) == 1);

                if ($isSuperTenant) {
                    $selectedTenantId = session('selected_tenant_id');
                    if ($selectedTenantId && $selectedTenantId > 1) {
                        // Do not ignore the filter. Enforce filtering by the selected tenant.
                    } else {
                        // Ignore the default filter of tenant_id = 1 (or tenant_id = user's tenant_id)
                        // If they want to specifically filter for another tenant (e.g. tenant_id = 2), keep it.
                        if ($actualValue == 1 || $actualValue == $user->tenant_id || $actualValue === null) {
                            return $this;
                        }
                    }
                }
            }
        }

        return parent::where($column, $operator, $value, $boolean);
    }
}
