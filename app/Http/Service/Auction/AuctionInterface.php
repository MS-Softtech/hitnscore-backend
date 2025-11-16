<?php
namespace App\Http\Repository\Auction;

use App\Http\Connector\Auction\AuctionInterface;
use Illuminate\Support\Facades\DB;

/**
 * Class AuctionRepository
 *
 * Reads from:
 *  - player_auctions (id, name, start_time, end_time, status)
 *  - auction_players (id, auction_id, user_id, base_price, sold_price, sold_team_id, status)
 *  - auction_bids (id, auction_id, player_id, team_id, bid_amount, created_at)
 * And composes the dashboard card: id, player_profile_id, base_price, highest_bid, bids[], end_time, eligibility_rules.
 */
class AuctionRepository implements AuctionInterface
{
    /** Minimum increment for bidding (₹). Set in .env as AUCTION_MIN_INCREMENT=100 if needed. */
    private float $minIncrement;

    public function __construct()
    {
        $this->minIncrement = (float) (config('app.auction_min_increment', env('AUCTION_MIN_INCREMENT', 100)));
    }

    /**
     * {@inheritdoc}
     */
    public function listLivePlayers(array $request): object
    {
        $limit = max(1, min(20, (int)($request['limit'] ?? 10)));
        $now   = now();

        // Only auctions whose status is 'live' and not yet ended
        $rows = DB::table('auction_players as ap')
            ->join('player_auctions as a', 'a.id', '=', 'ap.auction_id')
            ->leftJoin('users as u', 'u.id', '=', 'ap.user_id')
            ->where('a.status', 'live')
            ->where(function($w) use ($now) {
                $w->whereNull('a.end_time')->orWhere('a.end_time', '>', $now);
            })
            ->orderBy('a.end_time', 'asc')
            ->limit($limit)
            ->get([
                'ap.id as player_id',
                'ap.user_id as player_profile_id',
                'ap.base_price',
                'a.end_time',
                'a.id as auction_id',
                'u.name as player_name',
                'u.photo_url as player_photo'
            ]);

        $data = [];
        foreach ($rows as $r) {
            $top = $this->highestBidForPlayer((int)$r->player_id);
            $data[] = [
                'id'                => (int)$r->player_id,
                'player_profile_id' => (int)$r->player_profile_id,
                'player_name'       => $r->player_name,
                'player_photo'      => $r->player_photo,
                'base_price'        => (float)$r->base_price,
                'highest_bid'       => $top['amount'],
                'highest_bid_team'  => $top['team_id'],
                'end_time'          => $r->end_time,
                'eligibility_rules' => $this->auctionRulesText((int)$r->auction_id),
            ];
        }

        return (object)['success' => true, 'data' => $data, 'message' => ''];
    }

    /**
     * {@inheritdoc}
     */
    public function listAuctionPlayers(int $auctionId, array $request): object
    {
        $limit = max(1, min(50, (int)($request['limit'] ?? 50)));

        $rows = DB::table('auction_players as ap')
            ->leftJoin('users as u', 'u.id', '=', 'ap.user_id')
            ->where('ap.auction_id', $auctionId)
            ->orderBy('ap.id', 'asc')
            ->limit($limit)
            ->get([
                'ap.id as player_id',
                'ap.user_id as player_profile_id',
                'ap.base_price',
                'ap.status',
                'ap.sold_price',
                'ap.sold_team_id'
            ]);

        $auction = DB::table('player_auctions')->where('id', $auctionId)->first();

        $data = [];
        foreach ($rows as $r) {
            $top = $this->highestBidForPlayer((int)$r->player_id);
            $data[] = [
                'id'                => (int)$r->player_id,
                'player_profile_id' => (int)$r->player_profile_id,
                'base_price'        => (float)$r->base_price,
                'highest_bid'       => $top['amount'],
                'bids'              => $this->bidsForPlayer((int)$r->player_id),
                'end_time'          => $auction->end_time ?? null,
                'eligibility_rules' => $this->auctionRulesText($auctionId),
                'status'            => $r->status,
                'sold_price'        => $r->sold_price ? (float)$r->sold_price : null,
                'sold_team_id'      => $r->sold_team_id ? (int)$r->sold_team_id : null,
            ];
        }

        return (object)['success' => true, 'data' => $data, 'message' => ''];
    }

