<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use OpenAI\OpenAI;

class AIController extends Controller
{
    /**
     * Generate a product description using AI.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateProductDescription($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $apiKey = env('OPENAI_API_KEY');
        $client = new \OpenAI\Client($apiKey);

        $prompt = "Create a catchy, marketing-focused description for a product named '{$product->name}' with the following features: {$product->description}";

        $response = $client->completions()->create([
            'model' => 'text-davinci-003',
            'prompt' => $prompt,
            'max_tokens' => 100,
        ]);

        $description = $response['choices'][0]['text'];

        return response()->json([
            'product_id' => $id,
            'generated_description' => $description
        ]);
    }
}
