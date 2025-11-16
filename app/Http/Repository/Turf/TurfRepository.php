<?php

namespace App\Http\Repository\Turf;

use App\Http\Connector\Turf\TurfInterface;
use Illuminate\Support\Facades\DB;

/**
 * Class TurfRepository
 *
 * Reads data from:
 *  - turfs
 *  - turf_slots
 *  - turf_pricing
 * Returns: id, name, location, slots[], price_per_hour
 */
class TurfRepository implements TurfInterface
{
    /**
     * {@inheritdoc}
     */
    public function listTurfs(array $request, $user): object
    {
        $ret = (object)['success' => false, 'data' => [], 'message' => ''];

        try {
            $limit       = max(1, min(20, (int)($request['limit'] ?? 6)));
            $slotsLimit  = max(1, min(6,  (int)($request['slots_limit'] ?? 3)));
            $fromDateStr = $request['from_date'] ?? now()->toDateString();

            $q = DB::table('turfs as t');

            if (!empty($request['city'])) {
                $q->where('t.city', $request['city']);
            }

            $turfs = $q->orderBy('t.name', 'asc')
                ->limit($limit)
                ->get([
                    't.id',
                    't.name',
                    't.address',
                    't.city',
                    't.pincode',
                    't.price_per_hour'
                ]);

            if ($turfs->isEmpty()) {
                $ret->success = true;
                $ret->data    = [];
                return $ret;
            }

            $turfIds = $turfs->pluck('id')->all();

            // Preload upcoming available slots per turf
            $slots = DB::table('turf_slots')
                ->whereIn('turf_id', $turfIds)
                ->where('slot_date', '>=', $fromDateStr)
                ->where('is_booked', false)
                ->orderBy('slot_date', 'asc')
                ->orderBy('slot_start_time', 'asc')
                ->get([
                    'id', 'turf_id', 'slot_date', 'slot_start_time', 'slot_end_time'
                ])
                ->groupBy('turf_id');

            // Preload pricing per turf (for fallback or slot price derivation)
            $pricing = DB::table('turf_pricing')
                ->whereIn('turf_id', $turfIds)
                ->get(['turf_id','dow','start_time','end_time','price'])
                ->groupBy('turf_id');

            $data = [];
            foreach ($turfs as $t) {
                $loc  = trim(($t->address ? ($t->address . ', ') : '') . ($t->city ?: ''));

                // Pick slots (limit N)
                $slotRows = ($slots->get($t->id) ?? collect())->take($slotsLimit);

                $slotArr = [];
                foreach ($slotRows as $s) {
                    $slotArr[] = [
                        'id'         => (int)$s->id,
                        'date'       => $s->slot_date,
                        'start_time' => $s->slot_start_time,
                        'end_time'   => $s->slot_end_time,
                        'price'      => $this->priceForSlot($t, $s, $pricing->get($t->id) ?? collect()),
                    ];
                }

                // Derive price_per_hour: use turfs.price_per_hour else min pricing row
                $derivedPerHour = $this->derivePerHour($t, $pricing->get($t->id) ?? collect());

                $data[] = [
                    'id'             => (int)$t->id,
                    'name'           => $t->name,
                    'location'       => $loc,
                    'price_per_hour' => $derivedPerHour,
                    'slots'          => $slotArr
                ];
            }

            $ret->success = true;
            $ret->data    = $data;
        } catch (\Throwable $e) {
            $ret->message = $e->getMessage();
        }

        return $ret;
    }

    /**
     * Compute a slot price using turf_pricing if available.
     * - Match by day-of-week and time window containment.
     * - If no rule matches, return null.
     *
     * @param object              $turf
     * @param object              $slot (fields: slot_date, slot_start_time, slot_end_time)
     * @param \Illuminate\Support\Collection $rules for this turf
     * @return float|null
     */
    private function priceForSlot(object $turf, object $slot, $rules): ?float
    {
        // If turf has fixed price_per_hour, you may compute per-slot by duration, but
        // for dashboard display we typically show per-hour; return null here so UI uses price_per_hour.
        if ($turf->price_per_hour !== null) {
            return null;
        }

        try {
            $dow = (int) (new \DateTime($slot->slot_date))->format('w'); // 0-6
        } catch (\Throwable $e) {
            return null;
        }

        foreach ($rules as $r) {
            if ((int)$r->dow !== $dow) continue;
            // slot start within [start_time, end_time)
            if ($slot->slot_start_time >= $r->start_time && $slot->slot_start_time < $r->end_time) {
                return (float)$r->price;
            }
        }
        return null;
    }

    /**
     * Derive price_per_hour:
     * - Prefer turfs.price_per_hour when present.
     * - Else, use MIN(turf_pricing.price) for that turf (cheapest per-hour rule).
     *
     * @param object $turf
     * @param \Illuminate\Support\Collection $rules
     * @return float|null
     */
    private function derivePerHour(object $turf, $rules): ?float
    {
        if ($turf->price_per_hour !== null) {
            return (float)$turf->price_per_hour;
        }
        if ($rules->isEmpty()) return null;

        $min = null;
        foreach ($rules as $r) {
            $p = (float)$r->price;
            $min = $min === null ? $p : min($min, $p);
        }
        return $min;
    }
}
