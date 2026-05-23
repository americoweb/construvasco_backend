<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Settings\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class AdminClientController extends Controller
{
    /**
     * GET /admin/clients/search?q=
     * Live search for clients (users with customer role) by name or identifier.
     */
    public function search(Request $request): JsonResponse
    {
        $q = trim($request->get('q', ''));

        $query = User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(12);

        if ($q !== '') {
            $like = '%' . $q . '%';
            $query->where(function ($sub) use ($like) {
                $sub->where('name', 'like', $like)
                    ->orWhere('identifier', 'like', $like);
            });
        }

        $clients = $query->get(['id', 'name', 'identifier', 'type', 'profile_photo_path']);

        return response()->json([
            'data' => $clients->map(fn ($u) => [
                'id'         => $u->id,
                'name'       => $u->name,
                'identifier' => $u->identifier,
                'type'       => $u->type,
            ]),
        ]);
    }

    /**
     * POST /admin/clients
     * Create a new customer (client) from the admin panel.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'       => 'required|string|max:255',
            'identifier' => ['required', 'string', 'max:255', Rule::unique('users', 'identifier')],
            // Accept legacy "phone" from admin UI, normalize to whatsapp for DB consistency.
            'type'       => 'required|in:email,phone,whatsapp',
        ]);

        $normalizedType = $data['type'] === 'phone' ? 'whatsapp' : $data['type'];

        $user = User::create([
            'name'       => $data['name'],
            'identifier' => $data['identifier'],
            'type'       => $normalizedType,
            'password'   => Hash::make(str()->random(16)),
            'is_active'  => true,
        ]);

        $this->attachAsCustomer($user);

        return response()->json([
            'data' => [
                'id'         => $user->id,
                'name'       => $user->name,
                'identifier' => $user->identifier,
                // Keep response compatible with existing UI expectations.
                'type'       => $user->type === 'whatsapp' ? 'phone' : $user->type,
            ],
        ], 201);
    }

    private function attachAsCustomer(User $user): void
    {
        $tenant = Tenant::where('slug', 'construvasco')->first();
        $tenantId = $tenant?->id ?? 1;
        $customerRole = Role::firstOrCreate(
            ['name' => 'customer', 'guard_name' => 'api'],
            ['display_name' => 'Cliente', 'description' => 'Cliente', 'is_system' => true]
        );

        if (!$user->tenants()->where('tenants.id', $tenantId)->exists()) {
            $user->tenants()->attach($tenantId, [
                'role_id' => $customerRole->id,
                'current_tenant' => true,
                'status' => 'active',
            ]);
        }

        DB::table('model_has_roles')->updateOrInsert(
            [
                'role_id' => $customerRole->id,
                'model_type' => User::class,
                'model_id' => $user->id,
                'tenant_id' => $tenantId,
            ],
            []
        );
    }
}
