<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Http\Resources\TenantResource;
use App\Models\User;
use App\Services\ActivityLogService;
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
    public function __construct(private ActivityLogService $activityLogService)
    {
        $this->middleware('auth:api', ['except' => ['login', 'register', 'googleLogin']]);
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

                $tenantId = 1;
                $customerRole = Role::where('name', 'customer')->where('guard_name', 'api')->first();
                if (!$customerRole) {
                    $customerRole = Role::create([
                        'name' => 'customer',
                        'guard_name' => 'api',
                        'display_name' => 'Customer',
                        'description' => 'Customer access',
                        'is_system' => true,
                    ]);
                }

                $user->tenants()->attach($tenantId, [
                    'role_id' => $customerRole->id,
                    'current_tenant' => true,
                    'status' => 'active',
                ]);
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

                $token = JWTAuth::fromUser($user);
                $this->activityLogService->logUserAction('user_registered', $user);

                return response()->json([
                    'access_token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => JWTAuth::factory()->getTTL() * 60,
                    'user' => new UserResource($user),
                    'current_tenant' => $user->getCurrentTenant() ? new TenantResource($user->getCurrentTenant()) : null,
                    'message' => 'Registration successful'
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
     * Google OAuth Login/Sign-up
     * Simplified version: all users are customers on tenant_id = 1
     * 
     * @param Request $request
     * @return JsonResponse
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

            return DB::transaction(function () use ($googleId, $email, $name, $picture) {
                // Check if user exists by google_id or email
                $user = User::where('google_id', $googleId)
                    ->orWhere('identifier', $email)
                    ->first();

                $isNewUser = !$user;
                $tenantId = 1; // Always use tenant 1

                if ($isNewUser) {
                    // NEW USER - Create with tenant_id = 1, role = customer
                    Log::info('New Google user detected, creating account', ['email' => $email]);

                    // Check if email is already registered
                    if (User::where('identifier', $email)->exists()) {
                        Log::warning('Email already registered', ['email' => $email]);
                        return response()->json([
                            'error' => 'This email is already registered. Please sign in instead.',
                            'error_code' => 'EMAIL_EXISTS'
                        ], 422);
                    }

                    // Create the user
                    $user = User::create([
                        'name' => $name ?? 'User',
                        'identifier' => $email,
                        'type' => 'email',
                        'password' => Hash::make(\Illuminate\Support\Str::random(32)), // Random password since Google auth is used
                        'google_id' => $googleId,
                        'verified_at' => now(), // Google-verified emails are pre-verified
                        'is_active' => true,
                    ]);

                    Log::info('User created successfully', ['user_id' => $user->id, 'name' => $user->name]);

                    // Attach user to tenant 1 with customer role
                    $customerRole = Role::where('name', 'customer')->where('guard_name', 'api')->first();
                    
                    // Create customer role if it doesn't exist
                    if (!$customerRole) {
                        Log::warning('Customer role not found, creating it', ['user_id' => $user->id]);
                        $customerRole = Role::create([
                            'name' => 'customer',
                            'guard_name' => 'api',
                            'display_name' => 'Customer',
                            'description' => 'Customer access for e-commerce platform',
                            'is_system' => true,
                        ]);
                        Log::info('Customer role created', ['role_id' => $customerRole->id]);
                    }

                    // Attach user to tenant 1
                    $user->tenants()->attach($tenantId, [
                        'role_id' => $customerRole->id,
                        'current_tenant' => true,
                        'status' => 'active'
                    ]);

                    // Set tenant in session before assigning role (required for Spatie Permission with teams)
                    session(['tenant_id' => $tenantId]);

                    // Assign customer role to user with tenant context
                    // Use direct DB insert to ensure tenant_id is set (Spatie Permission with teams)
                    DB::table('model_has_roles')->insert([
                        'role_id' => $customerRole->id,
                        'model_type' => get_class($user),
                        'model_id' => $user->id,
                        'tenant_id' => $tenantId,
                    ]);

                    Log::info('User attached to tenant 1 as customer', [
                        'user_id' => $user->id,
                        'tenant_id' => $tenantId,
                        'role_id' => $customerRole->id
                    ]);
                } else {
                    // EXISTING USER - Login flow
                    Log::info('Existing Google user detected, logging in', ['user_id' => $user->id, 'email' => $email]);

                    // Update google_id if not set
                    if (!$user->google_id) {
                        $user->google_id = $googleId;
                        $user->save();
                        Log::info('Updated user with google_id', ['user_id' => $user->id]);
                    }

                    // Ensure user is attached to tenant 1
                    if (!$user->tenants()->where('tenants.id', $tenantId)->exists()) {
                        $customerRole = Role::where('name', 'customer')->where('guard_name', 'api')->first();
                        
                        // Create customer role if it doesn't exist
                        if (!$customerRole) {
                            Log::warning('Customer role not found, creating it', ['user_id' => $user->id]);
                            $customerRole = Role::create([
                                'name' => 'customer',
                                'guard_name' => 'api',
                                'display_name' => 'Customer',
                                'description' => 'Customer access for e-commerce platform',
                                'is_system' => true,
                            ]);
                            Log::info('Customer role created', ['role_id' => $customerRole->id]);
                        }

                        $user->tenants()->attach($tenantId, [
                            'role_id' => $customerRole->id,
                            'current_tenant' => true,
                            'status' => 'active'
                        ]);

                        // Set tenant in session before assigning role (required for Spatie Permission with teams)
                        session(['tenant_id' => $tenantId]);

                        // Assign customer role if not already assigned
                        if (!$user->hasRole('customer')) {
                            // Use direct DB insert to ensure tenant_id is set (Spatie Permission with teams)
                            DB::table('model_has_roles')->insert([
                                'role_id' => $customerRole->id,
                                'model_type' => get_class($user),
                                'model_id' => $user->id,
                                'tenant_id' => $tenantId,
                            ]);
                        }
                    } else {
                        // Set tenant 1 as current tenant
                        $user->tenants()->updateExistingPivot($tenantId, ['current_tenant' => true]);
                    }

                    // Set tenant in session
                    session(['tenant_id' => $tenantId]);
                    Log::info('Tenant 1 set in session for existing user', ['tenant_id' => $tenantId]);
                }

                // Update last login
                $user->updateLastLogin();

                // Generate JWT token
                $token = JWTAuth::fromUser($user);
                Log::info('JWT token generated for Google user', ['user_id' => $user->id]);

                $this->activityLogService->logUserAction($isNewUser ? 'user_registered' : 'user_logged_in', $user);

                return response()->json([
                    'access_token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => JWTAuth::factory()->getTTL() * 60,
                    'user' => new UserResource($user),
                    'current_tenant' => $user->getCurrentTenant() ? 
                        new TenantResource($user->getCurrentTenant()) : null,
                    'message' => $isNewUser ? 'User registered successfully with Google.' : 'User authenticated successfully with Google.'
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
}