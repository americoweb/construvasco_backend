<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TenantResource;
use App\Http\Resources\UserResource;
use App\Http\Requests\CreateTenantRequest;
use App\Http\Requests\AddUserToTenantRequest;
use App\Models\Settings\Tenant;
use App\Models\User;
use App\Services\TenantService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;

class TenantController extends Controller
{
    public function __construct(
        private TenantService $tenantService,
        private NotificationService $notificationService
    ) {
        // $this->middleware('auth:api');
        $this->middleware('tenant')->except(['index', 'store', 'switch']);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        
        $tenants = $user->activeTenants()
            ->withPivot(['role_id', 'current_tenant', 'status', 'joined_at'])
            ->when($request->get('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->orderBy('tenants.name')
            ->get()
            ->map(function ($tenant) {
                $tenant->user_role = $tenant->pivot->role_id;
                $tenant->is_current = $tenant->pivot->current_tenant;
                $tenant->user_status = $tenant->pivot->status;
                $tenant->joined_at = $tenant->pivot->joined_at;
                return $tenant;
            });

        return TenantResource::collection($tenants);
    }

    public function store(CreateTenantRequest $request): JsonResponse
    {
        $user = $request->user();

        try {
            $tenant = $this->tenantService->createTenant($request->validated(), $user);

            return response()->json([
                'data' => new TenantResource($tenant),
                'message' => 'Tenant created successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create tenant',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenant = $user->getCurrentTenant();
        
        if (!$tenant) {
            return response()->json([
                'success' => false,
                'error' => 'No current tenant set'
            ], 404);
        }

        if (!$user->hasTenantPermission('tenants.view')) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }

        $tenantData = new TenantResource($tenant);
        $tenantData->additional([
            'user_context' => $user->getTenantContext(),
            'stats' => $this->tenantService->getTenantStats($tenant)
        ]);

        return response()->json(['data' => $tenantData]);
    }

    public function switch(Request $request): JsonResponse
    {
        $request->validate(['tenant_id' => 'required|integer|exists:tenants,id']);

        $user = $request->user();
        $tenantId = $request->get('tenant_id');

        try {
            $success = $this->tenantService->switchUserTenant($user, $tenantId);
            
            if (!$success) {
                return response()->json(['error' => 'Unable to switch to the requested tenant'], 403);
            }

            $newTenant = $user->getCurrentTenant();

            return response()->json([
                'data' => new TenantResource($newTenant),
                'user_context' => $user->getTenantContext(),
                'message' => "Switched to tenant: {$newTenant->name}"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to switch tenant',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function users(Request $request)
    {
        $user = $request->user();
        $tenant = $user->getCurrentTenant();
        
        if (!$tenant) {
            return response()->json([
                'success' => false,
                'error' => 'No current tenant set'
            ], 404);
        }

        if (!$user->hasTenantPermission('users.view')) {
            return response()->json([
                'success' => false,
                'error' => 'Insufficient permissions'
            ], 403);
        }

        $users = $this->tenantService->getTenantUsers($tenant, $request->get('status', 'active'));

        return response()->json([
            'success' => true,
            'data' => UserResource::collection($users)->resolve()
        ]);
    }

    public function addUser(Request $request): JsonResponse
    {
        $currentUser = $request->user();
        $tenant = $currentUser->getCurrentTenant();
        
        if (!$tenant) {
            return response()->json([
                'success' => false,
                'error' => 'No current tenant set'
            ], 404);
        }

        $request->validate([
            'identifier' => 'required|string',
            'type' => 'sometimes|in:email,whatsapp',
            'role' => 'required|string',
        ]);

        $identifier = $request->get('identifier');
        $type = $request->get('type') ?? $this->detectIdentifierType($identifier);
        $role = $request->get('role');

        // Try to find the user by identifier
        $targetUser = \App\Models\User::where('identifier', $identifier)->first();

        if ($targetUser) {
            // User exists, add to tenant
            try {
                app(\App\Services\TenantService::class)->addUserToTenant(
                    $tenant,
                    $targetUser,
                    $role,
                    false,
                    []
                );

                return response()->json([
                    'success' => true,
                    'data' => new \App\Http\Resources\UserResource($targetUser),
                    'message' => 'User added to tenant successfully'
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to add user to tenant',
                    'message' => $e->getMessage()
                ], 422);
            }
        } else {
            // User does not exist, create invitation
            $token = bin2hex(random_bytes(32));
            $invitation = \App\Models\TenantInvitation::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'identifier' => $identifier
                ],
                [
                    'type' => $type,
                    'role' => $role,
                    'inviter_id' => $currentUser->id,
                    'token' => $token,
                    'status' => 'pending',
                    'accepted_at' => null
                ]
            );

            // Send invitation notification based on type
            $this->notificationService->sendInvitation($invitation, $tenant, $currentUser);

            // Format invitation data for response
            $invitationData = [
                'id' => (string) $invitation->id,
                'tenant_id' => (string) $invitation->tenant_id,
                'identifier' => $invitation->identifier,
                'type' => $invitation->type,
                'role' => $invitation->role,
                'inviter_id' => (string) $invitation->inviter_id,
                'token' => $invitation->token,
                'status' => $invitation->status,
                'accepted_at' => $invitation->accepted_at?->toISOString(),
                'created_at' => $invitation->created_at->toISOString(),
                'updated_at' => $invitation->updated_at->toISOString(),
            ];
            
            return response()->json([
                'success' => true,
                'data' => $invitationData,
                'message' => 'Invitation created for user'
            ], 201);
        }
    }

    public function invitations(Request $request)
    {
        $user = $request->user();
        $tenant = $user->getCurrentTenant();
        if (!$tenant) {
            return response()->json([
                'success' => false,
                'error' => 'No current tenant set'
            ], 404);
        }
        $invitations = \App\Models\TenantInvitation::where('tenant_id', $tenant->id)
            ->where('status', 'pending')
            ->get()
            ->map(function($invitation) {
                return [
                    'id' => (string) $invitation->id,
                    'tenant_id' => (string) $invitation->tenant_id,
                    'identifier' => $invitation->identifier,
                    'type' => $invitation->type,
                    'role' => $invitation->role,
                    'inviter_id' => (string) $invitation->inviter_id,
                    'token' => $invitation->token,
                    'status' => $invitation->status,
                    'accepted_at' => $invitation->accepted_at?->toISOString(),
                    'created_at' => $invitation->created_at->toISOString(),
                    'updated_at' => $invitation->updated_at->toISOString(),
                ];
            });
        return response()->json([
            'success' => true,
            'data' => $invitations
        ]);
    }

    public function cancelInvitation(Request $request, $invitationId)
    {
        $user = $request->user();
        $tenant = $user->getCurrentTenant();
        $invitation = \App\Models\TenantInvitation::where('id', $invitationId)
            ->where('tenant_id', $tenant->id)
            ->where('status', 'pending')
            ->first();
        if (!$invitation) {
            return response()->json([
                'success' => false,
                'error' => 'Invitation not found'
            ], 404);
        }
        $invitation->status = 'cancelled';
        $invitation->save();
        return response()->json([
            'success' => true,
            'message' => 'Invitation cancelled'
        ]);
    }

    /**
     * Detect if the identifier is an email or WhatsApp number
     */
    private function detectIdentifierType(string $identifier): string
    {
        // Email regex pattern
        $emailPattern = '/^[^\s@]+@[^\s@]+\.[^\s@]+$/';
        
        // WhatsApp regex pattern (supports international format and common formats)
        $whatsappPattern = '/^[\+]?[1-9][\d\s\-\(\)]{7,15}$/';
        
        // Clean the identifier (remove spaces, dashes, parentheses) for validation
        $cleanIdentifier = preg_replace('/[\s\-\(\)]/', '', $identifier);
        
        if (preg_match($emailPattern, $identifier)) {
            return 'email';
        } elseif (preg_match($whatsappPattern, $identifier) && strlen($cleanIdentifier) >= 8 && strlen($cleanIdentifier) <= 15) {
            return 'whatsapp';
        } else {
            // Default to email if we can't determine
            return 'email';
        }
    }
}