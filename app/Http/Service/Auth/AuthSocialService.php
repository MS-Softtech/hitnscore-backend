<?php
namespace App\Http\Service\Auth;

use App\Http\Connector\Auth\AuthSocialRepositoryInterface;
use App\Http\Connector\Auth\AuthSocialServiceInterface;
use App\Http\Utils\JwtUtils;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

/**
 * Class AuthSocialService
 * Verifies provider tokens, normalizes profile, and issues JWT.
 */
class AuthSocialService implements AuthSocialServiceInterface
{
    public function __construct(private readonly AuthSocialRepositoryInterface $repo) {}

    /** @inheritDoc */
    public function loginWithProvider(string $provider, ?string $token, ?string $idToken): array
    {
        $profile = match ($provider) {
            'google'   => $this->fetchGoogleProfile(accessToken: $token, idToken: $idToken),
            'facebook' => $this->fetchFacebookProfile(accessToken: $token),
            default    => throw ValidationException::withMessages(['provider' => 'Unsupported']),
        };

        $user = $this->repo->upsertSocialUser($provider, $profile);

         $jwt = JwtUtils::issue($user);

        return [$jwt, [
            'id'     => $user->id,
            'name'   => $user->name,
            'email'  => $user->email,
            'avatar' => $user->avatar,
        ]];
    }

    /**
     * Verify Google token and return normalized profile.
     * Supports either id_token (preferred) or access_token (web flow).
     */
    private function fetchGoogleProfile(?string $accessToken, ?string $idToken): array
    {
        // 1) id_token path (One-Tap / native) → validate via Google tokeninfo
        if ($idToken) {
            $resp = Http::get('https://oauth2.googleapis.com/tokeninfo', ['id_token' => $idToken]);
            if (!$resp->ok()) throw ValidationException::withMessages(['token' => 'Invalid Google id_token']);

            $p = $resp->json();
            // Optional: enforce audience
            $aud = $p['aud'] ?? '';
            if ($aud !== config('services.google.web_client_id', env('GOOGLE_WEB_CLIENT_ID'))) {
                throw ValidationException::withMessages(['token' => 'Google token audience mismatch']);
            }


            return [
                'id'              => $p['sub'] ?? '',
                'email'           => $p['email'] ?? null,
                'name'            => $p['name'] ?? ($p['email'] ?? 'Google User'),
                'avatar'          => $p['picture'] ?? null,
                'email_verified'  => filter_var($p['email_verified'] ?? false, FILTER_VALIDATE_BOOL),
            ];
        }

        // 2) access_token path (Flutter Web’s token client) → call userinfo
        if ($accessToken) {
            $resp = Http::withToken($accessToken)
                ->get('https://www.googleapis.com/oauth2/v3/userinfo');
            if (!$resp->ok()) {
                // fallback endpoint
                $resp = Http::withToken($accessToken)
                    ->get('https://www.googleapis.com/oauth2/v1/userinfo', ['alt' => 'json']);
            }
            if (!$resp->ok()) throw ValidationException::withMessages(['token' => 'Invalid Google access_token']);

            $p = $resp->json();
            return [
                'id'              => $p['sub'] ?? $p['id'] ?? '',
                'email'           => $p['email'] ?? null,
                'name'            => $p['name'] ?? ($p['email'] ?? 'Google User'),
                'avatar'          => $p['picture'] ?? null,
                'email_verified'  => filter_var($p['email_verified'] ?? $p['verified_email'] ?? false, FILTER_VALIDATE_BOOL),
            ];
        }

        throw ValidationException::withMessages(['token' => 'Missing Google token']);
    }

    /**
     * Verify Facebook access token and return normalized profile.
     */
    private function fetchFacebookProfile(?string $accessToken): array
    {
        if (!$accessToken) {
            throw ValidationException::withMessages(['token' => 'Missing Facebook token']);
        }

        $appId  = env('FACEBOOK_APP_ID');
        $secret = env('FACEBOOK_APP_SECRET');

        // Validate the token belongs to our app
        $debug = Http::get('https://graph.facebook.com/debug_token', [
            'input_token'  => $accessToken,
            'access_token' => "{$appId}|{$secret}",
        ]);

        $dbg = $debug->json('data', []);
        if (!$debug->ok() || !($dbg['is_valid'] ?? false) || ($dbg['app_id'] ?? '') !== $appId) {
            throw ValidationException::withMessages(['token' => 'Invalid Facebook token']);
        }

        // Fetch profile
        $me = Http::get('https://graph.facebook.com/me', [
            'fields'       => 'id,name,email,picture.width(400)',
            'access_token' => $accessToken,
        ]);
        if (!$me->ok()) throw ValidationException::withMessages(['token' => 'Facebook profile fetch failed']);

        $p = $me->json();

        return [
            'id'              => $p['id'] ?? '',
            'email'           => $p['email'] ?? null,
            'name'            => $p['name'] ?? 'Facebook User',
            'avatar'          => $p['picture']['data']['url'] ?? null,
            'email_verified'  => !empty($p['email']), // FB does not expose verification flag here
        ];
    }
}
