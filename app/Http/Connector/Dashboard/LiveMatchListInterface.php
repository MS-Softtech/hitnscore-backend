<?php

namespace App\Http\Connector\Dashboard;

/**
 * Interface LiveMatchListInterface
 *
 * Contract for fetching compact live match list for dashboard cards.
 */
interface LiveMatchListInterface
{
    /**
     * Fetch compact list of current live matches.
     *
     * @param array $request Filters like limit/city/category/match_type.
     * @param mixed $user    Authenticated user (for future personalization).
     * @return object        { success, data[], message }
     */
    public function getLiveMatchList(array $request, $user): object;
}
