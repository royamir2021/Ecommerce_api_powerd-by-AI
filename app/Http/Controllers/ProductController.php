<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\ErrorLogger;

/**
 * @OA\Tag(
 *     name="Products",
 *     description="Product Management Endpoints"
 * )
 */
class ProductController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/products",
     *     tags={"Products"},
     *     summary="Get all products",
     *     @OA\Response(response=200, description="Products retrieved successfully"),
     *     @OA\Response(response=500, description="Product retrieval error")
     * )
     */
    public function index()
    {
        try {
            $products = Product::all();

            ErrorLogger::logError('Product List Viewed', [
                'user_id' => Auth::id(),
                'action' => 'view_products'
            ]);

            return response()->json($products);

        } catch (\Exception $e) {
            ErrorLogger::logError('Product Retrieval Error', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json(['message' => 'Unable to fetch products'], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/products",
     *     tags={"Products"},
     *     summary="Create a new product",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="price", type="number"),
     *             @OA\Property(property="stock", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Product created successfully"),
     *     @OA\Response(response=400, description="Validation error"),
     *     @OA\Response(response=500, description="Product creation error")
     * )
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'price' => 'required|numeric',
                'stock' => 'required|integer',
            ]);

            $product = Product::create($request->all());

            ErrorLogger::logError('Product Created', [
                'user_id' => Auth::id(),
                'product_id' => $product->id,
                'action' => 'create_product'
            ]);

            return response()->json($product, 201);

        } catch (\Exception $e) {
            ErrorLogger::logError('Product Creation Error', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json(['message' => 'Unable to create product'], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/products/{id}",
     *     tags={"Products"},
     *     summary="Get product details",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Product ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Product details retrieved successfully"),
     *     @OA\Response(response=404, description="Product not found"),
     *     @OA\Response(response=500, description="Product retrieval error")
     * )
     */
    public function show($id)
    {
        try {
            $product = Product::find($id);

            if (!$product) {
                ErrorLogger::logError('Product Not Found', [
                    'user_id' => Auth::id(),
                    'product_id' => $id,
                    'action' => 'view_product'
                ]);

                return response()->json(['message' => 'Product not found'], 404);
            }

            ErrorLogger::logError('Product Viewed', [
                'user_id' => Auth::id(),
                'product_id' => $id,
                'action' => 'view_product'
            ]);

            return response()->json($product);

        } catch (\Exception $e) {
            ErrorLogger::logError('Product View Error', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json(['message' => 'Unable to fetch product'], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/products/{id}",
     *     tags={"Products"},
     *     summary="Update a product",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Product ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="price", type="number"),
     *             @OA\Property(property="stock", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Product updated successfully"),
     *     @OA\Response(response=404, description="Product not found"),
     *     @OA\Response(response=500, description="Product update error")
     * )
     */
    public function update(Request $request, $id)
    {
        try {
            $product = Product::find($id);

            if (!$product) {
                ErrorLogger::logError('Product Not Found for Update', [
                    'user_id' => Auth::id(),
                    'product_id' => $id,
                    'action' => 'update_product'
                ]);

                return response()->json(['message' => 'Product not found'], 404);
            }

            $product->update($request->all());

            ErrorLogger::logError('Product Updated', [
                'user_id' => Auth::id(),
                'product_id' => $id,
                'action' => 'update_product'
            ]);

            return response()->json($product);

        } catch (\Exception $e) {
            ErrorLogger::logError('Product Update Error', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json(['message' => 'Unable to update product'], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/products/{id}",
     *     tags={"Products"},
     *     summary="Delete a product",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Product ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Product deleted successfully"),
     *     @OA\Response(response=404, description="Product not found"),
     *     @OA\Response(response=500, description="Product deletion error")
     * )
     */
    public function destroy($id)
    {
        try {
            $product = Product::find($id);

            if (!$product) {
                ErrorLogger::logError('Product Not Found for Deletion', [
                    'user_id' => Auth::id(),
                    'product_id' => $id,
                    'action' => 'delete_product'
                ]);

                return response()->json(['message' => 'Product not found'], 404);
            }

            $product->delete();

            ErrorLogger::logError('Product Deleted', [
                'user_id' => Auth::id(),
                'product_id' => $id,
                'action' => 'delete_product'
            ]);

            return response()->json(['message' => 'Product deleted successfully']);

        } catch (\Exception $e) {
            ErrorLogger::logError('Product Deletion Error', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json(['message' => 'Unable to delete product'], 500);
        }
    }
}
