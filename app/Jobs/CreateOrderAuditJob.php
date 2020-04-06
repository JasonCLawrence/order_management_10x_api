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


class CreateOrderAuditJob implements ShouldQueue
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

            $message = "<b>$userName</b> created order <b>#$orderNumber</b>".($invoiceNumber?"":":<br/>");
            if ($invoiceNumber)
                $message .= "(external id: <b>#$invoiceNumber</b>):<br/>";

            
            // driver
            if (isset($data["driver_id"]) &&  $data["driver_id"] !== null) {
                $driver = User::find($data["driver_id"]);
                if ($driver) {
                    $driverName = htmlspecialchars($driver->first_name.' '.$driver->last_name);

                    $message .= "&bull; Driver set to <b>$driverName</b><br/>";
                }
            }

            // customer
            if (isset($data["customer_id"]) &&  $data["customer_id"] !== null) {
                $customer = Customer::find($data["customer_id"]);
                if ($customer) {
                    $customerName = htmlspecialchars($customer->name);

                    $message .= "&bull; Customer set to <b>$customerName</b><br/>";
                }
            }

            // warehouse
            if (isset($data["warehouse_id"]) &&  $data["warehouse_id"] !== null) {
                $warehouse = Warehouse::find($data["warehouse_id"]);
                if ($warehouse) {
                    $warehouseName = htmlspecialchars($warehouse->name);

                    $message .= "&bull; Warehouse set to <b>$warehouseName</b><br/>";
                }
            }

            // description
            if (isset($data["description"]) &&  $data["description"] !== null) {
                $description = htmlspecialchars($data["description"]);

                $message .= "&bull; Description set to '$description'<br/>";
            }

            // address
            if (isset($data["address"]) &&  $data["address"] !== null) {
                $address = htmlspecialchars($data["address"]);

                $message .= "&bull; Address set to '$address'<br/>";
            }

            // date time
            if (isset($data["scheduled_at"]) &&  $data["scheduled_at"] !== null) {
                $datetime = htmlspecialchars($data["scheduled_at"]);

                $message .= "&bull; Scheduled date and time set to '$datetime'<br/>";
            }

            // lat
            if (isset($data["lat"]) &&  $data["lat"] !== null) {
                $lat = htmlspecialchars($data["lat"]);

                $message .= "&bull; Latitude set to '$lat'#<br/>";
            }

            // long
            if (isset($data["long"]) &&  $data["long"] !== null) {
                $long = htmlspecialchars($data["long"]);

                $message .= "&bull; Longitude set to '$long'<br/>";
            }

            if (isset($data["signature"]) &&  $data["signature"] !== null) {
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
            $log->type = "CREATE_ORDER";
            $log->save();

        }catch(\Exception $e) {
            Log::info($data);
            Log::error($e->getMessage());
        }
    }
}