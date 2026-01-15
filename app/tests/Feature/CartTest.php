<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_to_cart_merges_duplicates(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'stock' => 100,
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'qty' => 2,
        ])->assertStatus(200);

        $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'qty' => 3,
        ])->assertStatus(200);

        $cart = Cart::where('user_id', $user->id)->first();

        $this->assertNotNull($cart);
        $this->assertEquals(1, $cart->items()->count());

        $item = $cart->items()->first();

        $this->assertEquals(5, $item->qty);
    }

    public function test_checkout_fails_when_stock_insufficient_and_does_not_change_state(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'stock' => 2,
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'qty' => 3,
        ])->assertStatus(200);

        $originalStock = $product->stock;

        $response = $this->postJson('/api/cart/checkout');

        $response->assertStatus(422);

        $this->assertEquals($originalStock, $product->fresh()->stock);

        $cart = Cart::where('user_id', $user->id)->first();

        $this->assertNotNull($cart);
        $this->assertEquals(1, $cart->items()->count());
    }
}

