<?php

namespace Tests\Feature\Checkout;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Cart\Cart;
use App\Models\Cart\CartItem;
use App\Models\Product\Product;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_checkout_summary(): void
    {
        $cart = Cart::factory()->create();
        CartItem::factory()->count(2)->create(['cart_id' => $cart->id]);

        $response = $this->getJson("/api/v1/checkout/summary/{$cart->uuid}");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         'cart_uuid',
                         'items',
                         'subtotal',
                         'formatted_subtotal',
                         'total_items',
                         'shipping_options',
                     ]
                 ]);
    }

    public function test_can_process_checkout(): void
    {
        $cart = Cart::factory()->create();
        CartItem::factory()->count(2)->create(['cart_id' => $cart->id]);

        $checkoutData = [
            'cart_uuid' => $cart->uuid,
            'shipping_name' => 'João Silva',
            'shipping_address' => 'Av. Eduardo Mondlane, 123',
            'shipping_city' => 'Maputo',
            'shipping_whatsapp' => '+258841234567',
            'billing_email' => 'joao@example.com',
        ];

        $response = $this->postJson('/api/v1/checkout/process', $checkoutData);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'data' => [
                         'order_id',
                         'order_number',
                         'status',
                         'total_amount',
                     ],
                     'message'
                 ]);
    }

    public function test_cannot_checkout_empty_cart(): void
    {
        $cart = Cart::factory()->create();

        $checkoutData = [
            'cart_uuid' => $cart->uuid,
            'shipping_name' => 'João Silva',
            'shipping_address' => 'Av. Eduardo Mondlane, 123',
            'shipping_whatsapp' => '+258841234567',
            'billing_email' => 'joao@example.com',
        ];

        $response = $this->postJson('/api/v1/checkout/process', $checkoutData);

        $response->assertStatus(422);
    }

    public function test_validates_whatsapp_format(): void
    {
        $cart = Cart::factory()->create();
        CartItem::factory()->create(['cart_id' => $cart->id]);

        $checkoutData = [
            'cart_uuid' => $cart->uuid,
            'shipping_name' => 'João Silva',
            'shipping_address' => 'Av. Eduardo Mondlane, 123',
            'shipping_whatsapp' => '841234567', // Invalid format
            'billing_email' => 'joao@example.com',
        ];

        $response = $this->postJson('/api/v1/checkout/process', $checkoutData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['shipping_whatsapp']);
    }

    public function test_can_get_shipping_options(): void
    {
        $cart = Cart::factory()->create();
        CartItem::factory()->create(['cart_id' => $cart->id]);

        $response = $this->getJson("/api/v1/checkout/shipping/{$cart->uuid}");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         '*' => [
                             'id',
                             'name',
                             'price',
                             'formatted_price',
                             'delivery_time',
                         ]
                     ]
                 ]);
    }
}
