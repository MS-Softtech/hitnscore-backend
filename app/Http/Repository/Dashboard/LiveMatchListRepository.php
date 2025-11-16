<?php

namespace App\Http\Repository\Dashboard;

use App\Http\Connector\Dashboard\LiveMatchListInterface;
use Illuminate\Support\Facades\DB;

/**
 * Class LiveMatchListRepository
 *
 * Reads live-match list from DB and shapes it for small dashboard cards.
 * Tables used (from your schema):
 *  - matches(id, team_a_id, team_b_id, venue, city, start_datetime, end_datetime, match_type, category)
 *  - teams(id, name)
 *  - match_scores(match_id, runs, wickets, overs, updated_at)
 */
class LiveMatchListRepository implements LiveMatchListInterface
{
    /**
     * See interface docs.
     *
     * @param array $request
     * @param mixed $user
     * @return object
     */
    public function getLiveMatchList(array $request, $user): object
    {
        $ret = (object)[
            'success' => false,
            'data'    => [],
            'message' => '',
        ];

        try {
            $limit = (int)($request['limit'] ?? 20);
            if ($limit <= 0 || $limit > 100) $limit = 20;

            $now = now();

            // Subquery: latest score row per match (by updated_at)
            $latestScoreSub = DB::table('match_scores as ms1')
                ->select('ms1.match_id', 'ms1.runs', 'ms1.wickets', 'ms1.overs', 'ms1.updated_at')
                ->join(
                    DB::raw('(SELECT match_id, MAX(updated_at) AS maxu FROM match_scores GROUP BY match_id) AS mx'),
                    function ($join) {
                        $join->on('mx.match_id', '=', 'ms1.match_id')
                             ->on('mx.maxu', '=', 'ms1.updated_at');
                    }
                );

            $q = DB::table('matches as m')
                ->leftJoin('teams as ta', 'ta.id', '=', 'm.team_a_id')
                ->leftJoin('teams as tb', 'tb.id', '=', 'm.team_b_id')
                ->leftJoinSub($latestScoreSub, 'ls', function ($join) {
                    $join->on('ls.match_id', '=', 'm.id');
                })
                // LIVE window: started and not yet ended
                ->where('m.start_datetime', '<=', $now)
                ->where(function ($w) use ($now) {
                    $w->whereNull('m.end_datetime')
                      ->orWhere('m.end_datetime', '>=', $now);
                });

            // Optional filters
            if (!empty($request['city'])) {
                $q->where('m.city', $request['city']);
            }
            if (!empty($request['match_type'])) {
                $q->where('m.match_type', $request['match_type']);
            }
            if (!empty($request['category'])) {
                $q->where('m.category', $request['category']);
            }

            $rows = $q->orderByDesc('m.start_datetime')
                ->limit($limit)
                ->get([
                    'm.id as match_id',
                    DB::raw("CONCAT(COALESCE(ta.name,'Team A'),' vs ',COALESCE(tb.name,'Team B')) as title"),
                    'm.venue',
                    'm.city',
                    'ls.runs',
                    'ls.wickets',
                    'ls.overs',
                ]);

            $data = [];
            foreach ($rows as $r) {
                $scoreRuns    = (int)($r->runs ?? 0);
                $scoreWkts    = (int)($r->wickets ?? 0);
                $scoreText    = $scoreRuns . '/' . $scoreWkts;
                $subtitle     = $r->venue ?: ($r->city ?: '');

                $data[] = [
                    'match_id'   => (int)$r->match_id,
                    'title'      => $r->title,          // "Team A vs Team B"
                    'subtitle'   => $subtitle,          // "Stadium 1" (or city)
                    'score_text' => $scoreText,         // "78/2"
                    'runs'       => $scoreRuns,
                    'wickets'    => $scoreWkts,
                    'overs'      => $r->overs,
                    'status'     => 'Live',
                    'is_live'    => true,
                ];
            }

            $ret->success = true;
            $ret->data    = $data;
        } catch (\Throwable $e) {
            $ret->message = $e->getMessage();
        }

        return $ret;
    }
}
