<?php

namespace App\Http\Repository\Dashboard;

use App\Http\Connector\Dashboard\LiveMatchInterface;
use Illuminate\Support\Facades\DB;

/**
 * Class LiveMatchRepository
 *
 * Repository responsible for reading live match information
 * from the database and shaping it for the dashboard carousel.
 */
class LiveMatchRepository implements LiveMatchInterface
{
    /**
     * Get live matches for dashboard carousel.
     *
     * @param array $request
     * @param mixed $user
     * @return object
     */
    public function getLiveMatches(array $request, $user): object
    {
        $return = (object)[
            'success' => false,
            'data'    => [],
            'message' => '',
        ];

        try {
            $now = now();

            // Base query to find live matches
            $query = DB::table('matches as m')
                ->leftJoin('teams as ta', 'ta.id', '=', 'm.team_a_id')
                ->leftJoin('teams as tb', 'tb.id', '=', 'm.team_b_id')
                ->leftJoin('streams as s', function ($join) {
                    $join->on('s.match_id', '=', 'm.id')
                         ->where('s.status', '=', 'live');
                })
                ->where('m.start_datetime', '<=', $now)
                ->where(function ($q) use ($now) {
                    $q->whereNull('m.end_datetime')
                      ->orWhere('m.end_datetime', '>=', $now);
                });

            // Optional filters
            if (!empty($request['city'])) {
                $query->where('m.city', $request['city']);
            }
            if (!empty($request['match_type'])) {
                $query->where('m.match_type', $request['match_type']);
            }
            if (!empty($request['category'])) {
                $query->where('m.category', $request['category']);
            }

            $matches = $query->select([
                    'm.id as match_id',
                    'm.match_type',
                    'm.category',
                    'm.gender',
                    'm.venue',
                    'm.city',
                    'm.start_datetime',
                    'm.end_datetime',
                    'ta.id as team_a_id',
                    'ta.name as team_a_name',
                    'tb.id as team_b_id',
                    'tb.name as team_b_name',
                    's.provider as stream_provider',
                    's.url as stream_url',
                ])
                ->orderBy('m.start_datetime', 'desc')
                ->limit(10)
                ->get();

            if ($matches->isEmpty()) {
                $return->success = true;
                $return->data    = [];
                $return->message = 'No live matches found.';
                return $return;
            }

            $matchIds = $matches->pluck('match_id')->all();

            // Latest score per match
            $scoreRows = DB::table('match_scores as ms')
                ->whereIn('ms.match_id', $matchIds)
                ->select([
                    'ms.match_id',
                    'ms.innings_no',
                    'ms.team_side',
                    'ms.runs',
                    'ms.wickets',
                    'ms.overs',
                    'ms.extras',
                    'ms.updated_at',
                ])
                ->orderBy('ms.match_id')
                ->orderByDesc('ms.updated_at')
                ->get()
                ->groupBy('match_id');

            // Last few balls from commentary (we will pick last 5)
            $commentaryRows = DB::table('match_commentaries as mc')
                ->whereIn('mc.match_id', $matchIds)
                ->select([
                    'mc.match_id',
                    'mc.innings_no',
                    'mc.over_no',
                    'mc.ball_no',
                    'mc.event',
                ])
                ->orderBy('mc.match_id')
                ->orderByDesc('mc.innings_no')
                ->orderByDesc('mc.over_no')
                ->orderByDesc('mc.ball_no')
                ->get()
                ->groupBy('match_id');

            $data = [];

            foreach ($matches as $row) {
                $scoreForMatch = $scoreRows->get($row->match_id)?->first();

                $runs    = $scoreForMatch->runs ?? 0;
                $wickets = $scoreForMatch->wickets ?? 0;
                $overs   = $scoreForMatch->overs ?? 0.0;
                $runRate = null;

                if ($overs > 0) {
                    $normalizedOvers = $this->normalizeOvers((float)$overs);
                    if ($normalizedOvers > 0) {
                        $runRate = round($runs / $normalizedOvers, 2);
                    }
                }

                $lastBalls = [];
                if ($commentaryRows->has($row->match_id)) {
                    $events   = $commentaryRows->get($row->match_id)->take(5)->pluck('event')->toArray();
                    // Reverse so that UI sees chronological order
                    $lastBalls = array_reverse($events);
                }

                $data[] = [
                    'match_id'   => $row->match_id,
                    'title'      => trim(($row->team_a_name ?? 'Team A') . ' vs ' . ($row->team_b_name ?? 'Team B')),
                    'match_type' => $row->match_type,
                    'category'   => $row->category,
                    'gender'     => $row->gender,
                    'venue'      => $row->venue,
                    'city'       => $row->city,
                    'start_time' => $row->start_datetime,
                    'team_a'     => [
                        'id'   => $row->team_a_id,
                        'name' => $row->team_a_name,
                    ],
                    'team_b'     => [
                        'id'   => $row->team_b_id,
                        'name' => $row->team_b_name,
                    ],
                    'score'      => [
                        'innings_no'   => $scoreForMatch->innings_no ?? null,
                        'batting_side' => $scoreForMatch->team_side ?? null,
                        'runs'         => $runs,
                        'wickets'      => $wickets,
                        'overs'        => $overs,
                        'run_rate'     => $runRate,
                        'extras'       => $scoreForMatch->extras ?? 0,
                    ],
                    'last_five_balls' => $lastBalls,
                    'stream'          => [
                        'provider' => $row->stream_provider,
                        'url'      => $row->stream_url,
                    ],
                ];
            }

            $return->success = true;
            $return->data    = $data;
            $return->message = '';
        } catch (\Throwable $e) {
            $return->success = false;
            $return->data    = [];
            $return->message = $e->getMessage();
        }

        return $return;
    }

    /**
     * Convert cricket overs notation (e.g. 38.2) into a decimal value
     * (e.g. 38.3333) for run-rate calculation.
     *
     * @param float $overs
     * @return float
     */
    private function normalizeOvers(float $overs): float
    {
        $fullOvers = floor($overs);
        $fraction  = $overs - $fullOvers;
        $balls     = (int)round($fraction * 10); // .0 - .5 -> 0-5 balls

        if ($balls < 0) {
            $balls = 0;
        }
        if ($balls > 5) {
            $balls = 5;
        }

        return $fullOvers + ($balls / 6);
    }
}
