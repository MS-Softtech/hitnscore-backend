<?php
// app/Http/Service/Corporate/CorporateService.php
namespace App\Http\Service\Corporate;

use App\Http\Connector\Corporate\CorporateInterface;

/**
 * Class CorporateService
 *
 * Service layer for corporate ads & enrollment flows.
 */
class CorporateService
{
    /** @var CorporateInterface */
    private CorporateInterface $repo;

    /**
     * @param CorporateInterface $repo
     */
    public function __construct(CorporateInterface $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Delegate to repository to list corporate ads.
     *
     * @param array $request
     * @return object
     */
    public function listCorporateAds(array $request): object
    {
        return $this->repo->listCorporateAds($request);
    }

    /**
     * Enroll a team into a corporate tournament.
     *
     * @param array $request
     * @param mixed $user
     * @return object
     */
    public function enrollTeam(array $request, $user): object
    {
        return $this->repo->enrollTeam($request, $user);
    }
}
