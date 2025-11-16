<?php

namespace App\Http\Utils;

use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Config;

/**
 * Class JwtUtils
 *
 * Minimal JWT issue/verify utilities (HS256).
 */
class JwtUtils
{
    /**
     * Create a JWT for the given user id.
     *
     * @param User $user Authenticated user.
     * @return array{token:string,expires_at:int} Signed token and epoch expiry.
     */
    public static function issue(User $user): array
    {
        $now   = time();
        $ttl   = (int) (env('JWT_TTL_MIN', 10080)) * 60; // minutes → seconds
        $exp   = $now + $ttl;

        $payload = [
            'iss' => config('app.url', 'http://localhost'),
            'aud' => config('app.url', 'http://localhost'),
            'iat' => $now,
            'nbf' => $now,
            'exp' => $exp,
            'sub' => $user->id,
            'email' => $user->email,
            'role'  => $user->role,
        ];

        $secret = env('JWT_SECRET');
        $token  = JWT::encode($payload, $secret, 'HS256');

        return ['token' => $token, 'expires_at' => $exp];
    }

    /**
     * Verify a JWT and return the decoded payload.
     *
     * @param string $jwt Bearer token without the "Bearer " prefix.
     * @return object Decoded claims.
     */
    public static function verify(string $jwt): object
    {
        $secret = env('JWT_SECRET');
        return JWT::decode($jwt, new Key($secret, 'HS256'));
    }
}
