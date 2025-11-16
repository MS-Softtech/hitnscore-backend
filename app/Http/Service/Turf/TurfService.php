<?php

namespace App\Http\Service\Turf;

use App\Http\Connector\Turf\TurfInterface;

/**
 * Class TurfService
 *
 * Service layer for dashboard Turf section.
 */
class TurfService
{
    /**
     * @var TurfInterface
     */
    private TurfInterface $repo;

    /**
     * TurfService constructor.
     *
     * @param TurfInterface $repo
     */
    public function __construct(TurfInterface $repo)
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
    public function listTurfs(array $request, $user): object
    {
        return $this->repo->listTurfs($request, $user);
    }
}
