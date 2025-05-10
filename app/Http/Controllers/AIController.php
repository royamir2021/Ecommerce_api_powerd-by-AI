<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Orhanerday\OpenAi\OpenAi;
use App\Models\Product;

/**
 * @OA\Tag(
 *     name="AI",
 *     description="AI-Powered Endpoints"
 * )
 */
class AIController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/products/{id}/generate-description",
     *     tags={"AI"},
     *     summary="Generate marketing description for a product using AI",
     *     security={{"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Product ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="AI-generated product description"),
     *     @OA\Response(response=404, description="Product not found"),
     *     @OA\Response(response=500, description="AI processing error")
     * )
     */
    public function generateProductDescription($id)
    {
        try {
            $product = Product::find($id);

            if (!$product) {
                Log::error('Product not found for AI processing', [
                    'product_id' => $id,
                    'action' => 'generate_product_description'
                ]);

                return response()->json(['message' => 'Product not found'], 404);
            }

            $openAi = new OpenAi(env('OPENAI_API_KEY'));

            $prompt = "Generate a compelling marketing description for a product named '{$product->name}' with the following features: {$product->description}";

            $response = $openAi->completion([
                'model' => 'text-davinci-003',
                'prompt' => $prompt,
                'max_tokens' => 100,
            ]);

            $result = json_decode($response, true);
            $generatedDescription = $result['choices'][0]['text'] ?? 'No description generated';

            Log::info('AI-generated description for product', [
                'product_id' => $id,
                'generated_description' => $generatedDescription,
                'action' => 'generate_product_description'
            ]);

            return response()->json([
                'product_id' => $id,
                'generated_description' => $generatedDescription
            ]);

        } catch (\Exception $e) {
            Log::error('AI processing error', [
                'error' => $e->getMessage(),
                'action' => 'generate_product_description'
            ]);

            return response()->json(['message' => 'AI processing error'], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/products/{id}/recommendations",
     *     tags={"AI"},
     *     summary="Get AI-powered product recommendations",
     *     security={{"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Product ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Recommendations generated successfully"),
     *     @OA\Response(response=404, description="Product not found"),
     *     @OA\Response(response=500, description="AI processing error")
     * )
     */
    /**
     * Generate AI-powered product recommendations.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function recommendProducts($id)
    {
        try {
            $product = Product::find($id);

            if (!$product) {
                Log::error('Product not found for recommendations', [
                    'product_id' => $id,
                    'action' => 'recommend_products'
                ]);

                return response()->json(['message' => 'Product not found'], 404);
            }

            $openAi = new OpenAi(env('OPENAI_API_KEY'));

            $prompt = "Based on the following product: '{$product->name}', '{$product->description}', recommend three similar products from our inventory.";

            $response = $openAi->completion([
                'model' => 'text-davinci-003',
                'prompt' => $prompt,
                'max_tokens' => 150,
            ]);

            $result = json_decode($response, true);
            $recommendations = $result['choices'][0]['text'] ?? 'No recommendations generated';

            Log::info('AI-generated recommendations', [
                'product_id' => $id,
                'recommendations' => $recommendations,
                'action' => 'recommend_products'
            ]);

            return response()->json([
                'product_id' => $id,
                'recommendations' => $recommendations
            ]);

        } catch (\Exception $e) {
            Log::error('AI recommendation error', [
                'error' => $e->getMessage(),
                'action' => 'recommend_products'
            ]);

            return response()->json(['message' => 'Recommendation generation failed'], 500);
        }}
}
