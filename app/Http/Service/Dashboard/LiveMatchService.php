<?php

namespace App\Http\Service\Dashboard;

use App\Http\Connector\Dashboard\LiveMatchInterface;

/**
 * Class LiveMatchService
 *
 * Service layer for live matches used in dashboard carousel.
 */
class LiveMatchService
{
    /**
     * @var LiveMatchInterface
     */
    private LiveMatchInterface $liveMatch;

    /**
     * LiveMatchService constructor.
     *
     * @param LiveMatchInterface $liveMatch
     */
    public function __construct(LiveMatchInterface $liveMatch)
    {
        $this->liveMatch = $liveMatch;
    }

    /**
     * Get live matches for dashboard carousel.
     *
     * @param array $request
     * @param mixed $user
     * @return object
     */
    public function getLiveMatches(array $request, $user): object
    {
        return $this->liveMatch->getLiveMatches($request, $user);
    }
}
