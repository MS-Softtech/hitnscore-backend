<?php

namespace App\Http\Middleware;

use App\Http\Service\Auth\AuthService;
use App\Http\Utils\JwtUtils;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class JwtMiddleware
 *
 * Parses Bearer token, validates it, and injects `auth_user_id` into the request.
 */
class JwtMiddleware
{
    /** @var AuthService */
    private AuthService $service;

    /**
     * JwtMiddleware constructor.
     *
     * @param AuthService $service Injected auth service.
     */
    public function __construct(AuthService $service)
    {
        $this->service = $service;
    }

    /**
     * Handle an incoming request, rejecting invalid/expired tokens.
     *
     * @param Request $request Incoming request.
     * @param Closure $next Next middleware.
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Authorization', '');
        if (!str_starts_with($header, 'Bearer ')) {
            return response()->json(['success' => false, 'message' => 'Missing Bearer token'], 401);
        }

        try {
            $claims = JwtUtils::verify(substr($header, 7));
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Invalid or expired token'], 401);
        }

        // Optional: check user and token revocation timestamp
        $user = $this->service->me((int)$claims->sub);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 401);
        }

        $request->attributes->set('auth_user_id', $user->id);
        return $next($request);
    }
}
