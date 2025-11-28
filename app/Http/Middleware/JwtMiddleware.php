<?php

namespace App\Http\Middleware;

use App\Http\Utils\JwtUtils;
use App\Models\User;
use Closure;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Class JwtMiddleware
 *
 * Authenticates requests based on a JWT issued by JwtUtils.
 * Expects header: Authorization: Bearer <token>
 */
class JwtMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            $authHeader = $request->header('Authorization');

            if (!$authHeader || !preg_match('/Bearer\s+(.+)/i', $authHeader, $matches)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing Authorization Bearer token',
                ], 401);
            }

            $token = trim($matches[1]);

            // Decode & verify using your utility
            $payload = JwtUtils::verify($token);

            // Expect user id in 'sub'
            $userId = $payload->sub ?? null;
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid token payload (sub missing)',
                ], 401);
            }

            /** @var User|null $user */
            $user = User::find($userId);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found for token',
                ], 401);
            }

            // Attach user to current request (default guard)
            Auth::login($user);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid token: ' . $e->getMessage(),
            ], 401);
        }

        return $next($request);
    }
}
