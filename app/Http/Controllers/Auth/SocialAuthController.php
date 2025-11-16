<?php
namespace App\Http\Controllers\Auth;

use App\Http\Connector\Auth\AuthSocialServiceInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Class SocialAuthController
 * Handles social login requests from clients (Flutter).
 */
class SocialAuthController extends Controller
{
    public function __construct(private readonly AuthSocialServiceInterface $service) {}
    

    /**
     * POST /api/auth/social-login
     * Body: { provider: 'google'|'facebook', token?: string, id_token?: string }
     */
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'provider' => 'required|in:google,facebook',
            'token'    => 'nullable|string',   // Google/FB access_token
            'id_token' => 'nullable|string',   // Google id_token (optional)
        ]);

        [$jwt, $user] = $this->service->loginWithProvider(
            $data['provider'],
            $data['token'] ?? null,
            $data['id_token'] ?? null
        );

         return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data'    => [
                'user'  => $user,
                'token' => $jwt,
                'expires_at' => $jwt['expires_at'],
            ]
        ]);
    }
}
