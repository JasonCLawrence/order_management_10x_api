<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
	
	public function getInvoiceTotalAttribute($value)
	{
		return $value/1000;
	}

	public function setInvoiceTotalAttribute($value)
	{
		return $this->attributes['invoice_total'] = $value*1000;
	}

	public function getInvoiceSalesTaxAttribute($value)
	{
		return $value/1000;
	}

	public function setInvoiceSalesTaxAttribute($value)
	{
		return $this->attributes['invoice_sales_tax'] = $value*1000;
	}

	public function checklist()
	{
		return $this->hasMany("App\OrderChecklist");
	}

	public function attachments()
	{
		return $this->hasMany("App\OrderAttachment");
	}

	public function invoiceItems()
	{
		return $this->hasMany("App\OrderInvoiceItem");
	}

	public function notes()
	{
		return $this->hasMany("App\OrderNote");
	}

	public function createdBy()
	{
		return $this->belongsTo('App\User', 'created_by');
	}

	public function driver()
	{
		return $this->belongsTo('App\User', 'driver_id');
	}

	public function customer()
	{
		return $this->belongsTo('App\Customer', 'customer_id');
	}

	public function warehouse()
	{
		return $this->belongsTo('App\Warehouse', 'warehouse_id');
	}

	public function auditLogs()
	{
		return $this->hasMany('App\AuditLog');
	}





}
