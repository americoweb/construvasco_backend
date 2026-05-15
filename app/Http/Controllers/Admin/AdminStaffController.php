<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\CreateStaffRequest;
use App\Http\Requests\Staff\UpdateStaffRequest;
use App\Http\Resources\Staff\StaffResource;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AdminStaffController extends Controller
{
    /**
     * GET /admin/staff
     * Paginated list — optionally filtered by role or search string.
     */
    public function index(Request $request): JsonResponse
    {
        $this->resolveTenantId($request);

        $query = User::with('roles')
            ->when($request->filled('role'), function ($q) use ($request) {
                $q->role($request->role, 'api');
            }, function ($q) {
                // Exclude pure customers by default
                $q->whereHas('roles', fn ($r) => $r->where('guard_name', 'api')->where('name', '!=', 'customer'));
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = '%' . $request->search . '%';
                $q->where(fn ($q2) => $q2->where('name', 'like', $s)->orWhere('identifier', 'like', $s));
            })
            ->when($request->filled('is_active'), function ($q) use ($request) {
                $q->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
            })
            ->orderBy('name');

        $perPage = (int) $request->get('per_page', 15);
        $staff   = $query->paginate($perPage);

        return response()->json([
            'data' => StaffResource::collection($staff->items()),
            'meta' => [
                'current_page' => $staff->currentPage(),
                'per_page'     => $staff->perPage(),
                'total'        => $staff->total(),
                'last_page'    => $staff->lastPage(),
            ],
        ]);
    }

    /**
     * GET /admin/staff/designers
     * Flat list of active designers — for select/autocomplete dropdowns.
     */
    public function designers(Request $request): JsonResponse
    {
        $this->resolveTenantId($request);

        $role = Role::query()
            ->where('name', 'designer')
            ->where('guard_name', 'api')
            ->first();

        if ($role === null) {
            return response()->json(['data' => []]);
        }

        $designers = User::role('designer', 'api')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'identifier', 'profile_photo_path']);

        return response()->json(['data' => $designers]);
    }

    /**
     * POST /admin/staff
     */
    public function store(CreateStaffRequest $request): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);

        $user = DB::transaction(function () use ($request, $tenantId) {
            $user = User::create([
                'name'       => $request->name,
                'identifier' => $request->identifier,
                'type'       => 'email',
                'password'   => Hash::make($request->password),
                'is_active'  => true,
            ]);

            $role = Role::findByName($request->role, 'api');
            $this->assignRoleForTenant($user, $role->id, $tenantId, false);

            return $user;
        });

        $user->load('roles');
        return response()->json(['data' => new StaffResource($user)], 201);
    }

    /**
     * GET /admin/staff/{id}
     */
    public function show(int $id): JsonResponse
    {
        $tenantId = (int) session('tenant_id', 0);

        $user = User::with('roles')->findOrFail($id);

        if ($tenantId > 0 && !$user->tenants()->where('tenants.id', $tenantId)->exists()) {
            abort(404, 'Staff member not found for current tenant.');
        }

        return response()->json(['data' => new StaffResource($user)]);
    }

    /**
     * PUT /admin/staff/{id}
     */
    public function update(UpdateStaffRequest $request, int $id): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);

        $user = DB::transaction(function () use ($request, $id, $tenantId) {
            $user = User::findOrFail($id);

            $user->fill(array_filter([
                'name'      => $request->name,
                'is_active' => $request->has('is_active') ? $request->boolean('is_active') : null,
            ], fn ($v) => $v !== null));

            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }

            $user->save();

            if ($request->filled('role')) {
                $role = Role::findByName($request->role, 'api');
                $this->assignRoleForTenant($user, $role->id, $tenantId, true);
            }

            return $user;
        });

        $user->load('roles');
        return response()->json(['data' => new StaffResource($user)]);
    }

    private function resolveTenantId(Request $request): int
    {
        $tenantId = (int) session('tenant_id', 0);

        if ($tenantId <= 0 && $request->user()) {
            $tenantId = (int) optional($request->user()->getCurrentTenant())->id;
        }

        if ($tenantId <= 0) {
            $tenantId = 1;
        }

        session(['tenant_id' => $tenantId]);
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenantId);

        return $tenantId;
    }

    private function assignRoleForTenant(User $user, int $roleId, int $tenantId, bool $sync): void
    {
        if (!$user->tenants()->where('tenants.id', $tenantId)->exists()) {
            $user->tenants()->attach($tenantId, [
                'role_id' => $roleId,
                'current_tenant' => true,
                'status' => 'active',
            ]);
        } else {
            $user->tenants()->updateExistingPivot($tenantId, ['role_id' => $roleId]);
        }

        if ($sync) {
            DB::table('model_has_roles')
                ->where('model_id', $user->id)
                ->where('model_type', User::class)
                ->where('tenant_id', $tenantId)
                ->delete();
        }

        DB::table('model_has_roles')->updateOrInsert(
            [
                'role_id' => $roleId,
                'model_type' => User::class,
                'model_id' => $user->id,
                'tenant_id' => $tenantId,
            ],
            []
        );
    }

    /**
     * DELETE /admin/staff/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'Staff member removed.'], 200);
    }
}
