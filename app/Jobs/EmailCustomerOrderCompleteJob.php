<?php

namespace App\Jobs;

use Storage;
use Illuminate\Bus\Queueable;
use App\Mail\EmailCustomerOrderComplete as EmailCustomer;
use Exception;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class EmailCustomerOrderCompleteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $orderId;

    public function __construct($orderId)
    {
        $this->orderId = $orderId;
    }

    public function handle()
    {
        $order = Order::with('customer')->find($this->orderId);
        if (!$order)
            throw new Exception("Order with id $order->id doesnt exist");

        $customer = $order->customer;
        $email = $customer->email;

        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
            throw new Exception("Invalid customer email '$email'");

        // send
        Mail::to($email)->send(new EmailCustomer($order->customer, $order));
    }
}
