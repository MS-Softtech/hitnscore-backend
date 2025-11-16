<?php
// app/Http/Repository/Referral/ReferralRepository.php
namespace App\Http\Repository\Referral;

use App\Http\Connector\Referral\ReferralInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Class ReferralRepository
 *
 * Reads from:
 *  - referrals (referrer’s codes & referral status)
 *  - referral_rewards (earned/pending/paid amounts)
 *  - cms_pages (rules HTML via slug = 'referral-rules')
 *
 * And writes:
 *  - create a new unique referral code for user.
 */
class ReferralRepository implements ReferralInterface
{
    /**
     * {@inheritdoc}
     */
    public function getSummary(object $user): object
    {
        $ret = (object)['success' => false, 'data' => null, 'message' => ''];

        try {
            // 1) Get (or lazily provision) the user’s default referral code
            $code = $this->getOrCreateDefaultCode($user->id);

            // 2) Earnings aggregation from referral_rewards
            $agg = DB::table('referral_rewards')
                ->selectRaw("
                    SUM(CASE WHEN status='pending' THEN amount ELSE 0 END) AS pending_amount,
                    SUM(CASE WHEN status IN ('earned','paid') THEN amount ELSE 0 END) AS earned_amount,
                    SUM(CASE WHEN status='paid' THEN amount ELSE 0 END) AS paid_amount
                ")
                ->where('user_id', $user->id)
                ->first();

            // 3) Milestones from referrals status counts
            $mil = DB::table('referrals')
                ->selectRaw("
                    SUM(CASE WHEN status='invited'    THEN 1 ELSE 0 END) AS invited,
                    SUM(CASE WHEN status='registered' THEN 1 ELSE 0 END) AS registered,
                    SUM(CASE WHEN status='completed'  THEN 1 ELSE 0 END) AS completed
                ")
                ->where('user_id', $user->id)
                ->first();

            // 4) Rules HTML from cms_pages (slug = 'referral-rules')
            $rules = DB::table('cms_pages')
                ->where('slug', 'referral-rules')
                ->select(['title', 'html'])
                ->first();

            // 5) Compose share links (use APP_URL or sensible fallback)
            $base = config('app.url') ?: env('APP_URL', 'https://hitnscore.app');
            $landingUrl   = $base . '/signup?ref=' . urlencode($code);
            $shareText    = "Join HitnScore! Use my code $code to sign up: $landingUrl";
            $whatsappUrl  = 'https://wa.me/?text=' . urlencode($shareText);
            $twitterUrl   = 'https://twitter.com/intent/tweet?text=' . urlencode($shareText);
            $copyText     = $shareText;

            $ret->success = true;
            $ret->data = [
                'referral_code' => $code,
                'share' => [
                    'landing_url'  => $landingUrl,
                    'whatsapp_url' => $whatsappUrl,
                    'twitter_url'  => $twitterUrl,
                    'copy_text'    => $copyText,
                ],
                'earnings' => [
                    'pending' => (float)($agg->pending_amount ?? 0),
                    'earned'  => (float)($agg->earned_amount  ?? 0),
                    'paid'    => (float)($agg->paid_amount    ?? 0),
                    'total'   => (float)(($agg->pending_amount ?? 0) + ($agg->earned_amount ?? 0)),
                ],
                'milestones' => [
                    'invited'    => (int)($mil->invited    ?? 0),
                    'registered' => (int)($mil->registered ?? 0),
                    'completed'  => (int)($mil->completed  ?? 0),
                ],
                'rules' => $rules ? [
                    'title' => $rules->title,
                    'html'  => $rules->html,        // show in popup
                ] : null,
            ];
        } catch (\Throwable $e) {
            $ret->message = $e->getMessage();
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function generateNewCode(object $user): object
    {
        $ret = (object)['success' => false, 'data' => null, 'message' => ''];

        try {
            $new = $this->createUniqueCode($user->id);
            $ret->success = true;
            $ret->data = ['referral_code' => $new];
        } catch (\Throwable $e) {
            $ret->message = $e->getMessage();
        }

        return $ret;
    }

    /**
     * Return an existing default code for the user, or create one.
     *
     * We treat a row in `referrals` with (user_id = X AND referred_user_id IS NULL)
     * as the user's default, reusable referral code for sharing.
     *
     * @param int $userId
     * @return string
     */
    private function getOrCreateDefaultCode(int $userId): string
    {
        $row = DB::table('referrals')
            ->where('user_id', $userId)
            ->whereNull('referred_user_id')
            ->orderBy('id', 'asc')
            ->first();

        if ($row) return $row->referral_code;

        $code = $this->createUniqueCode($userId);

        DB::table('referrals')->insert([
            'user_id'          => $userId,
            'referral_code'    => $code,
            'referred_user_id' => null,
            'status'           => 'invited',
            'created_at'       => now(),
        ]);

        return $code;
    }

    /**
     * Generate a unique referral code (e.g., "HITN7ABC2").
     *
     * @param int $userId
     * @return string
     */
    private function createUniqueCode(int $userId): string
    {
        do {
            $code = 'HITN' . $userId . strtoupper(Str::random(3));
            $exists = DB::table('referrals')->where('referral_code', $code)->exists();
        } while ($exists);

        return $code;
    }
}
