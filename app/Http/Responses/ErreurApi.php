<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

/**
 * Une seule forme d'erreur pour toute l'API : {"message": ..., "errors": ...}
 */
class ErreurApi
{
    public static function reponse(string $message, int $code, mixed $errors = null): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'errors' => $errors ?? new \stdClass(),
        ], $code);
    }
}
