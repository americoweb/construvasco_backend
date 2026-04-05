<?php

namespace App\Jobs\AI;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\AI\SuggestionService;
use Illuminate\Support\Facades\Log;

class GenerateBulkMockups implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public array $products,
        public array $designData,
        public ?string $callbackUrl = null
    ) {}

    public function handle(SuggestionService $suggestionService): void
    {
        $results = [];

        foreach ($this->products as $productId) {
            try {
                $mockupUrl = $suggestionService->generateMockup($productId, $this->designData);
                $results[$productId] = [
                    'success' => true,
                    'mockup_url' => $mockupUrl,
                ];
            } catch (\Exception $e) {
                Log::error('Bulk mockup generation failed', [
                    'product_id' => $productId,
                    'error' => $e->getMessage(),
                ]);
                $results[$productId] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        // TODO: Send callback with results if URL provided
        if ($this->callbackUrl) {
            Log::info('Bulk mockup generation completed', [
                'results' => $results,
                'callback_url' => $this->callbackUrl,
            ]);
        }
    }
}
