<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OpenclawApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = config('services.openclaw.api_key');

        if (! $apiKey) {
            return response()->json([
                'message' => 'Openclaw API key is not configured.',
            ], 500);
        }

        $provided = $request->header('X-Api-Key');

        if (! $provided) {
            $authorization = $request->header('Authorization', '');

            if (str_starts_with($authorization, 'Bearer ')) {
                $provided = trim(substr($authorization, 7));
            }
        }

        if (! $provided || ! hash_equals($apiKey, $provided)) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 401);
        }

        return $next($request);
    }
}
