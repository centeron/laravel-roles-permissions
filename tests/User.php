<?php

namespace Centeron\Permissions\Tests;

use Centeron\Permissions\Traits\HasAuthItems;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasAuthItems;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'status', 'email_verified_at'
    ];

    protected $table = 'users';
}
