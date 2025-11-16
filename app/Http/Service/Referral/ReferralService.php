<?php
// app/Http/Service/Referral/ReferralService.php
namespace App\Http\Service\Referral;

use App\Http\Connector\Referral\ReferralInterface;

/**
 * Class ReferralService
 *
 * Service layer for referral widget orchestration.
 */
class ReferralService
{
    private ReferralInterface $repo;

    public function __construct(ReferralInterface $repo)
    {
        $this->repo = $repo;
    }

    /** @inheritDoc */
    public function getSummary(object $user): object
    {
        return $this->repo->getSummary($user);
    }

    /** @inheritDoc */
    public function generateNewCode(object $user): object
    {
        return $this->repo->generateNewCode($user);
    }
}
