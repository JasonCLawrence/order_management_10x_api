<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VehicleDriver extends Model
{
    //

    public $timestamps = false;

	protected $primaryKey = null;

	public $incrementing = false;

    public function user()
    {
    	return $this->belongsTo("App\User", "user_id");
    }

    public function fleet()
    {
    	return $this->belongsTo("App\Fleet", "fleet_id");
    }
}
