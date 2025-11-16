<?php
// app/Http/Controllers/Api/Referral/ReferralController.php
namespace App\Http\Controllers\Api\Referral;

use App\Http\Controllers\Controller;
use App\Http\Service\Referral\ReferralService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Class ReferralController
 *
 * Exposes APIs for the dashboard referral widget:
 * - summary(): current code, share links, earnings, milestones, rules.
 * - newCode(): create a new unique referral code for the user.
 */
class ReferralController extends Controller
{
    private ReferralService $service;

    public function __construct(ReferralService $service)
    {
        $this->service = $service;
    }

    /**
     * GET /api/dashboard/referral/summary
     *
     * @return JsonResponse
     */
    public function summary(): JsonResponse
    {
        $user = Auth::guard('users')->user();
        return response()->json($this->service->getSummary($user));
    }

    /**
     * POST /api/referral/new-code
     *
     * @return JsonResponse
     */
    public function newCode(): JsonResponse
    {
        $user = Auth::guard('users')->user();
        return response()->json($this->service->generateNewCode($user));
    }
}
