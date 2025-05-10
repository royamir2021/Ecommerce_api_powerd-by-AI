<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\ErrorLogger;

/**
 * @OA\Tag(
 *     name="Cart",
 *     description="Cart Management Endpoints"
 * )
 */
class CartController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/cart",
     *     tags={"Cart"},
     *     summary="Get all cart items for the authenticated user",
     *     security={{"BearerAuth": {}}},
     *     @OA\Response(response=200, description="Cart items retrieved successfully"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function index()
    {
        try {
            $userId = Auth::id();
            $cartItems = Cart::with('product')->where('user_id', $userId)->get();

            ErrorLogger::logError('Cart Viewed', [
                'user_id' => $userId,
                'action' => 'view_cart'
            ]);

            return response()->json($cartItems);

        } catch (\Exception $e) {
            ErrorLogger::logError('Cart View Error', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Unable to fetch cart items'], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/cart",
     *     tags={"Cart"},
     *     summary="Add a product to the cart",
     *     security={{"BearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="product_id", type="integer"),
     *             @OA\Property(property="quantity", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Item added to cart successfully"),
     *     @OA\Response(response=400, description="Validation error"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'product_id' => 'required|exists:products,id',
                'quantity' => 'required|integer|min:1'
            ]);

            $userId = Auth::id();
            $cartItem = Cart::where('user_id', $userId)
                ->where('product_id', $request->product_id)
                ->first();

            if ($cartItem) {
                $cartItem->quantity += $request->quantity;
                $cartItem->save();

                ErrorLogger::logError('Cart Updated', [
                    'user_id' => $userId,
                    'product_id' => $request->product_id,
                    'quantity' => $cartItem->quantity,
                    'action' => 'update_cart'
                ]);

            } else {
                $cartItem = Cart::create([
                    'user_id' => $userId,
                    'product_id' => $request->product_id,
                    'quantity' => $request->quantity
                ]);

                ErrorLogger::logError('Item Added to Cart', [
                    'user_id' => $userId,
                    'product_id' => $request->product_id,
                    'quantity' => $request->quantity,
                    'action' => 'add_to_cart'
                ]);
            }

            return response()->json($cartItem, 201);

        } catch (\Exception $e) {
            ErrorLogger::logError('Cart Store Error', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Unable to add item to cart'], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/cart/{id}",
     *     tags={"Cart"},
     *     summary="Remove an item from the cart",
     *     security={{"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Cart item ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Item removed from cart successfully"),
     *     @OA\Response(response=404, description="Item not found"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function destroy($id)
    {
        try {
            $userId = Auth::id();
            $cartItem = Cart::where('user_id', $userId)->where('id', $id)->first();

            if (!$cartItem) {
                ErrorLogger::logError('Cart Item Not Found', [
                    'user_id' => $userId,
                    'cart_item_id' => $id,
                    'action' => 'delete_cart_item'
                ]);

                return response()->json(['message' => 'Item not found in cart'], 404);
            }

            $cartItem->delete();

            ErrorLogger::logError('Item Removed from Cart', [
                'user_id' => $userId,
                'cart_item_id' => $id,
                'action' => 'delete_cart_item'
            ]);

            return response()->json(['message' => 'Item removed from cart']);

        } catch (\Exception $e) {
            ErrorLogger::logError('Cart Delete Error', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Unable to remove item from cart'], 500);
        }
    }
}
