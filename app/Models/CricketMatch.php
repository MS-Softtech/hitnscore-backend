<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class CricketMatch
 *
 * Eloquent model for matches table.
 */
class CricketMatch extends Model
{
    /**
     * @var string
     */
    protected $table = 'matches';

    /**
     * @var string[]
     */
    protected $fillable = [
        'match_type',
        'category',
        'gender',
        'team_a_id',
        'team_b_id',
        'venue',
        'city',
        'start_datetime',
        'end_datetime',
        'toss_result',
        'stream_link',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * matches has only created_at, no updated_at.
     *
     * @var bool
     */
    public $timestamps = false;
}
