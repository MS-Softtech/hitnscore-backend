<?php

namespace App\Http\Service\Dashboard;

use App\Http\Connector\Dashboard\LiveMatchListInterface;

/**
 * Class LiveMatchListService
 *
 * Service layer for compact "Live Matches" (dashboard cards).
 */
class LiveMatchListService
{
    /**
     * @var LiveMatchListInterface
     */
    private LiveMatchListInterface $repo;

    /**
     * LiveMatchListService constructor.
     *
     * @param LiveMatchListInterface $repo
     */
    public function __construct(LiveMatchListInterface $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Delegate to repository.
     *
     * @param array $request
     * @param mixed $user
     * @return object
     */
    public function getLiveMatchList(array $request, $user): object
    {
        return $this->repo->getLiveMatchList($request, $user);
    }
}
