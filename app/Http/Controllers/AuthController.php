<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use App\Models\Settings\Tenant;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Resources\UserResource;
use App\Http\Resources\TenantResource;
use App\Services\ActivityLogService;

class AuthController extends Controller
{
    public function __construct(private ActivityLogService $activityLogService)
    {
    }

    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'identifier' => 'required|string|max:255|unique:users,identifier',
            'type' => 'required|string|in:email,whatsapp',
            'password' => 'required|string|min:8|confirmed',
            'invitation_token' => 'nullable|string',
            'company_name' => 'nullable|string|max:255', // For SMS invitations
        ]);

        // Validate identifier format based on type
        $identifier = $request->get('identifier');
        $type = $request->get('type');
        
        if ($type === 'email' && !filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'error' => 'Invalid email format',
                'message' => 'Please enter a valid email address'
            ], 422);
        }
        
        if ($type === 'whatsapp') {
            // WhatsApp regex pattern (supports international format and common formats)
            $whatsappPattern = '/^[\+]?[1-9][\d\s\-\(\)]{7,15}$/';
            $cleanIdentifier = preg_replace('/[\s\-\(\)]/', '', $identifier);
            
            if (!preg_match($whatsappPattern, $identifier) || strlen($cleanIdentifier) < 8 || strlen($cleanIdentifier) > 15) {
                return response()->json([
                    'error' => 'Invalid WhatsApp format',
                    'message' => 'Please enter a valid WhatsApp number (with country code)'
                ], 422);
            }
        }

        try {
            // Log the incoming request
            Log::info('Registration request received', [
                'name' => $request->get('name'),
                'identifier' => $request->get('identifier'),
                'has_invitation_token' => $request->has('invitation_token'),
                'invitation_token' => $request->get('invitation_token'),
                'has_company_name' => $request->has('company_name'),
                'company_name' => $request->get('company_name'),
                'has_organization_name' => $request->has('organization_name'),
                'organization_name' => $request->get('organization_name'),
            ]);

            $user = User::create([
                'name' => $request->get('name'),
                'identifier' => $request->get('identifier'),
                'type' => $request->get('type'),
                'password' => Hash::make($request->get('password')),
                'is_active' => true,
            ]);

            $tenant = null;
            $invitationAccepted = false;

            // Check for pending invitation by token (for email invitations)
            if ($request->has('invitation_token')) {
                Log::info('Processing invitation token', ['token' => $request->get('invitation_token')]);
                
                // First, let's see all invitations with this token
                $allInvitations = \App\Models\TenantInvitation::where('token', $request->get('invitation_token'))->get();
                Log::info('All invitations with this token', [
                    'count' => $allInvitations->count(),
                    'invitations' => $allInvitations->toArray()
                ]);
                
                $invitation = \App\Models\TenantInvitation::where('token', $request->get('invitation_token'))
                    ->where('status', 'pending')
                    ->first();

                if ($invitation) {
                    Log::info('Invitation found', [
                        'invitation_id' => $invitation->id,
                        'tenant_id' => $invitation->tenant_id,
                        'identifier' => $invitation->identifier,
                        'role' => $invitation->role
                    ]);
                    
                    $tenant = \App\Models\Settings\Tenant::find($invitation->tenant_id);
                    if ($tenant) {
                        Log::info('Tenant found', ['tenant_id' => $tenant->id, 'tenant_name' => $tenant->name]);
                        $this->acceptInvitation($invitation, $tenant, $user);
                        $invitationAccepted = true;
                    } else {
                        Log::error('Tenant not found for invitation', ['tenant_id' => $invitation->tenant_id]);
                    }
                } else {
                    Log::error('Invitation not found or not pending', ['token' => $request->get('invitation_token')]);
                    
                    // Let's also check if there are any invitations with this token but different status
                    $otherStatusInvitations = \App\Models\TenantInvitation::where('token', $request->get('invitation_token'))
                        ->where('status', '!=', 'pending')
                        ->get();
                    Log::info('Invitations with this token but different status', [
                        'count' => $otherStatusInvitations->count(),
                        'invitations' => $otherStatusInvitations->toArray()
                    ]);
                }
            } 
            // Check for pending invitation by company name (for SMS invitations)
            elseif ($request->has('company_name')) {
                $companyName = $request->get('company_name');
                
                // Find tenant by name
                $tenant = \App\Models\Settings\Tenant::where('name', $companyName)
                    ->where('is_active', true)
                    ->first();
                
                if ($tenant) {
                    // Find pending invitation for this email and tenant
                    $invitation = \App\Models\TenantInvitation::where('identifier', $request->get('identifier'))
                        ->where('type', 'email')
                        ->where('tenant_id', $tenant->id)
                        ->where('status', 'pending')
                        ->first();
                    
                    if ($invitation) {
                        $this->acceptInvitation($invitation, $tenant, $user);
                        $invitationAccepted = true;
                    }
                }
            }
            // Check for pending invitation by email (fallback)
            else {
                $invitation = \App\Models\TenantInvitation::where('identifier', $request->get('identifier'))
                    ->where('type', 'email')
                    ->where('status', 'pending')
                    ->first();

                if ($invitation) {
                    $tenant = \App\Models\Settings\Tenant::find($invitation->tenant_id);
                    if ($tenant) {
                        $this->acceptInvitation($invitation, $tenant, $user);
                        $invitationAccepted = true;
                    }
                }
            }

            Log::info('Invitation processing result', ['invitation_accepted' => $invitationAccepted]);

            // If no invitation or invitation not found, add user to default tenant (ID: 1)
            if (!$invitationAccepted) {
                // Get or ensure default tenant exists
                $tenant = \App\Models\Settings\Tenant::find(1);
                
                if (!$tenant) {
                    // Create default tenant if it doesn't exist
                    $tenant = app(\App\Services\TenantService::class)->createTenant([
                        'name' => 'Default Organization',
                        'is_active' => true,
                    ], $user);
                } else {
                    // Add user to existing default tenant
                    try {
                        app(\App\Services\TenantService::class)->addUserToTenant(
                            $tenant,
                            $user,
                            'team_member', // Default role
                            true, // Set as current tenant
                            []
                        );
                    } catch (\Exception $e) {
                        // User might already belong to tenant, check and handle
                        if (strpos($e->getMessage(), 'already belongs') === false) {
                            throw $e;
                        }
                        // User already belongs, just set as current
                        $user->switchTenant($tenant->id);
                    }
                }
            }

            $token = JWTAuth::fromUser($user);

            // Log the registration without complex properties
            try {
                activity('user_actions')
                    ->by($user)
                    ->log('user_registered');
            } catch (\Exception $e) {
                Log::error('Failed to log user registration', ['error' => $e->getMessage()]);
            }

            return response()->json([
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => JWTAuth::factory()->getTTL() * 60,
                'user' => new UserResource($user),
                'current_tenant' => new TenantResource($tenant),
                'message' => $invitationAccepted ? 'Registration successful - Invitation accepted' : 'Registration successful'
            ], 201);

        } catch (\Exception $e) {
            Log::error('Registration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Registration failed',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Accept an invitation and add user to tenant
     */
    private function acceptInvitation($invitation, $tenant, $user): void
    {
        app(\App\Services\TenantService::class)->addUserToTenant(
            $tenant,
            $user,
            $invitation->role,
            true, // Set as current tenant
            []
        );

        // Mark invitation as accepted
        $invitation->update([
            'status' => 'accepted',
            'accepted_at' => now()
        ]);

        // Ensure session is set
        session(['tenant_id' => $tenant->id]);
        
        // Log the invitation acceptance
        Log::info('Invitation accepted during registration', [
            'user_id' => $user->id,
            'user_email' => $user->identifier,
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
            'invitation_id' => $invitation->id,
            'role' => $invitation->role
        ]);
        
        // Refresh user to ensure tenant relationship is loaded
        $user->refresh();
        
        // Force set current tenant
        $user->switchTenant($tenant->id);
        
        // Verify the user is properly assigned to the tenant
        if (!$user->belongsToTenant($tenant->id)) {
            Log::error('User not properly assigned to tenant after invitation acceptance', [
                'user_id' => $user->id,
                'tenant_id' => $tenant->id
            ]);
            throw new \Exception('Failed to assign user to tenant');
        }
        
        Log::info('User successfully assigned to tenant', [
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'current_tenant_id' => session('tenant_id')
        ]);
    }

    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'identifier' => 'nullable|string',
            'email' => 'nullable|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'message_pt' => 'Validação falhou.',
                'error_code' => 'VALIDATION_ERROR',
                'errors' => $validator->errors(),
            ], 422);
        }

        $credentials = $validator->validated();

        $raw = $credentials['identifier'] ?? $request->input('email', '');
        $identifier = strtolower(trim((string) $raw));

        if ($identifier === '') {
            return response()->json([
                'success' => false,
                'message' => 'Email or identifier is required.',
                'message_pt' => 'Indique o email ou identificador.',
                'error_code' => 'VALIDATION_ERROR',
            ], 422);
        }

        // Find user by identifier (case-insensitive; allow legacy clients that send "email")
        $user = User::whereRaw('LOWER(identifier) = ?', [$identifier])->first();
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password.',
                'message_pt' => 'Email ou palavra-passe invalidos.',
                'error_code' => 'INVALID_IDENTIFIER',
            ], 422);
        }

        // Check if user is active
        if (isset($user->is_active) && !$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'User account is inactive.',
                'message_pt' => 'A conta do utilizador está inativa.',
                'error_code' => 'USER_INACTIVE',
            ], 403);
        }

        // Check password
        if (! Hash::check((string) $request->get('password'), $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password.',
                'message_pt' => 'Email ou palavra-passe invalidos.',
                'error_code' => 'INVALID_PASSWORD',
            ], 422);
        }

        // Attempt login and get token
        if (! $token = auth('api')->login($user)) {
            return response()->json(['error' => 'Could not log in.'], 500);
        }

        // Update last login timestamp
        $user->update(['last_login_at' => now()]);

        if ($user->must_change) {
            return response()->json([
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
                'must_change' => true
            ]);
        }

        return $this->respondWithToken($token);
    }

    public function me(): JsonResponse
    {
        $user = auth('api')->user();
        
        return response()->json([
            'user' => new UserResource($user),
            'current_tenant' => $user->getCurrentTenant() ? 
                new TenantResource($user->getCurrentTenant()) : null,
            'tenant_context' => $user->getTenantContext(),
        ]);
    }

    public function logout(): JsonResponse
    {
        $user = auth('api')->user();
        
        $this->activityLogService->logUserAction('user_logged_out', $user);
        
        JWTAuth::invalidate(JWTAuth::getToken());

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function refresh(): JsonResponse
    {
        return response()->json([
            'access_token' => JWTAuth::refresh(JWTAuth::getToken()),
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60
        ]);
    }

    /**
     * Validate invitation token and return invitation details
     */
    public function validateInvitation(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string'
        ]);

        $invitation = \App\Models\TenantInvitation::where('token', $request->get('token'))
            ->where('status', 'pending')
            ->first();

        if (!$invitation) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid or expired invitation token'
            ], 404);
        }

        $tenant = \App\Models\Settings\Tenant::find($invitation->tenant_id);

        return response()->json([
            'valid' => true,
            'invitation' => [
                'id' => $invitation->id,
                'identifier' => $invitation->identifier,
                'type' => $invitation->type,
                'role' => $invitation->role,
                'tenant' => $tenant ? [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'slug' => $tenant->slug
                ] : null
            ]
        ]);
    }

    /**
     * Validate company invitation by company name
     */
    public function validateCompanyInvitation(Request $request): JsonResponse
    {
        $request->validate([
            'company_name' => 'required|string'
        ]);

        $tenant = \App\Models\Settings\Tenant::where('name', $request->get('company_name'))
            ->where('is_active', true)
            ->first();

        if (!$tenant) {
            return response()->json([
                'valid' => false,
                'message' => 'Company not found'
            ], 404);
        }

        return response()->json([
            'valid' => true,
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug
            ]
        ]);
    }

    /**
     * Test method to verify permissions are working
     */
    public function testPermissions(): JsonResponse
    {
        $user = auth('api')->user();
        
        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }
        
        $currentTenant = $user->getCurrentTenant();
        $tenantContext = $user->getTenantContext();
        
        return response()->json([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'current_tenant' => $currentTenant ? [
                'id' => $currentTenant->id,
                'name' => $currentTenant->name,
            ] : null,
            'tenant_context' => $tenantContext,
            'has_tenant_permission_forms_view' => $user->hasTenantPermission('forms.view'),
            'has_tenant_permission_projects_view' => $user->hasTenantPermission('projects.view'),
            'has_tenant_permission_users_view' => $user->hasTenantPermission('users.view'),
            'all_tenant_permissions' => $user->getTenantPermissions(),
        ]);
    }

    protected function respondWithToken($token): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60
        ]);
    }
}
