<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;

/**
 * Initializes tenant context from X-Tenant header or ?tenant= query param
 * if present. Passes through silently if no tenant identifier is found.
 * Used on central routes that must work for both admin and tenant users.
 */
class InitializeTenancyOptionally
{
    public function handle(Request $request, Closure $next)
    {
        $tenantId = $request->header('X-Tenant') ?? $request->query('tenant');

        if ($tenantId && !tenancy()->initialized) {
            $tenant = Tenant::find($tenantId);
            if ($tenant) {
                tenancy()->initialize($tenant);
            }
        }

        return $next($request);
    }
}
