<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class MatchScore
 *
 * Eloquent model for match_scores table.
 */
class MatchScore extends Model
{
    /**
     * @var string
     */
    protected $table = 'match_scores';

    /**
     * @var string[]
     */
    protected $fillable = [
        'match_id',
        'innings_no',
        'team_side',
        'runs',
        'wickets',
        'overs',
        'extras',
    ];

    /**
     * matches_scores has only updated_at timestamp.
     */
    public $timestamps = true;

    /**
     * @var string|null
     */
    const CREATED_AT = null;

    /**
     * @var string
     */
    const UPDATED_AT = 'updated_at';
}
