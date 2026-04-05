<?php

namespace App\DTOs\AI;

class ProductSuggestion
{
    public function __construct(
        public string $name,
        public string $designPrompt,
        public string $theme,
        public string $textToPrint,
        public string $colorStyle,
        public float $price,
        public int $quantity,
        public ?string $mockupUrl = null,
        public ?int $productId = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            designPrompt: $data['design_prompt'] ?? $data['designPrompt'] ?? '',
            theme: $data['theme'] ?? '',
            textToPrint: $data['text_to_print'] ?? $data['textToPrint'] ?? '',
            colorStyle: $data['color_style'] ?? $data['colorStyle'] ?? '',
            price: (float) ($data['price'] ?? 0),
            quantity: (int) ($data['quantity'] ?? 1),
            mockupUrl: $data['mockup_url'] ?? $data['mockupUrl'] ?? null,
            productId: $data['product_id'] ?? $data['productId'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'design_prompt' => $this->designPrompt,
            'theme' => $this->theme,
            'text_to_print' => $this->textToPrint,
            'color_style' => $this->colorStyle,
            'price' => $this->price,
            'quantity' => $this->quantity,
            'mockup_url' => $this->mockupUrl,
            'product_id' => $this->productId,
        ];
    }

    public function getTotalPrice(): float
    {
        return $this->price * $this->quantity;
    }
}
