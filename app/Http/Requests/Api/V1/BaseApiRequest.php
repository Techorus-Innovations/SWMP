<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class BaseApiRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Helper to encode structured error code into validator.
     */
    public static function formatError(string $code, string $message): string
    {
        return json_encode([
            'code' => $code,
            'message' => $message,
        ]);
    }

    /**
     * Abort validation with the OpenAPI BadGateway response when SAP is unreachable
     * or returns an error, so upstream outages are never reported as 422s.
     */
    protected function failUpstream(string $context, ?\Throwable $e = null): never
    {
        \Illuminate\Support\Facades\Log::error("Partner API [{$context}]: upstream SAP failure", [
            'error' => $e?->getMessage(),
        ]);

        throw new HttpResponseException(response()->json([
            'message' => 'Service not available.',
        ], 502));
    }

    /**
     * Handle a failed validation attempt with standardized error objects.
     */
    protected function failedValidation(Validator $validator)
    {
        $formattedErrors = [];

        foreach ($validator->errors()->getMessages() as $field => $messages) {
            foreach ($messages as $message) {
                $decoded = json_decode($message, true);
                if (is_array($decoded) && isset($decoded['code'], $decoded['message'])) {
                    $formattedErrors[] = [
                        'code' => $decoded['code'],
                        'field' => $field,
                        'message' => $decoded['message'],
                    ];
                } else {
                    $formattedErrors[] = [
                        'code' => 'MISSING_ATTRIBUTE',
                        'field' => $field,
                        'message' => 'Missing attribute: ' . $message,
                    ];
                }
            }
        }

        throw new HttpResponseException(response()->json([
            'message' => 'Validation failed.',
            'errors' => $formattedErrors,
        ], 422));
    }
}
