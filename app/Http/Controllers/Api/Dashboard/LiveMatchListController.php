<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Service\Dashboard\LiveMatchListService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Class LiveMatchListController
 *
 * Exposes the API to fetch compact "Live Matches" list for the dashboard.
 * Uses matches, teams, match_scores to build: title, venue, score, live-flag.
 */
class LiveMatchListController extends Controller
{
    /**
     * @var LiveMatchListService
     */
    private LiveMatchListService $service;

    /**
     * LiveMatchListController constructor.
     *
     * @param LiveMatchListService $service
     */
    public function __construct(LiveMatchListService $service)
    {
        $this->service = $service;
    }

    /**
     * GET /api/dashboard/live-match-list
     *
     * Query params (optional):
     * - limit (int, default 20)
     * - city (string)
     * - match_type (TNPL|WCKT|Local|Turf|Short Over|Test)
     * - category (General|Corporate|Under18)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {   
          $user = Auth::user();
        $result = $this->service->getLiveMatchList($request->all(), $user);

        return response()->json($result);
    }
}
