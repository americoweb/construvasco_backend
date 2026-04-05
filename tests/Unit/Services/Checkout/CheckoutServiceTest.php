<?php

namespace Tests\Unit\Services\Checkout;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\Checkout\CheckoutService;
use App\Services\Cart\CartService;
use App\Services\Order\OrderService;
use App\Models\Cart\Cart;
use App\Models\Cart\CartItem;
use Mockery;

class CheckoutServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CheckoutService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = app(CheckoutService::class);
    }

    public function test_validates_checkout_data(): void
    {
        $validData = [
            'shipping_name' => 'João Silva',
            'shipping_address' => 'Av. Eduardo Mondlane, 123',
            'shipping_whatsapp' => '+258841234567',
            'billing_email' => 'joao@example.com',
        ];

        $errors = $this->service->validateCheckoutData($validData);

        $this->assertEmpty($errors);
    }

    public function test_returns_errors_for_invalid_data(): void
    {
        $invalidData = [
            'shipping_name' => '',
            'shipping_whatsapp' => '841234567', // Wrong format
            'billing_email' => 'invalid-email',
        ];

        $errors = $this->service->validateCheckoutData($invalidData);

        $this->assertArrayHasKey('shipping_name', $errors);
        $this->assertArrayHasKey('shipping_address', $errors);
        $this->assertArrayHasKey('shipping_whatsapp', $errors);
        $this->assertArrayHasKey('billing_email', $errors);
    }
}
