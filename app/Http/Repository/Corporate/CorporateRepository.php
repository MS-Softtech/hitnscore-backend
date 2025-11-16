<?php
// app/Http/Repository/Corporate/CorporateRepository.php
namespace App\Http\Repository\Corporate;

use App\Http\Connector\Corporate\CorporateInterface;
use Illuminate\Support\Facades\DB;

/**
 * Class CorporateRepository
 *
 * Reads corporate tournaments from DB and handles team enrollment.
 * Uses:
 *  - tournaments (category='Corporate', registration_start, registration_end, name, city, start_date, entry_fee)
 *  - tournament_registrations (status, payment_status)
 *  - company_teams (optional validation when company_id provided)
 */
class CorporateRepository implements CorporateInterface
{
    /**
     * {@inheritdoc}
     */
    public function listCorporateAds(array $request): object
    {
        $ret = (object)['success' => false, 'data' => [], 'message' => ''];

        try {
            $limit = max(1, min(10, (int)($request['limit'] ?? 5)));
            $today = now()->toDateString();

            $q = DB::table('tournaments as t')
                ->where('t.category', 'Corporate')
                // registration window “open”
                ->where(function ($w) use ($today) {
                    $w->whereNull('t.registration_start')->orWhere('t.registration_start', '<=', $today);
                })
                ->where(function ($w) use ($today) {
                    $w->whereNull('t.registration_end')->orWhere('t.registration_end', '>=', $today);
                });

            if (!empty($request['city'])) {
                $q->where('t.city', $request['city']);
            }

            // Count approved & pending registrations
            $regAgg = DB::table('tournament_registrations as tr')
                ->select('tr.tournament_id',
                         DB::raw("SUM(tr.status='approved') as approved"),
                         DB::raw("SUM(tr.status='pending')  as pending"))
                ->groupBy('tr.tournament_id');

            $rows = $q->leftJoinSub($regAgg, 'ra', function ($join) {
                    $join->on('ra.tournament_id', '=', 't.id');
                })
                ->orderBy('t.start_date', 'asc')
                ->limit($limit)
                ->get([
                    't.id',
                    't.name',
                    't.city',
                    't.start_date',
                    't.end_date',
                    't.entry_fee',
                    't.registration_end',
                    DB::raw('COALESCE(ra.approved,0) as registered_teams'),
                    DB::raw('COALESCE(ra.pending,0)  as pending_approvals'),
                ]);

            $data = [];
            foreach ($rows as $r) {
                $data[] = [
                    'tournament_id'     => (int)$r->id,
                    'title'             => $r->name,                 // “Corp Cup 2026”
                    'city'              => $r->city,
                    'start_date'        => $r->start_date,
                    'end_date'          => $r->end_date,
                    'entry_fee'         => $r->entry_fee,
                    'register_by'       => $r->registration_end,     // show “Register by …”
                    'registered_teams'  => (int)$r->registered_teams,
                    'pending_approvals' => (int)$r->pending_approvals,
                    'cta' => [
                        'enroll_url'  => "/corporate/enroll?tournament_id={$r->id}",
                        'details_url' => "/corporate/tournaments/{$r->id}"
                    ],
                    'tagline' => 'Registration Open',
                ];
            }

            $ret->success = true;
            $ret->data = $data;
        } catch (\Throwable $e) {
            $ret->message = $e->getMessage();
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function enrollTeam(array $request, $user): object
    {
        $ret = (object)['success' => false, 'data' => null, 'message' => ''];

        // basic validation
        $tournamentId = (int)($request['tournament_id'] ?? 0);
        $teamId       = (int)($request['team_id'] ?? 0);
        $companyId    = !empty($request['company_id']) ? (int)$request['company_id'] : null;

        if ($tournamentId <= 0 || $teamId <= 0) {
            $ret->message = 'tournament_id and team_id are required.';
            return $ret;
        }

        DB::beginTransaction();
        try {
            // 1) Check tournament is Corporate and registration open
            $t = DB::table('tournaments')->where('id', $tournamentId)->first();
            if (!$t || $t->category !== 'Corporate') {
                $ret->message = 'Invalid tournament or not corporate.';
                DB::rollBack(); return $ret;
            }
            $today = now()->toDateString();
            if (($t->registration_start && $t->registration_start > $today) ||
                ($t->registration_end   && $t->registration_end   < $today)) {
                $ret->message = 'Registration window is closed.';
                DB::rollBack(); return $ret;
            }

            // 2) Optional: validate that team belongs to provided company
            if ($companyId) {
                $exists = DB::table('company_teams')
                    ->where('company_id', $companyId)
                    ->where('team_id', $teamId)
                    ->exists();
                if (!$exists) {
                    $ret->message = 'Team is not linked to the company.';
                    DB::rollBack(); return $ret;
                }
            }

            // 3) Prevent duplicates
            $dup = DB::table('tournament_registrations')
                ->where('tournament_id', $tournamentId)
                ->where('team_id', $teamId)
                ->first();
            if ($dup) {
                $ret->success = true;
                $ret->data = ['registration_id' => $dup->id, 'status' => $dup->status];
                DB::commit(); return $ret;
            }

            // 4) Create registration (pending/unpaid)
            $regId = DB::table('tournament_registrations')->insertGetId([
                'tournament_id'  => $tournamentId,
                'team_id'        => $teamId,
                'status'         => 'pending',
                'payment_status' => 'unpaid',
                'created_at'     => now(),
            ]);

            DB::commit();
            $ret->success = true;
            $ret->data = ['registration_id' => $regId, 'status' => 'pending'];
        } catch (\Throwable $e) {
            DB::rollBack();
            $ret->message = $e->getMessage();
        }

        return $ret;
    }
}
