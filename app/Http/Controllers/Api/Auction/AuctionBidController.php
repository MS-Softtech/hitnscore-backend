<?php
// app/Http/Controllers/Api/Auction/AuctionBidController.php
namespace App\Http\Controllers\Api\Auction;

use App\Http\Controllers\Controller;
use App\Http\Service\Auction\AuctionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Class AuctionBidController
 * Handles bid placement.
 */
class AuctionBidController extends Controller
{
    private AuctionService $service;

    public function __construct(AuctionService $service)
    {
        $this->service = $service;
    }

    /**
     * POST /api/auctions/bid
     * Body: {auction_id, player_id, team_id, bid_amount}
     */
    public function placeBid(Request $request): JsonResponse
    {
        $user = Auth::user();
        return response()->json($this->service->placeBid($request->all(), $user));
    }
}
