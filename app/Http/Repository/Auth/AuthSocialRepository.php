<?php
namespace App\Http\Repository\Auth;

use App\Http\Connector\Auth\AuthSocialRepositoryInterface;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Class AuthSocialRepository
 * Concrete repository for social-auth user persistence.
 */
class AuthSocialRepository implements AuthSocialRepositoryInterface
{
    /** @inheritDoc */
    public function upsertSocialUser(string $provider, array $profile): User
    {
        $providerId = (string)($profile['id'] ?? '');
        $email      = strtolower((string)($profile['email'] ?? ''));

        // 1) Try by provider_id
        $query = User::query()->where('provider', $provider)->where('provider_id', $providerId);
        $user  = $query->first();

        // 2) Or by email (if present)
        if (!$user && $email) {
            $user = User::where('email', $email)->first();
        }

        // 3) Create if missing
        if (!$user) {
            $user = new User();
            $user->name  = $profile['name'] ?? 'User';
            $user->email = $email ?: null;
            // random unusable password (not used for social)
            $user->password = bcrypt(Str::random(40));
        }

        // 4) Update social fields
        $user->provider    = $provider;
        $user->provider_id = $providerId;
        $user->avatar      = $profile['avatar'] ?? $user->avatar;

        if (($profile['email_verified'] ?? false) && $user->email && !$user->email_verified_at) {
            $user->email_verified_at = now();
        }

        $user->save();
        return $user;
    }
}
