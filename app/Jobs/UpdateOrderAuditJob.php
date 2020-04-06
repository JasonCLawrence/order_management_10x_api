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


class UpdateOrderAuditJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $data;
    public $oldOrder; // old order as array
    public $message;
    public $order_id;
    public $user_id;

    public function __construct($order_id, $user_id, $oldOrder, $data)
    {
        $this->data = $data;
        $this->order_id = $order_id;
        $this->user_id = $user_id;
        $this->oldOrder = $oldOrder;
    }

    public function handle()
    {
        //Log::info("handling inside job");

        try{
            $data = $this->data;
            $oldOrder = $this->oldOrder;
            $order = Order::find($this->order_id);
            $orderNumber = htmlspecialchars($order->id);
            $invoiceNumber = htmlspecialchars($order->invoice_number); // external id

            $user = User::find($this->user_id);
            $userName = htmlspecialchars($user->first_name.' '.$user->last_name);

            $message = "<b>$userName</b> updated order <b>#$orderNumber</b>".($invoiceNumber?"":":<br/>");
            if ($invoiceNumber)
                $message .= "(external id: <b>#$invoiceNumber</b>):<br/>";

            
            // driver
            if (isset($data["driver_id"]) &&  $data["driver_id"] !== null && $data["driver_id"] != $oldOrder["driver_id"]) {
                $driver = User::find($data["driver_id"]);
                $driverName = htmlspecialchars($driver->first_name.' '.$driver->last_name);

                $message .= "&bull; Driver set to <b>$driverName</b><br/>";
            }

            // customer
            if (isset($data["customer_id"]) &&  $data["customer_id"] !== null && $data["customer_id"] != $oldOrder["customer_id"]) {
                $customer = Customer::find($data["customer_id"]);
                $customerName = htmlspecialchars($customer->name);

                $message .= "&bull; Customer set to <b>$customerName</b><br/>";
            }

            // warehouse
            if (isset($data["warehouse_id"]) &&  $data["warehouse_id"] !== null && $data["warehouse_id"] != $oldOrder["warehouse_id"]) {
                $warehouse = Warehouse::find($data["warehouse_id"]);
                $warehouseName = htmlspecialchars($warehouse->name);

                $message .= "&bull; Warehouse set to <b>$warehouseName</b><br/>";
            }

            // description
            if (isset($data["description"]) &&  $data["description"] !== null && $data["description"] != $oldOrder["description"]) {
                $description = htmlspecialchars($data["description"]);

                $message .= "&bull; Description set to '$description'<br/>";
            }

            // address
            if (isset($data["address"]) &&  $data["address"] !== null && $data["address"] != $oldOrder["address"]) {
                $address = htmlspecialchars($data["address"]);

                $message .= "&bull; Address set to '$address'<br/>";
            }

            // date time
            if (isset($data["scheduled_at"]) &&  $data["scheduled_at"] !== null && $data["scheduled_at"] != $oldOrder["scheduled_at"]) {
                $datetime = htmlspecialchars($data["scheduled_at"]);

                $message .= "&bull; Scheduled date and time set to '$datetime'<br/>";
            }

            // lat
            if (isset($data["lat"]) &&  $data["lat"] !== null && $data["lat"] != $oldOrder["lat"]) {
                $lat = htmlspecialchars($data["lat"]);

                $message .= "&bull; Latitude set to '$lat'#<br/>";
            }

            // long
            if (isset($data["long"]) &&  $data["long"] !== null && $data["long"] != $oldOrder["long"]) {
                $long = htmlspecialchars($data["long"]);

                $message .= "&bull; Longitude set to '$long'<br/>";
            }

            if (isset($data["signature"]) &&  $data["signature"] !== null && (bool)$data["signature"] != (bool)$oldOrder["signature"]) {
                $signature = $data["signature"];

                if ($signature)
                    $message .= "&bull; <b>Enable</b> signature requirement<br/>";
                else
                    $message .= "&bull; <b>Disable</b> signature requirement<br/>";
            }

            $log = new AuditLog();
            $log->data = json_encode($data);
            $log->message = $message;
            $log->user_id = $user->id;
            $log->order_id = $order->id;
            $log->type = "UPDATE_ORDER";
            $log->save();

        }catch(\Exception $e) {
            Log::info($oldOrder);
            Log::info($data);
            Log::error($e->getMessage());
        }
    }
}