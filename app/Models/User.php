<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
/**
 * Class User
 *
 * Eloquent model for `users` table (see score_v2.sql).
 * Columns used: id, name, gender, dob, mobile_number (unique), email (unique),
 * password (hashed), pincode, role, status, city, photo_url, bio, batting_hand,
 * bowling_type, primary_role, jwt_revoked_at, last_login_at, created_at, updated_at.
 *
 * @package App\Models
 */
class User extends Authenticatable
{
 
    use  Notifiable, HasFactory;

    protected $table = 'users';

    /** @var array<int,string> $fillable Mass-assignable columns. */
    protected $fillable = [
        'name','gender','dob','mobile_number','email','password','pincode','role',
        'status','city','photo_url','bio','batting_hand','bowling_type','primary_role',
        'jwt_revoked_at','last_login_at'
    ];

    /** @var array<string,string> $casts Casts for date/time columns. */
    protected $casts = [
        'dob'            => 'date',
        'jwt_revoked_at' => 'datetime',
        'last_login_at'  => 'datetime',
        'created_at'     => 'datetime',
        'updated_at'     => 'datetime',
    ];

    /** @var array<int,string> $hidden Never expose these in API payloads. */
    protected $hidden = ['password'];
}
