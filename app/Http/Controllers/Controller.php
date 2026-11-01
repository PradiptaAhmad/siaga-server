<?php

namespace App\Http\Controllers;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    public function successResponse(
        mixed $data = null,
        string $responseMessage = 'successfully retrieved data',
        int $statusCode = 200,
        array $additionalData = []
    ) {
        $response = [
            'success' => true,
            'message' => $responseMessage,
        ];

        if (! empty($additionalData)) {
            $response = array_merge($response, $additionalData);
        }

        if (isset($data)) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a error response
     *
     * @param  string|Closure  $message
     * @param  int  $code
     * @param  array  $additional
     * @return JsonResponse
     */
    public function errorResponse($message, $code = 400, $additional = [])
    {
        return response()->json(
            array_merge([
                'success' => false,
                'message' => $message,
            ], $additional),
            $code
        );
    }

    /**
     * Check if the request is invalid
     */
    public function isInvalidRequest(Request $request, string $except): bool
    {
        if (isset($except)) {
            return count($request->all()) > 1 && ! $request->has($except);
        }

        return count($request->all()) > 1;
    }
}
