<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Carbon;

// send email to customer verifying that their order is complete
class EmailCustomerOrderComplete extends Mailable
{
    use Queueable;

    public $customerName;
    public $orderDate;
    public $orderTime;
    public $order;
    public $invoiceItems;
    public $invoiceTotal;
    public $tasks;

    public function __construct($customer, $order)
    {
        $this->customerName = $customer->name;
        $this->order = $order;

        $this->invoiceItems = $order->invoiceItems;
        $this->invoiceTotal = $order->invoice_total;
        $this->tasks = $order->tasks;

        $dt = Carbon::parse($order->completed_at);
        $this->orderDate = $dt->format('dddd MMMM DDDo, YYYY');
        $this->orderTime = $dt->format('h:mm A');
    }

    public function build()
    {
        return $this->subject('Your Order Has Been Completed')
            ->view('mail.order_complete');
    }
}
