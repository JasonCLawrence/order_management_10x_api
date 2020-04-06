<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PasswordReset extends Model
{
    //
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = 'email';

}
