<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderInvoiceItem extends Model
{
    //


	public function order()
	{
		return $this->belongsTo("App\Order");

	}

	public function getPriceAttribute($value)
	{
		return $value/1000;
	}

	public function setPriceAttribute($value)
	{
		return $this->attributes['price'] = $value*1000;
	}

}
