<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Http\Resources\TenantResource;
use App\Models\User;
use App\Services\Shared\FileStorageService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    protected $fileStorageService;

    public function __construct(FileStorageService $fileStorageService)
    {
        $this->fileStorageService = $fileStorageService;
    }

    /**
     * Get current user profile
     */
    public function profile(): JsonResponse
    {
        $user = auth('api')->user();
        
        // Get profile data from settings
        $settings = $user->getSettingsArray();
        $profileData = $settings['profile'] ?? [];
        
        // Use UserResource for consistent format
        $userResource = new UserResource($user);
        $userArray = $userResource->toArray(request());
        
        // Add profile fields from settings
        $userArray['phone'] = $profileData['phone'] ?? null;
        $userArray['whatsapp'] = $profileData['whatsapp'] ?? null;
        $userArray['company'] = $profileData['company'] ?? null;
        $userArray['job_title'] = $profileData['job_title'] ?? null;
        $userArray['bio'] = $profileData['bio'] ?? null;
        
        return response()->json([
            'success' => true,
            'data' => $userArray
        ]);
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|nullable|string|max:20',
            'whatsapp' => 'sometimes|nullable|string|max:20',
            'company' => 'sometimes|nullable|string|max:255',
            'job_title' => 'sometimes|nullable|string|max:255',
            'bio' => 'sometimes|nullable|string|max:1000',
            'settings' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        
        // Update basic user fields
        if (isset($data['name'])) {
            $user->name = $data['name'];
        }
        
        // Update settings (merge with existing)
        if (isset($data['settings'])) {
            $currentSettings = $user->getSettingsArray();
            $user->setSettingsArray(array_merge($currentSettings, $data['settings']));
        }
        
        // Store additional profile data in settings
        $profileData = array_intersect_key($data, array_flip(['phone', 'whatsapp', 'company', 'job_title', 'bio']));
        if (!empty($profileData)) {
            $currentSettings = $user->getSettingsArray();
            $currentSettings['profile'] = array_merge($currentSettings['profile'] ?? [], $profileData);
            $user->setSettingsArray($currentSettings);
        }
        
        $user->save();

        // Refresh user to get latest data
        $user->refresh();
        
        // Get profile data from settings for response
        $settings = $user->getSettingsArray();
        $profileData = $settings['profile'] ?? [];
        
        // Use UserResource for consistent format
        $userResource = new UserResource($user);
        $userArray = $userResource->toArray(request());
        
        // Add profile fields from settings
        $userArray['phone'] = $profileData['phone'] ?? null;
        $userArray['whatsapp'] = $profileData['whatsapp'] ?? null;
        $userArray['company'] = $profileData['company'] ?? null;
        $userArray['job_title'] = $profileData['job_title'] ?? null;
        $userArray['bio'] = $profileData['bio'] ?? null;

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $userArray
        ]);
    }

    /**
     * Upload user avatar
     */
    public function uploadAvatar(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        
        Log::info('Avatar upload started for user: ' . $user->id);
        
        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048', // 2MB max
        ]);

        if ($validator->fails()) {
            Log::error('Avatar upload validation failed: ' . json_encode($validator->errors()));
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            Log::info('Avatar upload validation passed');
            
            // Delete old avatar if exists
            if ($user->profile_photo_path) {
                Log::info('Deleting old avatar: ' . $user->profile_photo_path);
                Storage::disk('public')->delete($user->profile_photo_path);
            }

            // Store new avatar
            $file = $request->file('avatar');
            $path = $file->store('avatars', 'public');
            
            Log::info('New avatar stored at: ' . $path);
            
            $user->profile_photo_path = $path;
            $user->save();
            
            Log::info('User profile_photo_path updated to: ' . $user->profile_photo_path);

            return response()->json([
                'success' => true,
                'message' => 'Avatar uploaded successfully',
                'data' => [
                    'avatar_url' => Storage::disk('public')->url($path),
                    'profile_photo_path' => $path
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Avatar upload failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload avatar',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user settings
     */
    public function getSettings(): JsonResponse
    {
        $user = auth('api')->user();
        
        return response()->json([
            'success' => true,
            'data' => $user->getSettingsArray()
        ]);
    }

    /**
     * Update user settings
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        
        $validator = Validator::make($request->all(), [
            'settings' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $currentSettings = $user->getSettingsArray();
        $user->setSettingsArray(array_merge($currentSettings, $request->settings));
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully',
            'data' => $user->getSettingsArray()
        ]);
    }

    /**
     * Get user tenants
     */
    public function getTenants(): JsonResponse
    {
        $user = auth('api')->user();
        $tenants = $user->activeTenants()->get();
        
        return response()->json([
            'success' => true,
            'data' => TenantResource::collection($tenants)
        ]);
    }

    /**
     * Switch tenant
     */
    public function switchTenant(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        
        $validator = Validator::make($request->all(), [
            'tenant_id' => 'required|integer|exists:tenants,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $tenantId = $request->tenant_id;
        
        if (!$user->belongsToTenant($tenantId)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this tenant'
            ], 403);
        }

        $success = $user->switchTenant($tenantId);
        
        if ($success) {
            $tenant = $user->getCurrentTenant();
            return response()->json([
                'success' => true,
                'message' => 'Tenant switched successfully',
                'data' => $tenant ? (new TenantResource($tenant))->toArray(request()) : null
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to switch tenant'
        ], 500);
    }
} 