<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Stream
 *
 * Eloquent model for streams table.
 */
class Stream extends Model
{
    /**
     * @var string
     */
    protected $table = 'streams';

    /**
     * @var bool
     */
    public $timestamps = false;
}
