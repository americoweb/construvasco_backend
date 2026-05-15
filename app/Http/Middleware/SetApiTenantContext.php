<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

class SetApiTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('api');

        if ($user) {
            $tenantId = session('tenant_id')
                ?? cache()->get('tenant_id_' . $user->id);

            if (!$tenantId) {
                $tenantId = DB::table('tenant_users')
                    ->where('user_id', $user->id)
                    ->where('status', 'active')
                    ->orderByDesc('current_tenant')
                    ->value('tenant_id');
            }

            if ($tenantId) {
                session(['tenant_id' => (int) $tenantId]);
                cache()->put('tenant_id_' . $user->id, (int) $tenantId, now()->addDay());
                app(PermissionRegistrar::class)->setPermissionsTeamId((int) $tenantId);
            }
        }

        return $next($request);
    }
}
