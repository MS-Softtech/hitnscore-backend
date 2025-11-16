<?php

namespace App\Http\Controllers\Api\Addon;

use App\Http\Controllers\Controller;
use App\Http\Service\Addon\AddonService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Class AddonController
 *
 * Exposes dashboard API for listing Add-ons (products shown as services/extras).
 */
class AddonController extends Controller
{
    /** @var AddonService */
    private AddonService $service;

    /**
     * AddonController constructor.
     *
     * @param AddonService $service
     */
    public function __construct(AddonService $service)
    {
        $this->service = $service;
    }

    /**
     * Get Add-ons for dashboard.
     *
     * GET /api/dashboard/addons
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::guard('users')->user();
        return response()->json($this->service->listAddons($request->all(), $user));
    }
}
