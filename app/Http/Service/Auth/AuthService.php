<?php

namespace App\Http\Service\Auth;

use App\Http\Connector\Auth\AuthInterface;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

/**
 * Class AuthService
 *
 * Encapsulates validation + business rules for register/login.
 */
class AuthService
{
    /** @var AuthInterface */
    private AuthInterface $repo;

    /**
     * AuthService constructor.
     *
     * @param AuthInterface $repo Repository implementation.
     */
    public function __construct(AuthInterface $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Validate and register a new user.
     *
     * @param array<string,mixed> $input Request data.
     * @return User
     */
    public function register(array $input): User
    {
        // Validation per SRS (name, email unique, mobile pattern, pincode, password rules, min age 13)
        $v = Validator::make($input, [
            'name'          => ['required','string','max:100'],
            'email'         => ['required','email','max:100','unique:users,email'],
            'mobile_number' => ['nullable','regex:/^[6-9]\d{9}$/','unique:users,mobile_number'],
            'pincode'       => ['nullable','regex:/^\d{6}$/'],
            'gender'        => ['nullable','in:Male,Female,Other'],
            'dob'           => ['nullable','date','before_or_equal:'.now()->subYears(13)->toDateString()],
            'password'      => [
                'required','string','min:8','max:20',
                'regex:/[A-Z]/','regex:/[a-z]/','regex:/\d/','regex:/[!@#$^&*~]/'
            ],
            'accept_terms'  => ['required','accepted'],
        ], [
            'dob.before_or_equal' => 'You must be at least 13 years old.',
        ]);

        if ($v->fails()) {
            throw new InvalidArgumentException($v->errors()->first());
        }

        return $this->repo->register($input);
    }

    /**
     * Attempt login and return the authenticated user or null.
     *
     * @param string $login    Email or mobile number.
     * @param string $password Plain password.
     * @return User|null
     */
    public function login(string $login, string $password): ?User
    {
        return $this->repo->attempt($login, $password);
    }

    /**
     * Fetch the current user by id.
     *
     * @param int $id User id.
     * @return User|null
     */
    public function me(int $id): ?User
    {
        return $this->repo->findById($id);
    }

    /**
     * Touch last-login time after successful auth.
     *
     * @param int $id User id.
     * @return void
     */
    public function touchLastLogin(int $id): void
    {
        $this->repo->touchLastLogin($id);
    }
}
