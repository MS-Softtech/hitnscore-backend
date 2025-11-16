<?php
// app/Http/Controllers/Api/Auction/AuctionReadController.php
namespace App\Http\Controllers\Api\Auction;

use App\Http\Controllers\Controller;
use App\Http\Service\Auction\AuctionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class AuctionReadController
 * Read-only endpoints for auction dashboard/cards.
 */
class AuctionReadController extends Controller
{
    private AuctionService $service;

    public function __construct(AuctionService $service)
    {
        $this->service = $service;
    }

    /** GET /api/auctions/live */
    public function livePlayers(Request $request): JsonResponse
    {
        return response()->json($this->service->listLivePlayers($request->all()));
    }

    /** GET /api/auctions/{auctionId}/players */
    public function players(int $auctionId, Request $request): JsonResponse
    {
        return response()->json($this->service->listAuctionPlayers($auctionId, $request->all()));
    }

    /** GET /api/auctions/player/{playerId} */
    public function player(int $playerId): JsonResponse
    {
        return response()->json($this->service->getPlayerCard($playerId));
    }
}
