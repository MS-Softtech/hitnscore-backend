<?php

namespace App\Http\Connector\Turf;

/**
 * Interface TurfInterface
 *
 * Contract for reading turfs with slots and pricing.
 */
interface TurfInterface
{
    /**
     * List turfs with available slots and derived price_per_hour.
     *
     * @param array $request {city?, limit?, slots_limit?, from_date?}
     * @param mixed $user
     * @return object {success, data[], message}
     */
    public function listTurfs(array $request, $user): object;
}
