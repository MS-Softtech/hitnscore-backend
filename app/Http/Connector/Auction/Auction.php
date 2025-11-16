<?php
namespace App\Http\Connector\Auction;

/**
 * Interface AuctionInterface
 *
 * Contracts for reading auction data and placing bids.
 */
interface AuctionInterface
{
    /**
     * List all "live" auction player cards.
     * @param array $request {limit?:int}
     * @return object
     */
    public function listLivePlayers(array $request): object;

    /**
     * List players for a specific auction (any status).
     * @param int $auctionId
     * @param array $request {limit?:int}
     * @return object
     */
    public function listAuctionPlayers(int $auctionId, array $request): object;

    /**
     * Get one player auction card by auction_players.id
     * @param int $playerId
     * @return object
     */
    public function getPlayerCard(int $playerId): object;

    /**
     * Place a bid for a player.
     * @param array $request {auction_id:int, player_id:int, team_id:int, bid_amount:float}
     * @param mixed $user
     * @return object
     */
    public function placeBid(array $request, $user): object;
}
