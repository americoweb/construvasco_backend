<?php

namespace App\DTOs\AI;

class SuggestionResponse
{
    public function __construct(
        public string $type, // 'single' or 'bundle'
        public string $title,
        public array $products, // Array of ProductSuggestion
        public float $estimatedTotal,
        public string $cta,
        public ?string $bundleTheme = null
    ) {}

    public static function fromArray(array $data): self
    {
        $products = array_map(
            fn($p) => ProductSuggestion::fromArray($p),
            $data['products'] ?? []
        );

        return new self(
            type: $data['type'] ?? 'single',
            title: $data['title'] ?? '',
            products: $products,
            estimatedTotal: (float) ($data['estimated_total'] ?? $data['estimatedTotal'] ?? 0),
            cta: $data['cta'] ?? '',
            bundleTheme: $data['bundle_theme'] ?? $data['bundleTheme'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'title' => $this->title,
            'products' => array_map(fn($p) => $p->toArray(), $this->products),
            'estimated_total' => $this->estimatedTotal,
            'cta' => $this->cta,
            'bundle_theme' => $this->bundleTheme,
        ];
    }

    public function getProductCount(): int
    {
        return count($this->products);
    }

    public function isBundle(): bool
    {
        return $this->type === 'bundle';
    }
}
