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


class DeleteOrderAuditJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user_id;
    public $order;

    public function __construct(string $user_id, array $order)
    {
        $this->user_id = $user_id;
        $this->order = $order;
    }

    public function handle()
    {
        try{
            $data = $this->order;
            $order = (object)$this->order;
            $orderNumber = htmlspecialchars($order->id);
            $invoiceNumber = null;
            if (isset($data["invoice_number"])) // this field will not be in orders created in the ui
                htmlspecialchars($order->invoice_number); // external id

            $user = User::find($this->user_id);
            $userName = htmlspecialchars($user->first_name.' '.$user->last_name);

            $message = "<b>$userName</b> deleted order <b>#$orderNumber</b>".($invoiceNumber?"":":<br/>");
            if ($invoiceNumber)
                $message .= "(external id: <b>#$invoiceNumber</b>):<br/>";

            $log = new AuditLog();
            $log->data = json_encode($order);
            $log->message = $message;
            $log->user_id = $user->id;
            //$log->order_id = $order->id;
            $log->type = "DELETE_ORDER";
            $log->save();

        }catch(\Exception $e) {
            Log::info($data);
            Log::error($e->getMessage());
        }
    }
}