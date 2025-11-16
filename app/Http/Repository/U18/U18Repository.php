<?php

namespace App\Http\Repository\U18;

use App\Http\Connector\U18\U18Interface;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Class U18Repository
 *
 * Repository that reads/writes Under-18 workflow data:
 * - users, media_assets, approvals (module='u18_verification')
 * - matches/teams/match_scores for dashboard U18 section
 */
class U18Repository implements U18Interface
{
    /**
     * {@inheritdoc}
     */
    public function apply(array $request, $user): object
    {
        $ret = (object)['success' => false, 'data' => null, 'message' => ''];
        DB::beginTransaction();
        try {
            // 1) Persist/attach birth certificate media
            $mediaId = null;

            /** @var UploadedFile|null $file */
            $file = $request['file'] ?? null;

            if ($file instanceof UploadedFile) {
                $path = $file->store('u18', ['disk' => 'public']); // configure 'public' in filesystems.php
                $url  = Storage::disk('public')->url($path);

                $mediaId = DB::table('media_assets')->insertGetId([
                    'owner_id'  => $user->id,
                    'module'    => 'u18_verification',
                    'module_id' => $user->id,
                    'url'       => $url,
                    'mime'      => $file->getClientMimeType(),
                    'bytes'     => $file->getSize(),
                ]);
            } elseif (!empty($request['media_asset_id'])) {
                $mediaId = (int)$request['media_asset_id'];
            }

            // 2) Ensure DOB is present/updated (optional)
            if (!empty($request['dob'])) {
                DB::table('users')->where('id', $user->id)->update(['dob' => $request['dob']]);
            }

            // 3) Upsert Approval row
            $approval = DB::table('approvals')
                ->where('module', 'u18_verification')
                ->where('item_id', $user->id)
                ->first();

            if ($approval) {
                DB::table('approvals')->where('id', $approval->id)->update([
                    'submitter_id' => $user->id,
                    'status'       => 'pending',
                    'notes'        => $request['notes'] ?? null,
                ]);
                $approvalId = (int)$approval->id;
            } else {
                $approvalId = DB::table('approvals')->insertGetId([
                    'module'       => 'u18_verification',
                    'item_id'      => $user->id,
                    'submitter_id' => $user->id,
                    'status'       => 'pending',
                    'notes'        => $request['notes'] ?? null,
                ]);
            }

            DB::commit();

            $ret->success = true;
            $ret->data = [
                'approval_id' => $approvalId,
                'media_asset_id' => $mediaId,
            ];
            return $ret;
        } catch (\Throwable $e) {
            DB::rollBack();
            $ret->message = $e->getMessage();
            return $ret;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function status($user): object
    {
        $ret = (object)['success' => true, 'data' => null, 'message' => ''];

        // age check from users.dob (eligible < 18 years as of today)
        $eligible = null;
        if ($user->dob) {
            $eligible = (now()->diffInYears(\Carbon\Carbon::parse($user->dob)) < 18);
        }

        $approval = DB::table('approvals')
            ->where('module', 'u18_verification')
            ->where('item_id', $user->id)
            ->orderByDesc('id')
            ->first();

        $media = DB::table('media_assets')
            ->where('owner_id', $user->id)
            ->where('module', 'u18_verification')
            ->where('module_id', $user->id)
            ->orderByDesc('id')
            ->first();

        $ret->data = [
            'eligible_by_dob' => $eligible,
            'approval_status' => $approval->status ?? 'not_applied',
            'approval_id'     => $approval->id ?? null,
            'birth_certificate_media' => $media ? [
                'id'  => $media->id,
                'url' => $media->url,
            ] : null,
            'role' => $user->role,
        ];

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function pending(array $request): object
    {
        $ret = (object)['success' => true, 'data' => [], 'message' => ''];

        $q = DB::table('approvals as a')
            ->join('users as u', 'u.id', '=', 'a.item_id')
            ->where('a.module', 'u18_verification')
            ->where('a.status', 'pending');

        if (!empty($request['q'])) {
            $q->where(function ($w) use ($request) {
                $w->where('u.name', 'like', '%' . $request['q'] . '%')
                  ->orWhere('u.email', 'like', '%' . $request['q'] . '%')
                  ->orWhere('u.mobile_number', 'like', '%' . $request['q'] . '%');
            });
        }

        $rows = $q->orderBy('a.created_at', 'asc')->limit(50)->get([
            'a.id as approval_id',
            'u.id as user_id',
            'u.name',
            'u.email',
            'u.dob',
            'a.created_at',
        ]);

        $ret->data = $rows;
        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function approve(int $approvalId, $reviewer): object
    {
        $ret = (object)['success' => false, 'data' => null, 'message' => ''];

        DB::beginTransaction();
        try {
            $ap = DB::table('approvals')->where('id', $approvalId)->lockForUpdate()->first();
            if (!$ap || $ap->module !== 'u18_verification') {
                $ret->message = 'Invalid approval id.';
                return $ret;
            }

            DB::table('approvals')->where('id', $approvalId)->update([
                'status'      => 'approved',
                'reviewer_id' => $reviewer->id ?? null,
                'updated_at'  => now(),
            ]);

            // Mark user as under_18 role
            DB::table('users')->where('id', $ap->item_id)->update(['role' => 'under_18']);

            DB::commit();
            $ret->success = true;
            return $ret;
        } catch (\Throwable $e) {
            DB::rollBack();
            $ret->message = $e->getMessage();
            return $ret;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function reject(int $approvalId, $reviewer, string $notes = ''): object
    {
        $ret = (object)['success' => false, 'data' => null, 'message' => ''];

        DB::beginTransaction();
        try {
            $ap = DB::table('approvals')->where('id', $approvalId)->lockForUpdate()->first();
            if (!$ap || $ap->module !== 'u18_verification') {
                $ret->message = 'Invalid approval id.';
                return $ret;
            }

            DB::table('approvals')->where('id', $approvalId)->update([
                'status'      => 'rejected',
                'reviewer_id' => $reviewer->id ?? null,
                'notes'       => $notes,
                'updated_at'  => now(),
            ]);

            DB::commit();
            $ret->success = true;
            return $ret;
        } catch (\Throwable $e) {
            DB::rollBack();
            $ret->message = $e->getMessage();
            return $ret;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function listU18Matches(array $request): object
    {
        $ret = (object)['success' => true, 'data' => [], 'message' => ''];

        $limit = (int)($request['limit'] ?? 10);
        if ($limit <= 0 || $limit > 50) $limit = 10;

        $now = now();

        // Latest score per match
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
            ->where('m.category', 'Under18');

        // onlyUpcoming = true → start >= now
        if (!empty($request['onlyUpcoming'])) {
            $q->where('m.start_datetime', '>=', $now);
        } else {
            // Show LIVE first, then upcoming (tomorrow 8AM style)
            $q->where(function ($w) use ($now) {
                $w->where('m.start_datetime', '<=', $now)    // live (if not ended)
                  ->orWhere('m.start_datetime', '>=', $now); // upcoming
            });
        }

        if (!empty($request['city'])) {
            $q->where('m.city', $request['city']);
        }

        $rows = $q->orderByRaw("CASE WHEN m.start_datetime <= ? THEN 0 ELSE 1 END", [$now])
            ->orderBy('m.start_datetime', 'asc')
            ->limit($limit)
            ->get([
                'm.id as match_id',
                DB::raw("CONCAT(COALESCE(ta.name,'Team A'),' vs ',COALESCE(tb.name,'Team B')) as title"),
                'm.venue',
                'm.city',
                'm.start_datetime',
                'ls.runs', 'ls.wickets', 'ls.overs'
            ]);

        $data = [];
        foreach ($rows as $r) {
            $data[] = [
                'match_id'    => (int)$r->match_id,
                'title'       => $r->title,                          // e.g., "U18 League - Match 1" (front-end can prefix)
                'subtitle'    => $r->venue ?: $r->city,
                'starts_at'   => $r->start_datetime,                 // for "Tomorrow 8AM"
                'score_text'  => ($r->runs !== null) ? ($r->runs . '/' . $r->wickets) : null,
                'runs'        => $r->runs,
                'wickets'     => $r->wickets,
                'overs'       => $r->overs,
                'is_live'     => ($r->start_datetime <= $now),
            ];
        }

        $ret->data = $data;
        return $ret;
    }
}
