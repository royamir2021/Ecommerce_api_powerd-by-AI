<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\ErrorLogger;

/**
 * @OA\Tag(
 *     name="Orders",
 *     description="Order Management Endpoints"
 * )
 */
class OrderController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/orders",
     *     tags={"Orders"},
     *     summary="Place a new order based on the cart items",
     *     security={{"BearerAuth": {}}},
     *     @OA\Response(response=201, description="Order placed successfully"),
     *     @OA\Response(response=400, description="Cart is empty"),
     *     @OA\Response(response=500, description="Order placement error")
     * )
     */
    public function placeOrder()
    {
        try {
            $userId = Auth::id();
            $cartItems = Cart::where('user_id', $userId)->get();

            if ($cartItems->isEmpty()) {
                ErrorLogger::logError('Cart Empty for Order Placement', [
                    'user_id' => $userId,
                    'action' => 'place_order'
                ]);

                return response()->json(['message' => 'Cart is empty'], 400);
            }

            $totalAmount = $cartItems->sum(function ($item) {
                return $item->product->price * $item->quantity;
            });

            DB::beginTransaction();

            $order = Order::create([
                'user_id' => $userId,
                'status' => 'pending',
                'total_amount' => $totalAmount,
            ]);

            foreach ($cartItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $item->product->price,
                ]);
            }

            Cart::where('user_id', $userId)->delete();
            DB::commit();

            ErrorLogger::logError('Order Placed Successfully', [
                'user_id' => $userId,
                'order_id' => $order->id,
                'total_amount' => $totalAmount,
                'action' => 'place_order'
            ]);

            return response()->json(['message' => 'Order placed successfully', 'order' => $order], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            ErrorLogger::logError('Order Placement Error', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json(['message' => 'Order could not be placed'], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/orders",
     *     tags={"Orders"},
     *     summary="Get all orders for the authenticated user",
     *     security={{"BearerAuth": {}}},
     *     @OA\Response(response=200, description="Orders retrieved successfully"),
     *     @OA\Response(response=500, description="Order retrieval error")
     * )
     */
    public function getUserOrders()
    {
        try {
            $userId = Auth::id();
            $orders = Order::where('user_id', $userId)
                ->with('items.product')
                ->get();

            ErrorLogger::logError('Order History Viewed', [
                'user_id' => $userId,
                'action' => 'view_orders'
            ]);

            return response()->json($orders);

        } catch (\Exception $e) {
            ErrorLogger::logError('Order Retrieval Error', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json(['message' => 'Unable to fetch orders'], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/orders/{id}",
     *     tags={"Orders"},
     *     summary="Get the details of a specific order",
     *     security={{"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Order ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Order details retrieved successfully"),
     *     @OA\Response(response=404, description="Order not found"),
     *     @OA\Response(response=500, description="Order retrieval error")
     * )
     */
    public function getOrderDetails($id)
    {
        try {
            $userId = Auth::id();
            $order = Order::where('user_id', $userId)
                ->where('id', $id)
                ->with('items.product')
                ->first();

            if (!$order) {
                ErrorLogger::logError('Order Not Found', [
                    'user_id' => $userId,
                    'order_id' => $id,
                    'action' => 'view_order_details'
                ]);

                return response()->json(['message' => 'Order not found'], 404);
            }

            ErrorLogger::logError('Order Details Viewed', [
                'user_id' => $userId,
                'order_id' => $id,
                'action' => 'view_order_details'
            ]);

            return response()->json($order);

        } catch (\Exception $e) {
            ErrorLogger::logError('Order Details Error', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json(['message' => 'Unable to fetch order details'], 500);
        }
    }
}
