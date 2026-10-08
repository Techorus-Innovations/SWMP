<?php

namespace App\Http\Controllers\Api\V1\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

trait ApiResponses
{
    /**
     * Upstream SAP failure (OpenAPI BadGateway). Details are logged, never exposed.
     */
    protected function serviceUnavailable(string $context, ?\Throwable $e = null, array $extra = []): JsonResponse
    {
        Log::error("Partner API [{$context}]: upstream SAP failure", array_merge($extra, [
            'error' => $e?->getMessage(),
        ]));

        return response()->json([
            'message' => 'Service not available.',
        ], 502);
    }

    /**
     * Unexpected local failure. Details are logged, never exposed.
     */
    protected function serverError(string $context, \Throwable $e): JsonResponse
    {
        Log::error("Partner API [{$context}]: internal error - {$e->getMessage()}", [
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'message' => 'Internal server error.',
        ], 500);
    }
}
