<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\ErrorLogger;

/**
 * @OA\Tag(
 *     name="Order History",
 *     description="Order History Management Endpoints"
 * )
 */
class OrderHistoryController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/orders/history",
     *     tags={"Order History"},
     *     summary="Get all order history for the authenticated user",
     *     security={{"BearerAuth": {}}},
     *     @OA\Response(response=200, description="Order history retrieved successfully"),
     *     @OA\Response(response=500, description="Order history retrieval error")
     * )
     */
    public function index()
    {
        try {
            $userId = Auth::id();
            $orders = Order::where('user_id', $userId)->with('items.product')->get();

            ErrorLogger::logError('Order History Viewed', [
                'user_id' => $userId,
                'action' => 'view_order_history'
            ]);

            return response()->json($orders);

        } catch (\Exception $e) {
            ErrorLogger::logError('Order History Retrieval Error', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json(['message' => 'Unable to fetch order history'], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/orders/history/{id}",
     *     tags={"Order History"},
     *     summary="Get specific order details",
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
     *     @OA\Response(response=500, description="Order details retrieval error")
     * )
     */
    public function show($id)
    {
        try {
            $userId = Auth::id();
            $order = Order::where('user_id', $userId)->where('id', $id)->with('items.product')->first();

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
            ErrorLogger::logError('Order Details Retrieval Error', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json(['message' => 'Unable to fetch order details'], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/orders/history/{id}/status",
     *     tags={"Order History"},
     *     summary="Update the status of a specific order",
     *     security={{"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Order ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="shipped")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Order status updated successfully"),
     *     @OA\Response(response=404, description="Order not found"),
     *     @OA\Response(response=500, description="Order status update error")
     * )
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => 'required|string|in:pending,paid,shipped,delivered,cancelled'
            ]);

            $order = Order::where('user_id', Auth::id())->where('id', $id)->first();

            if (!$order) {
                ErrorLogger::logError('Order Not Found for Status Update', [
                    'user_id' => Auth::id(),
                    'order_id' => $id,
                    'action' => 'update_order_status'
                ]);

                return response()->json(['message' => 'Order not found'], 404);
            }

            $order->update(['status' => $request->status]);

            ErrorLogger::logError('Order Status Updated', [
                'user_id' => Auth::id(),
                'order_id' => $id,
                'new_status' => $request->status,
                'action' => 'update_order_status'
            ]);

            return response()->json(['message' => 'Order status updated', 'order' => $order]);

        } catch (\Exception $e) {
            ErrorLogger::logError('Order Status Update Error', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json(['message' => 'Unable to update order status'], 500);
        }
    }
}
