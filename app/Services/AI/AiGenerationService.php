<?php

namespace App\Services\AI;

use App\Enums\AiGenerationStatus;
use App\Enums\AiGenerationType;
use App\Models\AI\AiGeneration;
use App\Models\User;
use App\Exceptions\InsufficientCreditsException;
use App\Services\Credits\CreditService;
use Illuminate\Http\Request;

class AiGenerationService
{
    public function __construct(
        private CreditService $credits,
        private ArchitecturalAiService $architecturalAi
    ) {}

    public function create(User $user, array $data, ?Request $request = null): AiGeneration
    {
        $cost = (int) config('credits.cost_per_generation', 1);
        $type = AiGenerationType::from($data['type'] ?? 'facade_render');

        if ($this->credits->getBalance($user) < $cost) {
            throw new InsufficientCreditsException();
        }

        $generation = AiGeneration::create([
            'user_id' => $user->id,
            'project_request_id' => $data['project_request_id'] ?? null,
            'type' => $type,
            'prompt' => $data['design_prompt'] ?? null,
            'parameters' => $data,
            'status' => AiGenerationStatus::Processing,
            'provider' => 'gemini',
            'parent_generation_id' => $data['parent_generation_id'] ?? null,
        ]);

        try {
            $this->credits->consume($user, $cost, AiGeneration::class, $generation->id);

            $designData = [
                'design_prompt' => $data['design_prompt'] ?? '',
                'reference_image_base64' => $data['reference_image_base64'] ?? null,
                'reference_image_mime_type' => $data['reference_image_mime_type'] ?? null,
                'logo_base64' => $data['logo_base64'] ?? null,
                'logo_mime_type' => $data['logo_mime_type'] ?? null,
            ];

            $path = match ($type) {
                AiGenerationType::Floorplan => $this->architecturalAi->generateFloorPlan(
                    $designData,
                    $data['house_image_url'] ?? ''
                ),
                default => $this->architecturalAi->generateHouseRender($designData),
            };

            $baseUrl = $request?->getSchemeAndHttpHost() ?? config('app.url');
            $imageUrl = rtrim($baseUrl, '/') . '/storage/' . ltrim($path, '/');

            $generation->update([
                'status' => AiGenerationStatus::Completed,
                'image_path' => $path,
                'image_url' => $imageUrl,
                'credits_consumed' => $cost,
            ]);
        } catch (\Throwable $e) {
            $generation->update([
                'status' => AiGenerationStatus::Failed,
                'error_message' => $e->getMessage(),
            ]);

            if (!($e instanceof InsufficientCreditsException)) {
                $this->credits->refund($user, $cost, AiGeneration::class, $generation->id);
            }

            throw $e;
        }

        return $generation->fresh();
    }
}
