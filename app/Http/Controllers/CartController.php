<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function index()
    {
        $cartItems = Cart::with('product')->where('user_id', Auth::id())->get();
        return response()->json($cartItems);
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $cartItem = Cart::where('user_id', Auth::id())
            ->where('product_id', $request->product_id)
            ->first();

        if ($cartItem) {
            $cartItem->quantity += $request->quantity;
            $cartItem->save();
        } else {
            $cartItem = Cart::create([
                'user_id' => Auth::id(),
                'product_id' => $request->product_id,
                'quantity' => $request->quantity
            ]);
        }

        return response()->json($cartItem, 201);
    }

    public function destroy($id)
    {
        $cartItem = Cart::where('user_id', Auth::id())->where('id', $id)->first();

        if (!$cartItem) {
            return response()->json(['message' => 'Item not found in cart'], 404);
        }

        $cartItem->delete();
        return response()->json(['message' => 'Item removed from cart']);
    }
}
