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


class OrderWarehouseSignatureAuditJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user_id;

    // array of integers
    public $orderIds;
    public $signatureData;

    public function __construct(string $user_id, array $signatureData, array $orderIds)
    {
        $this->user_id = $user_id;
        $this->orderIds = $orderIds;
        $this->signatureData = $signatureData;
    }

    public function handle()
    {
        try{
            $orders = Order::whereIn('id', $this->orderIds)->get();

            $user = User::find($this->user_id);
            $userName = htmlspecialchars($user->first_name.' '.$user->last_name);

            $message = "<b>$userName</b> signed order(s):";

            foreach($orders as $order) {
                $orderNumber = htmlspecialchars($order->id);
                $invoiceNumber = $order->invoice_number ?
                    " (external id: <b>#".htmlspecialchars($order->invoice_number)."</b>)" :
                    "";

                $message .= " <b>#$orderNumber</b>$invoiceNumber -".htmlspecialchars($order->description);
            }
            
            $signatureData["orders"] = $this->orderIds;

            $log = new AuditLog();
            $log->data = json_encode($signatureData);
            $log->message = $message;
            $log->user_id = $user->id;
            $log->order_id = null;
            $log->type = "WAREHOUSE_SIGN_ORDER";
            $log->save();

        }catch(\Exception $e) {
            Log::info($data);
            Log::error($e->getMessage());
        }
    }
}