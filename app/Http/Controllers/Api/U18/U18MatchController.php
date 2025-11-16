<?php

namespace App\Http\Controllers\Api\U18;

use App\Http\Controllers\Controller;
use App\Http\Service\U18\U18Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class U18MatchController
 *
 * Dashboard section for Under-18 matches (live/upcoming).
 */
class U18MatchController extends Controller
{
    /** @var U18Service */
    private U18Service $service;

    /**
     * U18MatchController constructor.
     *
     * @param U18Service $service
     */
    public function __construct(U18Service $service)
    {
        $this->service = $service;
    }

    /**
     * GET /api/dashboard/u18-matches
     * Query params: limit, onlyUpcoming (bool), city
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function list(Request $request): JsonResponse
    {
        return response()->json($this->service->listU18Matches($request->all()));
    }
}
