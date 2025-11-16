<?php

namespace App\Http\Connector\Auth;

use App\Models\User;

/**
 * Interface AuthInterface
 *
 * Contract for Authentication repository.
 */
interface AuthInterface
{
    /**
     * Persist a new user using validated data.
     *
     * @param array<string,mixed> $data Validated registration input.
     * @return User Newly created user entity.
     */
    public function register(array $data): User;

    /**
     * Return the user for valid credentials; null otherwise.
     *
     * @param string $login Email or mobile number.
     * @param string $password Plain password to be verified.
     * @return User|null Authenticated user or null.
     */
    public function attempt(string $login, string $password): ?User;

    /**
     * Find user by id.
     *
     * @param int $id User id.
     * @return User|null
     */
    public function findById(int $id): ?User;

    /**
     * Update last login timestamp.
     *
     * @param int $id User id.
     * @return void
     */
    public function touchLastLogin(int $id): void;
}
