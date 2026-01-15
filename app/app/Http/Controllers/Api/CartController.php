<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AddCartItemRequest;
use App\Http\Requests\Api\UpdateCartItemRequest;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    public function store(AddCartItemRequest $request)
    {
        $user = $request->user();

        $cart = Cart::firstOrCreate(['user_id' => $user->id]);

        $product = Product::whereKey($request->integer('product_id'))
            ->where('is_active', true)
            ->firstOrFail();

        $qty = $request->integer('qty');

        $item = $cart->items()->where('product_id', $product->id)->first();

        if ($item) {
            $item->qty += $qty;
            $item->save();
        } else {
            $item = $cart->items()->create([
                'product_id' => $product->id,
                'qty' => $qty,
                'price_at_time' => $product->price,
            ]);
        }

        return $this->cartResponse($cart->fresh('items.product'));
    }

    public function show(Request $request)
    {
        $cart = Cart::where('user_id', $request->user()->id)
            ->with('items.product')
            ->first();

        return $this->cartResponse($cart);
    }

    public function update(UpdateCartItemRequest $request, Product $product)
    {
        $cart = Cart::firstOrCreate(['user_id' => $request->user()->id]);

        $item = $cart->items()->where('product_id', $product->id)->first();

        if (! $item) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found in cart.',
            ], 404);
        }

        $qty = $request->integer('qty');

        if ($qty === 0) {
            $item->delete();
        } else {
            $item->qty = $qty;
            $item->save();
        }

        return $this->cartResponse($cart->fresh('items.product'));
    }

    public function destroy(Request $request, Product $product)
    {
        $cart = Cart::where('user_id', $request->user()->id)->first();

        if (! $cart) {
            return response()->json([
                'success' => true,
                'message' => 'Cart is already empty.',
                'data' => [
                    'items' => [],
                    'total' => 0,
                ],
            ]);
        }

        $cart->items()->where('product_id', $product->id)->delete();

        return $this->cartResponse($cart->fresh('items.product'));
    }

    public function checkout(Request $request)
    {
        $user = $request->user();

        $cart = Cart::where('user_id', $user->id)
            ->with('items.product')
            ->lockForUpdate()
            ->first();

        if (! $cart || $cart->items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Cart is empty.',
            ], 400);
        }

        return DB::transaction(function () use ($cart) {
            foreach ($cart->items as $item) {
                $product = $item->product;

                if (! $product || ! $product->is_active || $product->stock < $item->qty) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient stock for product '.$item->product_id,
                    ], 422);
                }
            }

            foreach ($cart->items as $item) {
                $product = $item->product;
                $product->decrement('stock', $item->qty);
            }

            $total = $cart->items->sum(fn (CartItem $item) => $item->qty * $item->price_at_time);

            $cart->items()->delete();
            $cart->delete();

            return response()->json([
                'success' => true,
                'message' => 'Checkout successful.',
                'data' => [
                    'total' => $total,
                ],
            ]);
        });
    }

    protected function cartResponse(?Cart $cart)
    {
        if (! $cart) {
            return response()->json([
                'success' => true,
                'data' => [
                    'items' => [],
                    'total' => 0,
                ],
            ]);
        }

        $items = $cart->items->map(function (CartItem $item) {
            return [
                'product_id' => $item->product_id,
                'name' => $item->product?->name,
                'qty' => $item->qty,
                'price_at_time' => $item->price_at_time,
                'subtotal' => $item->qty * $item->price_at_time,
            ];
        })->values();

        $total = $items->sum('subtotal');

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $items,
                'total' => $total,
            ],
        ]);
    }
}
