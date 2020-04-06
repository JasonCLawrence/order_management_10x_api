<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Fleet extends Model
{
    //

    public function driver()
    {
    	return $this->hasOne('App\VehicleDriver')->with("user");
    }
}
