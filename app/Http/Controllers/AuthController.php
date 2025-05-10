<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Services\ErrorLogger;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Ecommerce API",
 *     description="API documentation for the Ecommerce platform"
 * )
 *
 * @OA\Tag(
 *     name="Authentication",
 *     description="User Authentication Endpoints"
 * )
 */
class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/register",
     *     tags={"Authentication"},
     *     summary="Register a new user",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string")
     *         )
     *     ),
     *     @OA\Response(response=201, description="User registered successfully"),
     *     @OA\Response(response=400, description="Validation error"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function register(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6'
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password)
            ]);

            $token = JWTAuth::fromUser($user);

            // Log registration event
            ErrorLogger::logError('User Registered', [
                'user_id' => $user->id,
                'email' => $user->email,
                'action' => 'register'
            ]);

            return response()->json(['token' => $token], 201);

        } catch (\Exception $e) {
            ErrorLogger::logError('Registration Error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Registration failed'], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/login",
     *     tags={"Authentication"},
     *     summary="Login a user",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Login successful"),
     *     @OA\Response(response=401, description="Invalid credentials"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function login(Request $request)
    {
        try {
            $credentials = $request->only('email', 'password');

            if (!$token = JWTAuth::attempt($credentials)) {
                // Log failed login attempt
                ErrorLogger::logError('Login Failed', [
                    'email' => $request->email,
                    'action' => 'login'
                ]);

                return response()->json(['error' => 'Invalid Credentials'], 401);
            }

            // Log successful login
            ErrorLogger::logError('User Logged In', [
                'user_id' => Auth::id(),
                'email' => $request->email,
                'action' => 'login'
            ]);

            return response()->json(['token' => $token]);

        } catch (\Exception $e) {
            ErrorLogger::logError('Login Error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Login failed'], 500);
        }
    }
}
