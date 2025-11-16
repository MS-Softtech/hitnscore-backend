<?php

namespace App\Http\Connector\U18;

/**
 * Interface U18Interface
 *
 * Contract for Under-18 profile workflow and dashboard listing.
 */
interface U18Interface
{
    /**
     * Submit an Under-18 verification request.
     * Either accepts a file upload for birth certificate or an existing media_asset_id.
     *
     * @param array $request
     * @param mixed $user
     * @return object {success, data, message}
     */
    public function apply(array $request, $user): object;

    /**
     * Get current user Under-18 status:
     * - eligible_by_dob (bool)
     * - approval_status: not_applied|pending|approved|rejected
     * - approval_id (nullable)
     * - birth_certificate_media (nullable)
     *
     * @param mixed $user
     * @return object
     */
    public function status($user): object;

    /**
     * Admin: list pending U18 approvals.
     *
     * @param array $request {q, page, size}
     * @return object
     */
    public function pending(array $request): object;

    /**
     * Admin: approve one U18 request.
     *
     * @param int   $approvalId
     * @param mixed $reviewer
     * @return object
     */
    public function approve(int $approvalId, $reviewer): object;

    /**
     * Admin: reject one U18 request (with notes).
     *
     * @param int    $approvalId
     * @param mixed  $reviewer
     * @param string $notes
     * @return object
     */
    public function reject(int $approvalId, $reviewer, string $notes = ''): object;

    /**
     * Dashboard: list Under-18 matches (upcoming & live).
     * Uses tables: matches, teams, match_scores.
     *
     * @param array $request {limit, onlyUpcoming(bool), city}
     * @return object
     */
    public function listU18Matches(array $request): object;
}
