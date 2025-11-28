<?php

namespace App\Http\Controllers\Api\U18;

use App\Http\Controllers\Controller;
use App\Http\Service\U18\U18Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Class U18ProfileController
 *
 * Handles end-user Under-18 application and status checks.
 */
class U18ProfileController extends Controller
{
    /** @var U18Service */
    private U18Service $service;

    /**
     * U18ProfileController constructor.
     *
     * @param U18Service $service
     */
    public function __construct(U18Service $service)
    {
        $this->service = $service;
    }

    /**
     * Submit Under-18 verification request.
     * Accepts multipart/form-data:
     * - file (birth certificate) OR media_asset_id
     * - dob (YYYY-MM-DD) optional (will update user.dob)
     * - notes (optional)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function apply(Request $request): JsonResponse
    {
        $user = Auth::user();
        $res  = $this->service->apply($request->all() + ['file' => $request->file('file')], $user);
        return response()->json($res);
    }

    /**
     * Get Under-18 status for current user.
     *
     * @return JsonResponse
     */
    public function status(): JsonResponse
    {
        $user = Auth::user();
        return response()->json($this->service->status($user));
    }
}
