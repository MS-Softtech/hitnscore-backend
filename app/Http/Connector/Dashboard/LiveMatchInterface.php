<?php

namespace App\Http\Connector\Dashboard;

/**
 * Interface LiveMatchInterface
 *
 * Contract for fetching dashboard live match data.
 */
interface LiveMatchInterface
{
    /**
     * Get live matches for dashboard carousel.
     *
     * @param array $request Request data / filters.
     * @param mixed $user    Authenticated user context.
     * @return object        Result object with success, data, message.
     */
    public function getLiveMatches(array $request, $user): object;
}
