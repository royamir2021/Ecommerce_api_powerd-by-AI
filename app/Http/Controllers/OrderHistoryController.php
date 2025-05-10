<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderHistoryController extends Controller
{

    public function index()
    {
        $userId = Auth::id();
        $orders = Order::where('user_id', $userId)->with('items.product')->get();
        return response()->json($orders);
    }


    public function show($id)
    {
        $userId = Auth::id();
        $order = Order::where('user_id', $userId)->where('id', $id)->with('items.product')->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        return response()->json($order);
    }

    /**
     * بروزرسانی وضعیت سفارش
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:pending,paid,shipped,delivered,cancelled'
        ]);

        $order = Order::find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $order->update(['status' => $request->status]);

        return response()->json(['message' => 'Order status updated', 'order' => $order]);
    }
}
