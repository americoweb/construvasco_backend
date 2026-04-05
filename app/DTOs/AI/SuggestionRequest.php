<?php

namespace App\DTOs\AI;

class SuggestionRequest
{
    public function __construct(
        public string $goal,
        public float $budget,
        public ?string $logoBase64 = null,
        public ?string $logoMimeType = null,
        public ?string $referenceImageBase64 = null,
        public ?string $referenceImageMimeType = null,
        public int $maxSuggestions = 5,
        public bool $includeBundles = true
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            goal: $data['goal'],
            budget: (float) $data['budget'],
            logoBase64: $data['logo_base64'] ?? null,
            logoMimeType: $data['logo_mime_type'] ?? null,
            referenceImageBase64: $data['reference_image_base64'] ?? null,
            referenceImageMimeType: $data['reference_image_mime_type'] ?? null,
            maxSuggestions: $data['max_suggestions'] ?? 5,
            includeBundles: $data['include_bundles'] ?? true
        );
    }

    public function hasLogo(): bool
    {
        return !empty($this->logoBase64);
    }

    public function hasReferenceImage(): bool
    {
        return !empty($this->referenceImageBase64);
    }
}
