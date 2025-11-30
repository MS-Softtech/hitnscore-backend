<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Service\Auth\AuthService;
use App\Http\Service\Dashboard\LiveMatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Class LiveMatchController
 *
 * Controller responsible for exposing APIs related to
 * live matches on the dashboard (carousel slider).
 */
class LiveMatchController extends Controller
{
    /**
     * @var LiveMatchService
     */
    private LiveMatchService $liveMatchService;
    private AuthService $service;

    /**
     * LiveMatchController constructor.
     *
     * @param LiveMatchService $liveMatchService
     */
    public function __construct(LiveMatchService $liveMatchService, AuthService $service)
    {
        $this->liveMatchService = $liveMatchService;
        $this->service = $service;
    }

    /**
     * Get list of live matches formatted for the dashboard carousel.
     *
     * GET /api/dashboard/live-matches
     *
     * Optional query params:
     * - city
     * - match_type
     * - category
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
          $user = Auth::user();

        $result = $this->liveMatchService->getLiveMatches($request->all(), $user);

        return response()->json($result);
    }
}
