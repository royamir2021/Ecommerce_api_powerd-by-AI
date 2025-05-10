<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class ErrorLogger
{
    public static function logError(string $message, array $context = [])
    {
        Log::error($message, $context);
    }
}
