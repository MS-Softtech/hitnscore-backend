<?php

namespace App\Http\Service\Addon;

use App\Http\Connector\Addon\AddonInterface;

/**
 * Class AddonService
 *
 * Service layer for Add-ons, delegating to repository.
 */
class AddonService
{
    /** @var AddonInterface */
    private AddonInterface $repo;

    /**
     * AddonService constructor.
     *
     * @param AddonInterface $repo
     */
    public function __construct(AddonInterface $repo)
    {
        $this->repo = $repo;
    }

    /**
     * List Add-ons for the dashboard card.
     *
     * @param array $request
     * @param mixed $user
     * @return object
     */
    public function listAddons(array $request, $user): object
    {
        return $this->repo->listAddons($request, $user);
    }
}
