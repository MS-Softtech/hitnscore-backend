<?php

namespace App\Http\Controllers\Api\Turf;

use App\Http\Controllers\Controller;
use App\Http\Service\Turf\TurfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Class TurfController
 *
 * Exposes dashboard Turf listing with available slots and pricing.
 */
class TurfController extends Controller
{
    /**
     * @var TurfService
     */
    private TurfService $service;

    /**
     * TurfController constructor.
     *
     * @param TurfService $service
     */
    public function __construct(TurfService $service)
    {
        $this->service = $service;
    }

    /**
     * Get turfs with upcoming available slots (for dashboard card).
     *
     * GET /api/dashboard/turfs
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $res  = $this->service->listTurfs($request->all(), $user);
        return response()->json($res);
    }
}
