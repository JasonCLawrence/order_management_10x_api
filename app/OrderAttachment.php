<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderAttachment extends Model
{
    //
    public function getUrlAttribute($value)
    {
    	return 'https://staffgenie.s3.amazonaws.com/'.$value;
    }
}
