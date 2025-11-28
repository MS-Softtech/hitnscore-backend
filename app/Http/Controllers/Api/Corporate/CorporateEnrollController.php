<?php
// app/Http/Controllers/Api/Corporate/CorporateEnrollController.php
namespace App\Http\Controllers\Api\Corporate;

use App\Http\Controllers\Controller;
use App\Http\Service\Corporate\CorporateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Class CorporateEnrollController
 *
 * Handles CTA “Enroll Team” to a corporate tournament.
 */
class CorporateEnrollController extends Controller
{
    /** @var CorporateService */
    private CorporateService $service;

    /**
     * @param CorporateService $service
     */
    public function __construct(CorporateService $service)
    {
        $this->service = $service;
    }

    /**
     * POST /api/corporate/enroll
     *
     * Body JSON:
     * - tournament_id: int (required)
     * - team_id: int (required)
     * - company_id: int (optional, validates via company_teams)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function enroll(Request $request): JsonResponse
    {
        $user = Auth::user();
        return response()->json($this->service->enrollTeam($request->all(), $user));
    }
}