    /**
     * {@inheritdoc}
     */
    public function getPlayerCard(int $playerId): object
    {
        $row = DB::table('auction_players as ap')
            ->join('player_auctions as a', 'a.id', '=', 'ap.auction_id')
            ->where('ap.id', $playerId)
            ->first(['ap.*', 'a.end_time', 'a.id as auction_id']);

        if (!$row) {
            return (object)['success' => false, 'data' => null, 'message' => 'Player not found in auction'];
        }

        $top = $this->highestBidForPlayer($playerId);

        $payload = [
            'id'                => (int)$row->id,
            'player_profile_id' => (int)$row->user_id,
            'base_price'        => (float)$row->base_price,
            'highest_bid'       => $top['amount'],
            'bids'              => $this->bidsForPlayer($playerId),
            'end_time'          => $row->end_time,
            'eligibility_rules' => $this->auctionRulesText((int)$row->auction_id),
            'status'            => $row->status,
            'sold_price'        => $row->sold_price ? (float)$row->sold_price : null,
            'sold_team_id'      => $row->sold_team_id ? (int)$row->sold_team_id : null,
        ];

        return (object)['success' => true, 'data' => $payload, 'message' => ''];
    }

    /**
     * {@inheritdoc}
     */
    public function placeBid(array $request, $user): object
    {
        $ret = (object)['success' => false, 'data' => null, 'message' => ''];

        $auctionId = (int)($request['auction_id'] ?? 0);
        $playerId  = (int)($request['player_id'] ?? 0);
        $teamId    = (int)($request['team_id'] ?? 0);
        $amount    = (float)($request['bid_amount'] ?? 0);

        if ($auctionId <= 0 || $playerId <= 0 || $teamId <= 0 || $amount <= 0) {
            $ret->message = 'auction_id, player_id, team_id and bid_amount are required.';
            return $ret;
        }

        $auction = DB::table('player_auctions')->where('id', $auctionId)->first();
        $player  = DB::table('auction_players')->where('id', $playerId)->first();
        if (!$auction || !$player || (int)$player->auction_id !== $auctionId) {
            $ret->message = 'Invalid auction/player.';
            return $ret;
        }

        // Check time window
        $now = now();
        if ($auction->end_time && $now->greaterThan($auction->end_time)) {
            $ret->message = 'Bidding closed.';
            return $ret;
        }

        // Highest so far
        $top = $this->highestBidForPlayer($playerId);
        $minAcceptable = max((float)$player->base_price, $top['amount'] + $this->minIncrement);

        if ($amount < $minAcceptable) {
            $ret->message = 'Bid must be ≥ ₹' . number_format($minAcceptable, 2);
            return $ret;
        }

        // Optional: verify team exists
        $teamExists = DB::table('teams')->where('id', $teamId)->exists();
        if (!$teamExists) {
            $ret->message = 'Team not found.';
            return $ret;
        }

        // Write bid
        $bidId = DB::table('auction_bids')->insertGetId([
            'auction_id' => $auctionId,
            'player_id'  => $playerId,
            'team_id'    => $teamId,
            'bid_amount' => $amount,
            'created_at' => $now,
        ]);

        $ret->success = true;
        $ret->data = [
            'bid_id'      => $bidId,
            'accepted_at' => $now->toDateTimeString(),
            'highest_bid' => $amount
        ];
        return $ret;
    }

    /**
     * Helper: highest bid for a player.
     * @param int $playerId
     * @return array{amount:float, team_id:int|null}
     */
    private function highestBidForPlayer(int $playerId): array
    {
        $row = DB::table('auction_bids')
            ->where('player_id', $playerId)
            ->orderBy('bid_amount', 'desc')
            ->orderBy('id', 'desc')
            ->first(['bid_amount', 'team_id']);

        return [
            'amount'  => $row ? (float)$row->bid_amount : 0.0,
            'team_id' => $row->team_id ?? null,
        ];
        }

    /**
     * Helper: full bid list for a player (latest first).
     * @param int $playerId
     * @return array<int, array{team_id:int, bid_amount:float, created_at:string}>
     */
    private function bidsForPlayer(int $playerId): array
    {
        $rows = DB::table('auction_bids as b')
            ->leftJoin('teams as t', 't.id', '=', 'b.team_id')
            ->where('b.player_id', $playerId)
            ->orderBy('b.bid_amount', 'desc')
            ->orderBy('b.id', 'desc')
            ->limit(20)
            ->get(['b.team_id', 'b.bid_amount', 'b.created_at', 't.name as team_name']);

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'team_id'    => (int)$r->team_id,
                'team_name'  => $r->team_name,
                'bid_amount' => (float)$r->bid_amount,
                'created_at' => $r->created_at,
            ];
        }
        return $out;
    }

    /**
     * Helper: return auction eligibility/rules HTML text.
     * Reads cms_pages where slug='auction-eligibility'; falls back to null.
     * @param int $auctionId
     * @return string|null
     */
    private function auctionRulesText(int $auctionId): ?string
    {
        $row = DB::table('cms_pages')->where('slug', 'auction-eligibility')->first(['html']);
        return $row->html ?? null;
    }
}
                                                                                                                