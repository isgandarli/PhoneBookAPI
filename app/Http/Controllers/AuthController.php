<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\JwtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(
        private JwtService $jwtService,
    ) {}

    /**
     * POST /api/auth/login
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('username', $request->input('username'))->first();

        if (!$user || !Hash::check($request->input('password'), $user->password)) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid username or password.',
            ], 401);
        }

        $token = $this->jwtService->encode($user);

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ]);
    }

    /**
     * POST /api/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Successfully logged out.',
        ]);
    }

    /**
     * POST /api/auth/refresh
     */
    public function refresh(Request $request): JsonResponse
    {
        $token = $this->extractBearerToken($request);

        if ($token === null) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Token not provided.',
            ], 401);
        }

        $payload = $this->jwtService->decodeForRefresh($token);

        if ($payload === null) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Token cannot be refreshed.',
            ], 401);
        }

        $user = User::find($payload['sub']);

        if ($user === null) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'User not found.',
            ], 401);
        }

        $newToken = $this->jwtService->encode($user);

        return response()->json([
            'access_token' => $newToken,
            'token_type' => 'Bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ]);
    }

    /**
     * GET /api/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'id' => $user->id,
            'username' => $user->username,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'full_name' => $user->full_name,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ]);
    }

    private function extractBearerToken(Request $request): ?string
    {
        $header = $request->header('Authorization', '');

        if (str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }

        return null;
    }
}
