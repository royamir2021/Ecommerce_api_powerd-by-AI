<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stripe\Stripe;
use Stripe\PaymentIntent;

class PaymentController extends Controller
{
    public function processPayment(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'payment_method' => 'required|string',
        ]);

        $order = Order::where('id', $request->order_id)
            ->where('user_id', Auth::id())
            ->where('status', 'pending')
            ->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found or already paid'], 404);
        }

        try {
            Stripe::setApiKey(env('STRIPE_SECRET'));

            $paymentIntent = PaymentIntent::create([
                'amount' => $order->total_amount * 100, // Amount in cents
                'currency' => 'usd',
                'payment_method' => $request->payment_method,
                'confirmation_method' => 'automatic',
                'confirm' => true,
            ]);

            $order->update(['status' => 'paid']);

            return response()->json([
                'message' => 'Payment successful',
                'paymentIntent' => $paymentIntent
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Payment failed', 'error' => $e->getMessage()], 500);
        }
    }
}
