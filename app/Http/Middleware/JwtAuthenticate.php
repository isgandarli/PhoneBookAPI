<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\JwtService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class JwtAuthenticate
{
    public function __construct(
        private JwtService $jwtService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->extractToken($request);

        if ($token === null) {
            return $this->unauthorizedResponse('Token not provided.');
        }

        try {
            $payload = $this->jwtService->decode($token);
        } catch (\RuntimeException $e) {
            return $this->unauthorizedResponse($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            return $this->unauthorizedResponse($e->getMessage());
        }

        $user = User::find($payload['sub']);

        if ($user === null) {
            return $this->unauthorizedResponse('User not found.');
        }

        $request->setUserResolver(fn () => $user);

        return $next($request);
    }

    private function extractToken(Request $request): ?string
    {
        $header = $request->header('Authorization', '');

        if (str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }

        return null;
    }

    private function unauthorizedResponse(string $message): JsonResponse
    {
        return response()->json([
            'error' => 'Unauthorized',
            'message' => $message,
        ], 401);
    }
}
