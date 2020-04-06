<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserRole extends Model
{

    public $timestamps = false;
    public $incrementing = false;

    public function role()
    {
    	return $this->belongsTo('App\Role')->select(['id', 'name']);
    }
}
