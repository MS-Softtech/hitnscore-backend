<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Service\Auth\AuthService;
use App\Http\Utils\JwtUtils;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Class AuthController
 *
 * HTTP layer for registration & login APIs.
 */
class AuthController extends Controller
{
    /** @var AuthService */
    private AuthService $service;

    /**
     * AuthController constructor.
     *
     * @param AuthService $service Injected service.
     */
    public function __construct(AuthService $service)
    {
        $this->service = $service;
    }

    /**
     * POST /api/auth/register
     * Create a new user account (native signup).
     *
     * Body JSON:
     * {
     *   "name": "...",
     *   "email": "...",
     *   "mobile_number": "98xxxxxxxx",
     *   "pincode": "6000xx",
     *   "gender": "Male|Female|Other",
     *   "dob": "YYYY-MM-DD",
     *   "password": "Pass@123",
     *   "accept_terms": true
     * }
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        try {
            $user = $this->service->register($request->all());
            $jwt  = JwtUtils::issue($user);

            return response()->json([
                'success' => true,
                'message' => 'Registration successful',
                'data'    => [
                    'user'   => [
                        'id'     => $user->id,
                        'name'   => $user->name,
                        'email'  => $user->email,
                        'mobile' => $user->mobile_number,
                        'role'   => $user->role,
                        'status' => $user->status,
                    ],
                    'token'  => $jwt['token'],
                    'expires_at' => $jwt['expires_at'],
                ]
            ], 201);
        } catch (InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/auth/login
     * Email or mobile + password.
     *
     * Body JSON:
     * { "login": "<email or mobile>", "password": "Pass@123" }
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $request->validate([
            'login'    => ['required','string','max:100'],
            'password' => ['required','string'],
        ]);

        $user = $this->service->login($request->input('login'), $request->input('password'));
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Invalid credentials'], 401);
        }

        $this->service->touchLastLogin($user->id);
        $jwt = JwtUtils::issue($user);

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data'    => [
                'user'  => [
                    'id'     => $user->id,
                    'name'   => $user->name,
                    'email'  => $user->email,
                    'mobile' => $user->mobile_number,
                    'role'   => $user->role,
                    'status' => $user->status,
                ],
                'token' => $jwt['token'],
                'expires_at' => $jwt['expires_at'],
            ]
        ]);
    }

    /**
     * GET /api/auth/me (requires JWT)
     * Returns the authenticated user's profile.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        $id = (int) $request->attributes->get('auth_user_id');
        $user = $this->service->me($id);

        return response()->json([
            'success' => true,
            'data'    => [
                'id'     => $user->id,
                'name'   => $user->name,
                'email'  => $user->email,
                'mobile' => $user->mobile_number,
                'role'   => $user->role,
                'status' => $user->status,
                'city'   => $user->city,
                'pincode'=> $user->pincode,
            ]
        ]);
    }
}
