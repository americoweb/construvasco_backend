<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class PermissionController extends Controller
{
    /**
     * Get all permissions grouped by category
     */
    public function index(): JsonResponse
    {
        $permissions = Permission::where('guard_name', 'api')
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->groupBy('category');

        return response()->json([
            'success' => true,
            'permissions' => $permissions,
            'categories' => $permissions->keys()->toArray()
        ]);
    }

    /**
     * Get all roles with their permissions
     */
    public function roles(): JsonResponse
    {
        $roles = Role::where('guard_name', 'api')
            ->with('permissions')
            ->orderBy('name')
            ->get()
            ->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'display_name' => $role->display_name ?? $role->name,
                    'description' => $role->description,
                    'is_system' => $role->is_system ?? false,
                    'permissions' => $role->permissions->pluck('name')->toArray(),
                    'permission_count' => $role->permissions->count()
                ];
            });

        return response()->json([
            'success' => true,
            'roles' => $roles
        ]);
    }

    /**
     * Get permissions for a specific role
     */
    public function rolePermissions(Role $role): JsonResponse
    {
        $role->load('permissions');
        $permissions = $role->permissions->pluck('name')->toArray();
        
        return response()->json([
            'success' => true,
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'display_name' => $role->display_name ?? $role->name,
                'description' => $role->description ?? '',
                'is_system' => $role->is_system ?? false,
                'permission_count' => $role->permissions->count()
            ],
            'permissions' => $permissions
        ]);
    }

    /**
     * Update permissions for a role
     */
    public function updateRolePermissions(Request $request, Role $role): JsonResponse
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'string|exists:permissions,name'
        ]);

        // Check if user has permission to manage roles
        if (!Auth::user()->hasTenantPermission('users.manage_roles')) {
            return response()->json([
                'success' => false,
                'error' => 'Insufficient permissions'
            ], 403);
        }

        // Prevent modification of system roles
        if ($role->is_system) {
            return response()->json([
                'success' => false,
                'error' => 'Cannot modify system roles'
            ], 403);
        }

        try {
            DB::beginTransaction();

            // Sync permissions
            $role->syncPermissions($request->permissions);

            // Log the activity
            activity()
                ->performedOn($role)
                ->withProperties([
                    'old_permissions' => $role->permissions->pluck('name')->toArray(),
                    'new_permissions' => $request->permissions
                ])
                ->log('role_permissions_updated');

            DB::commit();

            // Refresh role to get updated permissions
            $role->refresh();
            $role->load('permissions');
            
            return response()->json([
                'success' => true,
                'message' => 'Role permissions updated successfully',
                'role' => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'display_name' => $role->display_name ?? $role->name,
                    'description' => $role->description ?? '',
                    'is_system' => $role->is_system ?? false,
                    'permissions' => $role->permissions->pluck('name')->toArray(),
                    'permission_count' => $role->permissions->count()
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => 'Failed to update role permissions',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user permissions for the current tenant
     */
    public function userPermissions(): JsonResponse
    {
        $user = Auth::user();
        $currentTenant = $user->getCurrentTenant();
        
        if (!$currentTenant) {
            return response()->json([
                'success' => false,
                'error' => 'No active tenant found'
            ], 404);
        }
        
        $tenantId = $currentTenant->id;
        $tenantUser = $user->tenants()->where('tenant_id', $tenantId)->first();
        
        if (!$tenantUser) {
            return response()->json([
                'success' => false,
                'error' => 'User not found in current tenant'
            ], 404);
        }

        $permissions = $user->getTenantPermissions($tenantId);
        $role = $user->getTenantRole($tenantId);
        $customPermissions = $user->getCustomTenantPermissions($tenantId);

        return response()->json([
            'success' => true,
            'permissions' => $permissions,
            'role' => $role ? [
                'id' => $role->id,
                'name' => $role->name,
                'display_name' => $role->display_name ?? $role->name,
                'description' => $role->description ?? '',
                'is_system' => $role->is_system ?? false
            ] : null,
            'custom_permissions' => $customPermissions,
            'tenant_user' => [
                'role_id' => $tenantUser->role_id,
                'permissions' => $tenantUser->permissions
            ]
        ]);
    }

    /**
     * Update custom permissions for a user in the current tenant
     */
    public function updateUserPermissions(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'granted_permissions' => 'array',
            'granted_permissions.*' => 'string|exists:permissions,name',
            'denied_permissions' => 'array',
            'denied_permissions.*' => 'string|exists:permissions,name'
        ]);

        // Check if user has permission to manage user permissions
        if (!Auth::user()->hasTenantPermission('users.manage_permissions')) {
            return response()->json([
                'success' => false,
                'error' => 'Insufficient permissions'
            ], 403);
        }

        $currentUser = Auth::user();
        $currentTenant = $currentUser->getCurrentTenant();
        
        if (!$currentTenant) {
            return response()->json([
                'success' => false,
                'error' => 'No active tenant found'
            ], 404);
        }
        
        $tenantId = $currentTenant->id;
        
        // Get the target user's tenant relationship
        $targetUser = User::findOrFail($request->user_id);
        $tenantUser = $targetUser->tenants()->where('tenant_id', $tenantId)->first();
        
        if (!$tenantUser) {
            return response()->json([
                'success' => false,
                'error' => 'User not found in current tenant'
            ], 404);
        }

        try {
            DB::beginTransaction();

            // Log the current state before update
            logger()->debug('Current tenant user state before update', [
                'user_id' => $request->user_id,
                'tenant_id' => $tenantId,
                'current_permissions' => $tenantUser->permissions ?? 'null'
            ]);

            // Prepare custom permissions
            $customPermissions = [
                'granted' => $request->granted_permissions ?? [],
                'denied' => $request->denied_permissions ?? []
            ];

            logger()->debug('Updating user permissions', [
                'user_id' => $request->user_id,
                'tenant_id' => $tenantId,
                'custom_permissions' => $customPermissions,
                'json_permissions' => json_encode($customPermissions)
            ]);

            // Update tenant user permissions using updateExistingPivot
            $result = $targetUser->tenants()->updateExistingPivot($tenantId, [
                'permissions' => json_encode($customPermissions)
            ]);

            logger()->debug('Update result', ['result' => $result]);

            // Verify the update was successful by checking the database
            $updatedTenantUser = $targetUser->tenants()->where('tenant_id', $tenantId)->first();
            logger()->debug('Verification - updated tenant user', [
                'permissions' => $updatedTenantUser->permissions ?? 'null',
                'decoded_permissions' => json_decode($updatedTenantUser->permissions ?? 'null', true)
            ]);

            // Also check the raw database record
            $rawRecord = DB::table('tenant_users')
                ->where('user_id', $request->user_id)
                ->where('tenant_id', $tenantId)
                ->first();
            logger()->debug('Raw database record', [
                'permissions' => $rawRecord->permissions ?? 'null',
                'decoded_permissions' => json_decode($rawRecord->permissions ?? 'null', true)
            ]);

            // Log the activity
            activity()
                ->performedOn($targetUser)
                ->withProperties([
                    'tenant_id' => $tenantId,
                    'granted_permissions' => $customPermissions['granted'],
                    'denied_permissions' => $customPermissions['denied']
                ])
                ->log('user_permissions_updated');

            DB::commit();

            // Get updated permissions for response
            $updatedPermissions = $targetUser->getTenantPermissions($tenantId);
            logger()->debug('Updated permissions', ['permissions' => $updatedPermissions]);

            return response()->json([
                'success' => true,
                'message' => 'User permissions updated successfully',
                'user_permissions' => $updatedPermissions
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            logger()->error('Error updating user permissions', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Failed to update user permissions',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get permission matrix data for the frontend
     */
    public function matrix(): JsonResponse
    {
        $permissions = Permission::where('guard_name', 'api')
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->map(function ($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'category' => $permission->category ?? 'other',
                    'description' => $permission->description ?? '',
                    'guard_name' => $permission->guard_name,
                    'created_at' => $permission->created_at?->toISOString(),
                    'updated_at' => $permission->updated_at?->toISOString(),
                ];
            })
            ->groupBy('category')
            ->map(function ($group) {
                return $group->values()->all();
            });

        $roles = Role::where('guard_name', 'api')
            ->with('permissions')
            ->orderBy('name')
            ->get()
            ->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'display_name' => $role->display_name ?? $role->name,
                    'description' => $role->description ?? '',
                    'is_system' => $role->is_system ?? false,
                    'permissions' => $role->permissions->pluck('name')->toArray(),
                    'permission_count' => $role->permissions->count()
                ];
            });

        // Return matrix data directly (frontend expects PermissionMatrix interface)
        return response()->json([
            'permissions' => $permissions,
            'roles' => $roles,
            'categories' => $permissions->keys()->toArray()
        ]);
    }
} 