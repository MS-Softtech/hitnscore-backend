<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Team
 *
 * Eloquent model for teams table.
 */
class Team extends Model
{
    /**
     * @var string
     */
    protected $table = 'teams';

    /**
     * @var bool
     */
    public $timestamps = false;
}
