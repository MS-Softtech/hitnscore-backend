<?php
namespace App\Http\Connector\Auth;

/**
 * Interface AuthSocialServiceInterface
 * Service contract for social authentication flows.
 */
interface AuthSocialServiceInterface
{
    /**
     * Login via social provider and return [JWT, user array].
     *
     * @param string      $provider  'google'|'facebook'
     * @param string|null $token     OAuth access_token (Google/Facebook)
     * @param string|null $idToken   Google id_token (optional)
     * @return array{0:string,1:array}
     */
    public function loginWithProvider(string $provider, ?string $token, ?string $idToken): array;
}
