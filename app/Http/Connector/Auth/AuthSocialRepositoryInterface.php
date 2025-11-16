<?php
namespace App\Http\Connector\Auth;

use App\Models\User;

/**
 * Interface AuthSocialRepositoryInterface
 * Data access for social-auth related user operations.
 */
interface AuthSocialRepositoryInterface
{
    /**
     * Find or create a user by provider profile.
     *
     * @param string $provider      'google'|'facebook'
     * @param array  $profile       ['id','email','name','avatar','email_verified'=>bool]
     * @return User
     */
    public function upsertSocialUser(string $provider, array $profile): User;
}
