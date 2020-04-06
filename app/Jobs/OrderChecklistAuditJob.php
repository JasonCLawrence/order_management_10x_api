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


class OrderChecklistAuditJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $data;
    public $order_id;
    public $user_id;

    public function __construct($user_id, $order_id, $data)
    {
        $this->order_id = $order_id;
        $this->user_id = $user_id;
        $this->data = $data;
    }

    public function handle()
    {
        try{
            $data = $this->data;
            $order = Order::find($this->order_id);
            $orderNumber = htmlspecialchars($order->id);
            $invoiceNumber = htmlspecialchars($order->invoice_number); // external id

            $user = User::find($this->user_id);
            $userName = htmlspecialchars($user->first_name.' '.$user->last_name);

            $message = "<b>$userName</b> updated CHECKLIST of order <b>#$orderNumber</b>".($invoiceNumber?"":":<br/>");
            if ($invoiceNumber)
                $message .= "(external id: <b>#$invoiceNumber</b>):<br/>";
            
            foreach($data as $item) {
                $name = $item['name'];
                if (isset($item['id']))
                    $message.="&bull; <b>update</b> '$name'";
                elseif (isset($item['deleted']))
                    $message.="&bull; <b>delete</b> '$name'";
                else
                    $message.="&bull; <b>add</b> '$name'";

                $message .= " - " . ($item['checked'] ? "<b>checked</b>" : "<b>unchecked</b>");
                $message .= "<br/>";
            }
            

            $log = new AuditLog();
            $log->data = json_encode($data);
            $log->message = $message;
            $log->user_id = $user->id;
            $log->order_id = $order->id;
            $log->type = "UPDATE_ORDER_CHECKLIST";
            $log->save();

        }catch(\Exception $e) {
            Log::info($data);
            Log::error($e->getMessage());
        }
    }
}