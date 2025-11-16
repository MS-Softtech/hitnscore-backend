<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class MatchCommentary
 *
 * Eloquent model for match_commentaries table.
 */
class MatchCommentary extends Model
{
    /**
     * @var string
     */
    protected $table = 'match_commentaries';

    /**
     * @var bool
     */
    public $timestamps = false;
}
