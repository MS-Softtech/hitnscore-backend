<?php
// app/Http/Connector/Referral/ReferralInterface.php
namespace App\Http\Connector\Referral;

/**
 * Interface ReferralInterface
 *
 * Contract for referral summary & code management.
 */
interface ReferralInterface
{
    /**
     * Build the dashboard referral summary for a user.
     *
     * @param object $user Authenticated user.
     * @return object {success, data, message}
     */
    public function getSummary(object $user): object;

    /**
     * Generate and persist a new unique referral code for the user.
     *
     * @param object $user
     * @return object {success, data:{referral_code}, message}
     */
    public function generateNewCode(object $user): object;
}
