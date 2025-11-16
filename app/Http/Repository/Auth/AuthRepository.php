<?php

namespace App\Http\Repository\Auth;

use App\Http\Connector\Auth\AuthInterface;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Class AuthRepository
 *
 * Low-level data access for user registration and login.
 * Uses `users` table and its unique constraints for email/mobile (see schema).
 */
class AuthRepository implements AuthInterface
{
    /**
     * @inheritDoc
     */
    public function register(array $data): User
    {
        // Using transaction to be safe for future side effects (device_tokens, prefs).
        return DB::transaction(function () use ($data) {
            $user = new User();
            $user->name          = $data['name'];
            $user->email         = $data['email'];
            $user->mobile_number = $data['mobile_number'] ?? null;
            $user->dob           = $data['dob'] ?? null;
            $user->gender        = $data['gender'] ?? null;
            $user->pincode       = $data['pincode'] ?? null;
            $user->role          = $data['role'] ?? 'user';   // default per schema
            $user->status        = 'active';
            $user->password      = Hash::make($data['password']); // bcrypt

            $user->save();

            return $user;
        });
    }

    /**
     * @inheritDoc
     */
    public function attempt(string $login, string $password): ?User
    {
        // Accept email or mobile_number as login input.
        $user = User::query()
            ->where('email', $login)
            ->orWhere('mobile_number', $login)
            ->first();

        if (!$user) {
            return null;
        }

        return Hash::check($password, $user->password) ? $user : null;
    }

    /**
     * @inheritDoc
     */
    public function findById(int $id): ?User
    {
        return User::find($id);
    }

    /**
     * @inheritDoc
     */
    public function touchLastLogin(int $id): void
    {
        User::whereKey($id)->update(['last_login_at' => now()]);
    }
}
