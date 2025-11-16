<?php
// app/Http/Connector/Corporate/CorporateInterface.php
namespace App\Http\Connector\Corporate;

/**
 * Interface CorporateInterface
 *
 * Contract for corporate ads and enroll operations.
 */
interface CorporateInterface
{
    /**
     * List corporate tournaments with registration open (for dashboard ads).
     *
     * @param array $request {limit, city}
     * @return object {success, data[], message}
     */
    public function listCorporateAds(array $request): object;

    /**
     * Enroll a team to a corporate tournament.
     *
     * @param array $request {tournament_id, team_id, company_id?}
     * @param mixed $user    Authenticated user context.
     * @return object
     */
    public function enrollTeam(array $request, $user): object;
}
