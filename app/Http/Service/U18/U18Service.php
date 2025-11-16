<?php

namespace App\Http\Service\U18;

use App\Http\Connector\U18\U18Interface;

/**
 * Class U18Service
 *
 * Service layer orchestrating Under-18 workflow.
 */
class U18Service
{
    /** @var U18Interface */
    private U18Interface $repo;

    /**
     * U18Service constructor.
     *
     * @param U18Interface $repo
     */
    public function __construct(U18Interface $repo)
    {
        $this->repo = $repo;
    }

    /** @inheritDoc */
    public function apply(array $request, $user): object
    {
        return $this->repo->apply($request, $user);
    }

    /** @inheritDoc */
    public function status($user): object
    {
        return $this->repo->status($user);
    }

    /** @inheritDoc */
    public function pending(array $request): object
    {
        return $this->repo->pending($request);
    }

    /** @inheritDoc */
    public function approve(int $approvalId, $reviewer): object
    {
        return $this->repo->approve($approvalId, $reviewer);
    }

    /** @inheritDoc */
    public function reject(int $approvalId, $reviewer, string $notes = ''): object
    {
        return $this->repo->reject($approvalId, $reviewer, $notes);
    }

    /** @inheritDoc */
    public function listU18Matches(array $request): object
    {
        return $this->repo->listU18Matches($request);
    }
}
