<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Http\Resources\TenantResource;
use App\Models\Settings\Tenant;
use App\Models\TenantInvitation;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\Credits\CreditService;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    public function __construct(
        private ActivityLogService $activityLogService,
        private CreditService $creditService
    )
    {
        $this->middleware('auth:api', ['except' => [
            'login',
            'register',
            'googleLogin',
            'validateInvitation',
            'validateCompanyInvitation',
        ]]);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'identifier' => 'nullable|string',
            'email' => 'nullable|string',
            'password' => 'required|string',
            'tenant_id' => 'nullable|integer|exists:tenants,id',
        ]);

        $raw = $request->input('identifier') ?: $request->input('email');
        $identifier = $raw !== null && $raw !== '' ? strtolower(trim((string) $raw)) : '';

        if ($identifier === '') {
            throw ValidationException::withMessages([
                'identifier' => ['Indique o email (identifier ou email) e a palavra-passe.'],
            ]);
        }

        $password = (string) $request->get('password');

        // Case-insensitive match on identifier (emails stored lowercase from seeders)
        $user = User::whereRaw('LOWER(identifier) = ?', [$identifier])->first();

        if (!$user || !Hash::check($password, $user->password)) {
            $this->activityLogService->logSecurityEvent('failed_login_attempt', [
                'identifier' => $identifier,
                'ip' => $request->ip(),
            ]);

            throw ValidationException::withMessages([
                'identifier' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Generate token for the user
        $token = JWTAuth::fromUser($user);
        
        if (!$user->isActive()) {
            return response()->json([
                'error' => 'Account is inactive. Please contact administrator.'
            ], 403);
        }

        // Handle tenant context (ignore null / "" — has() is true for JSON null)
        if ($request->filled('tenant_id')) {
            $tenantId = (int) $request->get('tenant_id');
            if (!$user->belongsToTenant($tenantId)) {
                return response()->json([
                    'error' => 'You do not have access to the requested organization.'
                ], 403);
            }
            $user->switchTenant($tenantId);
        } else {
            // SPA + JWT: session may not persist; switchTenant sets pivot + session for web,
            // and getCurrentTenantId() now falls back to tenant_users pivot for API requests.
            $firstTenant = $user->activeTenants()->first();
            if ($firstTenant) {
                $user->switchTenant($firstTenant->id);
            }
        }

        $user->updateLastLogin();

        $this->activityLogService->logUserAction('user_logged_in', $user);

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
            'must_change' => (bool) $user->must_change,
            'user' => new UserResource($user),
            'current_tenant' => $user->getCurrentTenant() ?
                new TenantResource($user->getCurrentTenant()) : null,
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'identifier' => 'nullable|string|max:255',
            'email' => 'nullable|string|max:255',
            'type' => 'nullable|in:email,whatsapp',
            'password' => 'required|string|min:8|confirmed',
            'invitation_token' => 'nullable|string',
            'company_name' => 'nullable|string|max:255',
        ]);

        $raw = $request->input('identifier') ?: $request->input('email');
        $identifier = trim((string) $raw);
        if ($identifier === '') {
            throw ValidationException::withMessages([
                'identifier' => ['Indique um email válido para criar a conta.'],
            ]);
        }

        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL) !== false;
        $type = $request->input('type') ?: ($isEmail ? 'email' : 'whatsapp');
        if ($type === 'email') {
            $identifier = strtolower($identifier);
            if (!filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
                throw ValidationException::withMessages([
                    'identifier' => ['Email inválido.'],
                ]);
            }
        }

        try {
            $response = DB::transaction(function () use ($request, $identifier, $type) {
                $existingUser = User::whereRaw('LOWER(identifier) = ?', [strtolower($identifier)])->first();
                if ($existingUser) {
                    throw ValidationException::withMessages([
                        'identifier' => ['Este identificador já está em uso.'],
                    ]);
                }

                $user = User::create([
                    'name' => $request->get('name'),
                    'identifier' => $identifier,
                    'type' => $type,
                    'password' => Hash::make($request->get('password')),
                    'is_active' => true,
                ]);

                $invitationAccepted = $this->processRegistrationInvitation($request, $user, $identifier);

                if (!$invitationAccepted) {
                    $this->attachDefaultCustomerTenant($user);
                    $this->creditService->grantInitialCredits($user);
                }

                $user->refresh();
                $token = JWTAuth::fromUser($user);
                $this->activityLogService->logUserAction('user_registered', $user);

                return response()->json([
                    'access_token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => JWTAuth::factory()->getTTL() * 60,
                    'must_change' => false,
                    'user' => new UserResource($user),
                    'current_tenant' => $user->getCurrentTenant() ? new TenantResource($user->getCurrentTenant()) : null,
                    'message' => $invitationAccepted
                        ? 'Registration successful — invitation accepted'
                        : 'Registration successful',
                ], 201);
            });

            return $response;

        } catch (\Exception $e) {
            if ($e instanceof ValidationException) {
                throw $e;
            }
            return response()->json([
                'error' => 'Registration failed',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        if (!Hash::check((string) $request->input('current_password'), (string) $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Palavra-passe atual incorreta.'],
            ]);
        }

        $user->update([
            'password' => Hash::make((string) $request->input('password')),
            'must_change' => false,
        ]);

        return response()->json([
            'message' => 'Senha alterada com sucesso.',
        ]);
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
     * Google OAuth Login/Sign-up — tenant resolvido via pivot do utilizador (slug construvasco para novos clientes).
     */
    public function googleLogin(Request $request): JsonResponse
    {
        Log::info('Google OAuth login attempt initiated', [
            'ip' => $request->ip(),
            'environment' => app()->environment(),
        ]);

        // Check if Google client ID is configured
        $googleClientId = config('services.google.client_id');
        if (empty($googleClientId)) {
            Log::error('Google Client ID not configured');
            return response()->json([
                'error' => 'Google OAuth is not configured on the server.',
                'error_code' => 'GOOGLE_NOT_CONFIGURED'
            ], 500)->header('Cross-Origin-Opener-Policy', 'same-origin-allow-popups')
                ->header('Cross-Origin-Embedder-Policy', 'unsafe-none');
        }

        $validatedData = $request->validate([
            'token' => 'required|string',
        ]);

        try {
            // Configure HTTP client
            $httpClient = Http::timeout(60);
            
            // Only add SSL verification in local environment (not in production)
            // In production, use system's default CA bundle
            $appEnv = env('APP_ENV', app()->environment());
            if ($appEnv === 'local' && app()->environment('local')) {
                $certPath = 'C:\wamp64\bin\php\php8.2.18\extras\ssl\cacert.pem';
                if (file_exists($certPath)) {
                    $httpClient->withOptions([
                        'verify' => $certPath,
                    ]);
                }
            }
            // In production, Laravel's Http client will use system's default CA bundle automatically

            // Verify Google ID token using Google's tokeninfo endpoint
            $response = $httpClient->get('https://oauth2.googleapis.com/tokeninfo', [
                'id_token' => $validatedData['token']
            ]);

            if (!$response->successful()) {
                Log::error('Google token verification failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return response()->json([
                    'error' => 'Invalid Google token.',
                    'error_code' => 'INVALID_TOKEN'
                ], 401)->header('Cross-Origin-Opener-Policy', 'same-origin-allow-popups')
                    ->header('Cross-Origin-Embedder-Policy', 'unsafe-none');
            }

            $tokenData = $response->json();

            // Verify the token is for our client
            $expectedClientId = config('services.google.client_id');
            $receivedClientId = $tokenData['aud'] ?? null;
            
            if ($receivedClientId !== $expectedClientId) {
                Log::error('Google token audience mismatch', [
                    'expected' => $expectedClientId,
                    'received' => $receivedClientId,
                ]);
                return response()->json([
                    'error' => 'Invalid Google token.',
                    'error_code' => 'INVALID_TOKEN'
                ], 401)->header('Cross-Origin-Opener-Policy', 'same-origin-allow-popups')
                    ->header('Cross-Origin-Embedder-Policy', 'unsafe-none');
            }

            $googleId = $tokenData['sub'];
            $email = $tokenData['email'] ?? null;
            $name = $tokenData['name'] ?? null;
            $picture = $tokenData['picture'] ?? null;

            if (!$email) {
                Log::warning('Google token missing email');
                return response()->json([
                    'error' => 'Google account email is required.',
                    'error_code' => 'MISSING_EMAIL'
                ], 400);
            }

            return DB::transaction(function () use ($googleId, $email, $name) {
                $user = User::where('google_id', $googleId)
                    ->orWhere('identifier', $email)
                    ->first();

                $isNewUser = ! $user;

                if ($isNewUser) {
                    Log::info('New Google user detected, creating account', ['email' => $email]);

                    if (User::where('identifier', $email)->exists()) {
                        return response()->json([
                            'error' => 'This email is already registered. Please sign in instead.',
                            'error_code' => 'EMAIL_EXISTS',
                        ], 422);
                    }

                    $user = User::create([
                        'name' => $name ?? 'User',
                        'identifier' => $email,
                        'type' => 'email',
                        'password' => Hash::make(\Illuminate\Support\Str::random(32)),
                        'google_id' => $googleId,
                        'verified_at' => now(),
                        'is_active' => true,
                    ]);

                    $this->attachDefaultCustomerTenant($user);
                    $this->creditService->grantInitialCredits($user);
                } else {
                    Log::info('Existing Google user detected, logging in', ['user_id' => $user->id, 'email' => $email]);

                    if (! $user->google_id) {
                        $user->google_id = $googleId;
                        $user->save();
                    }

                    $this->ensureCustomerHasTenant($user);
                }

                $user->updateLastLogin();
                $token = JWTAuth::fromUser($user);
                $this->activityLogService->logUserAction($isNewUser ? 'user_registered' : 'user_logged_in', $user);

                return response()->json([
                    'access_token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => JWTAuth::factory()->getTTL() * 60,
                    'must_change' => (bool) $user->must_change,
                    'user' => new UserResource($user->fresh()),
                    'current_tenant' => $user->getCurrentTenant()
                        ? new TenantResource($user->getCurrentTenant())
                        : null,
                    'message' => $isNewUser ? 'User registered successfully with Google.' : 'User authenticated successfully with Google.',
                ], $isNewUser ? 201 : 200)->header('Cross-Origin-Opener-Policy', 'same-origin-allow-popups')
                    ->header('Cross-Origin-Embedder-Policy', 'unsafe-none');
            });
        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('Google token verification failed (Request error)', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to verify Google token. Please try again.',
                'error_code' => 'TOKEN_VERIFICATION_FAILED'
            ], 500);
        } catch (\Exception $e) {
            Log::error('Google login failed with exception', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'error' => 'Google authentication failed.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function validateInvitation(Request $request): JsonResponse
    {
        $request->validate(['token' => 'required|string']);

        $invitation = TenantInvitation::where('token', $request->get('token'))
            ->where('status', 'pending')
            ->first();

        if (!$invitation) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid or expired invitation token',
            ], 404);
        }

        $tenant = Tenant::find($invitation->tenant_id);

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
                    'slug' => $tenant->slug,
                ] : null,
            ],
        ]);
    }

    public function validateCompanyInvitation(Request $request): JsonResponse
    {
        $request->validate(['company_name' => 'required|string']);

        $tenant = Tenant::where('name', $request->get('company_name'))
            ->where('is_active', true)
            ->first();

        if (!$tenant) {
            return response()->json([
                'valid' => false,
                'message' => 'Company not found',
            ], 404);
        }

        $company = [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'slug' => $tenant->slug,
        ];

        return response()->json([
            'valid' => true,
            'tenant' => $company,
            'company' => $company,
        ]);
    }

    private function processRegistrationInvitation(Request $request, User $user, string $identifier): bool
    {
        if ($request->filled('invitation_token')) {
            $invitation = TenantInvitation::where('token', $request->get('invitation_token'))
                ->where('status', 'pending')
                ->first();

            if ($invitation) {
                $tenant = Tenant::find($invitation->tenant_id);
                if ($tenant) {
                    $this->acceptInvitation($invitation, $tenant, $user);

                    return true;
                }
            }

            return false;
        }

        if ($request->filled('company_name')) {
            $tenant = Tenant::where('name', $request->get('company_name'))
                ->where('is_active', true)
                ->first();

            if ($tenant) {
                $invitation = TenantInvitation::where('identifier', $identifier)
                    ->where('tenant_id', $tenant->id)
                    ->where('status', 'pending')
                    ->first();

                if ($invitation) {
                    $this->acceptInvitation($invitation, $tenant, $user);

                    return true;
                }
            }
        } else {
            $invitation = TenantInvitation::where('identifier', $identifier)
                ->where('status', 'pending')
                ->first();

            if ($invitation) {
                $tenant = Tenant::find($invitation->tenant_id);
                if ($tenant) {
                    $this->acceptInvitation($invitation, $tenant, $user);

                    return true;
                }
            }
        }

        return false;
    }

    private function acceptInvitation(TenantInvitation $invitation, Tenant $tenant, User $user): void
    {
        app(TenantService::class)->addUserToTenant(
            $tenant,
            $user,
            $invitation->role,
            true,
            []
        );

        $invitation->update([
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);

        $user->switchTenant($tenant->id);
    }

    private function attachDefaultCustomerTenant(User $user): void
    {
        $tenant = Tenant::where('slug', 'construvasco')->where('is_active', true)->first();
        if (! $tenant) {
            $tenant = Tenant::where('is_active', true)->orderBy('id')->first();
        }
        if (! $tenant) {
            throw new \RuntimeException('Nenhum tenant activo encontrado para registo de cliente.');
        }

        $tenantId = $tenant->id;
        $customerRole = Role::where('name', 'customer')->where('guard_name', 'api')->first();
        if (! $customerRole) {
            $customerRole = Role::create([
                'name' => 'customer',
                'guard_name' => 'api',
                'display_name' => 'Customer',
                'description' => 'Customer access',
                'is_system' => true,
            ]);
        }

        if (! $user->tenants()->where('tenants.id', $tenantId)->exists()) {
            $user->tenants()->attach($tenantId, [
                'role_id' => $customerRole->id,
                'current_tenant' => true,
                'status' => 'active',
            ]);
        } else {
            $user->tenants()->updateExistingPivot($tenantId, ['current_tenant' => true]);
        }

        $user->switchTenant($tenantId);

        DB::table('model_has_roles')->updateOrInsert(
            [
                'role_id' => $customerRole->id,
                'model_type' => get_class($user),
                'model_id' => $user->id,
                'tenant_id' => $tenantId,
            ],
            []
        );
    }

    private function ensureCustomerHasTenant(User $user): void
    {
        if ($user->tenants()->exists()) {
            $current = $user->tenants()->wherePivot('current_tenant', true)->first()
                ?? $user->tenants()->first();
            if ($current) {
                $user->switchTenant($current->id);
            }

            return;
        }

        $this->attachDefaultCustomerTenant($user);
    }
}