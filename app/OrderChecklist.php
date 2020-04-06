<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderChecklist extends Model
{
    function getChecked($value)
    {
        return $value == 1;
    }

    function setChecked($value)
    {
        $this->attributes['checked'] = !!$value;
    }
}
