<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use App\Services\ErrorLogger;

/**
 * @OA\Tag(
 *     name="Payments",
 *     description="Payment Management Endpoints"
 * )
 */
class PaymentController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/payments",
     *     tags={"Payments"},
     *     summary="Process payment for an order",
     *     security={{"BearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="order_id", type="integer"),
     *             @OA\Property(property="payment_method", type="string", example="pm_card_visa")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Payment processed successfully"),
     *     @OA\Response(response=404, description="Order not found or already paid"),
     *     @OA\Response(response=500, description="Payment processing error")
     * )
     */
    public function processPayment(Request $request)
    {
        try {
            $request->validate([
                'order_id' => 'required|exists:orders,id',
                'payment_method' => 'required|string',
            ]);

            $userId = Auth::id();
            $order = Order::where('id', $request->order_id)
                ->where('user_id', $userId)
                ->where('status', 'pending')
                ->first();

            if (!$order) {
                ErrorLogger::logError('Order Not Found for Payment', [
                    'user_id' => $userId,
                    'order_id' => $request->order_id,
                    'action' => 'process_payment'
                ]);

                return response()->json(['message' => 'Order not found or already paid'], 404);
            }

            Stripe::setApiKey(env('STRIPE_SECRET'));

            $paymentIntent = PaymentIntent::create([
                'amount' => $order->total_amount * 100,
                'currency' => 'usd',
                'payment_method' => $request->payment_method,
                'confirmation_method' => 'automatic',
                'confirm' => true,
            ]);

            $order->update(['status' => 'paid']);

            $payment = Payment::create([
                'order_id' => $order->id,
                'payment_id' => $paymentIntent->id,
                'status' => 'completed'
            ]);

            ErrorLogger::logError('Payment Processed', [
                'user_id' => $userId,
                'order_id' => $order->id,
                'payment_id' => $paymentIntent->id,
                'amount' => $order->total_amount,
                'action' => 'process_payment'
            ]);

            return response()->json(['message' => 'Payment successful', 'payment' => $payment], 200);

        } catch (\Exception $e) {
            ErrorLogger::logError('Payment Processing Error', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json(['message' => 'Payment failed'], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/payments/{id}",
     *     tags={"Payments"},
     *     summary="Get payment details for a specific order",
     *     security={{"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Order ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Payment details retrieved successfully"),
     *     @OA\Response(response=404, description="Payment not found"),
     *     @OA\Response(response=500, description="Payment retrieval error")
     * )
     */
    public function getPaymentDetails($id)
    {
        try {
            $userId = Auth::id();
            $payment = Payment::where('order_id', $id)->first();

            if (!$payment) {
                ErrorLogger::logError('Payment Not Found', [
                    'user_id' => $userId,
                    'order_id' => $id,
                    'action' => 'view_payment_details'
                ]);

                return response()->json(['message' => 'Payment not found'], 404);
            }

            ErrorLogger::logError('Payment Details Viewed', [
                'user_id' => $userId,
                'order_id' => $id,
                'payment_id' => $payment->payment_id,
                'action' => 'view_payment_details'
            ]);

            return response()->json($payment);

        } catch (\Exception $e) {
            ErrorLogger::logError('Payment Details Retrieval Error', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json(['message' => 'Unable to fetch payment details'], 500);
        }
    }
}
