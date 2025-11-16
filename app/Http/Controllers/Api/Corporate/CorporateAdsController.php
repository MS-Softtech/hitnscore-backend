<?php
// app/Http/Controllers/Api/Corporate/CorporateAdsController.php
namespace App\Http\Controllers\Api\Corporate;

use App\Http\Controllers\Controller;
use App\Http\Service\Corporate\CorporateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class CorporateAdsController
 *
 * Exposes the dashboard “Corporate Matches – Registration Open” list.
 */
class CorporateAdsController extends Controller
{
    /** @var CorporateService */
    private CorporateService $service;

    /**
     * CorporateAdsController constructor.
     *
     * @param CorporateService $service
     */
    public function __construct(CorporateService $service)
    {
        $this->service = $service;
    }

    /**
     * GET /api/dashboard/corporate/ads
     *
     * Query (optional):
     * - limit: int (default 5)
     * - city: string
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json($this->service->listCorporateAds($request->all()));
    }
}
