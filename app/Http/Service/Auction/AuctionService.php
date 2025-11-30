<?php
namespace App\Http\Service\Auction;

use App\Http\Connector\Auction\AuctionInterface;

/**
 * Class AuctionService
 * Service layer delegating to repository.
 */
class AuctionService
{
    private AuctionInterface $repo;

    public function __construct(AuctionInterface $repo)
    {
        $this->repo = $repo;
    }

    /** @inheritDoc */
    public function listLivePlayers(array $request): object
    {
        return $this->repo->listLivePlayers($request);
    }

    /** @inheritDoc */
    public function listAuctionPlayers(int $auctionId, array $request): object
    {
        return $this->repo->listAuctionPlayers($auctionId, $request);
    }

    /** @inheritDoc */
    public function getPlayerCard(int $playerId): object
    {
        return $this->repo->getPlayerCard($playerId);
    }

    /** @inheritDoc */
    public function placeBid(array $request, $user): object
    {
        return $this->repo->placeBid($request, $user);
    }
}
