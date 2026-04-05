<?php

namespace Database\Factories\Order;

use App\Models\Order\Order;
use App\Enums\Order\OrderStatus;
use App\Enums\Order\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 500, 10000);
        $shipping = $this->faker->randomElement([0, 150, 250, 500]);
        $discount = $this->faker->randomElement([0, 0, 0, 100, 200]);
        
        return [
            'uuid' => Str::uuid(),
            'order_number' => strtoupper(Str::random(9)),
            'user_id' => null,
            'session_id' => Str::random(32),
            'status' => OrderStatus::PENDING,
            'payment_status' => PaymentStatus::PENDING,
            'subtotal' => $subtotal,
            'shipping_cost' => $shipping,
            'tax_amount' => 0,
            'discount_amount' => $discount,
            'total_amount' => $subtotal + $shipping - $discount,
            'currency' => 'MT',
            'shipping_name' => $this->faker->name(),
            'shipping_address' => $this->faker->address(),
            'shipping_city' => 'Maputo',
            'shipping_state' => 'Maputo',
            'shipping_postal_code' => $this->faker->postcode(),
            'shipping_country' => 'Moçambique',
            'shipping_phone' => '+258' . $this->faker->numerify('8#########'),
            'shipping_whatsapp' => '+258' . $this->faker->numerify('8#########'),
            'billing_name' => $this->faker->name(),
            'billing_email' => $this->faker->email(),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::CONFIRMED,
            'confirmed_at' => now(),
        ]);
    }

    public function inProduction(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::IN_PRODUCTION,
            'confirmed_at' => now()->subDays(2),
        ]);
    }

    public function shipped(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::SHIPPED,
            'confirmed_at' => now()->subDays(5),
            'shipped_at' => now()->subDays(1),
        ]);
    }

    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::DELIVERED,
            'confirmed_at' => now()->subDays(10),
            'shipped_at' => now()->subDays(5),
            'delivered_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => 'Cancelado pelo cliente',
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => PaymentStatus::PAID,
        ]);
    }
}
