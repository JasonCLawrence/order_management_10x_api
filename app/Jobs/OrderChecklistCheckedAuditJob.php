<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

use App\Order;
use App\OrderInvoiceItem;
use App\OrderChecklist;
use App\Warehouse;
use App\User;
use App\Customer;
use App\AuditLog;


class OrderChecklistCheckedAuditJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $check_id;
    public $order_id;
    public $user_id;

    public function __construct($user_id, $order_id, $check_id)
    {
        $this->order_id = $order_id;
        $this->user_id = $user_id;
        $this->check_id = $check_id;
    }

    public function handle()
    {
        try {
            $check = OrderChecklist::find($this->check_id);
            $order = Order::find($this->order_id);
            $orderNumber = htmlspecialchars($order->id);
            $invoiceNumber = htmlspecialchars($order->invoice_number); // external id

            $user = User::find($this->user_id);
            $userName = htmlspecialchars($user->first_name . ' ' . $user->last_name);

            $status = $check->checked ? "CHECKED" : "UNCHECKED";

            $message = "<b>$userName</b> <b>$status</b> checklist item(<b>#{$check->name}</b>) of order <b>#$orderNumber</b>" . ($invoiceNumber ? "" : ":<br/>");
            if ($invoiceNumber)
                $message .= "(external id: <b>#$invoiceNumber</b>):<br/>";

            $log = new AuditLog();
            //$log->data = json_encode($data);
            $log->message = $message;
            $log->user_id = $user->id;
            $log->order_id = $order->id;
            $log->type = "CHECK_CHECKLIST_ITEM";
            $log->save();
        } catch (\Exception $e) {
            //Log::info($data);
            Log::error($e->getMessage());
        }
    }
}
