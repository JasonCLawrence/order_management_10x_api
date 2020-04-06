<?php

namespace App\Http\Controllers\Api\V1\Order;

use DB;
use Mail;
use App\User;
use stdClass;
use App\Order;
use Validator;
use App\Customer;
use App\Warehouse;
use Carbon\Carbon;
use App\OrderChecklist;
use App\Http\ApiResponse;
use App\OrderInvoiceItem;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Jobs\OrderCompleteJob;
use Illuminate\Validation\Rule;
use App\Jobs\CreateOrderAuditJob;
use App\Jobs\DeleteOrderAuditJob;
use App\Jobs\UpdateOrderAuditJob;
use App\Jobs\SendPushNotification;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Jobs\EmailCustomerOrderCompleteJob;
use App\Jobs\CalculateOrderWarehouseAddressJob;
use App\Jobs\CalculateOrderClientAddressJob;

class OrderController extends Controller
{
  //

  public function __construct()
  {
    $this->res = new ApiResponse();
  }

  /**
   * @OA\Get(
   *     path="/api/v1/order",
   *     summary="Get all orders",
   *     description="get all order append ? q={?} to search and or completed=true to fetch only compeleted orders",
   *     tags={"Order"},
   *     security={ {"bearer": {}} },
   *     @OA\Response(response=200,description="successful operation",@OA\JsonContent()),
   *     @OA\Response(response=400,description="validation/server error",),
   *     @OA\Response(response=401,description="validation/server error",)
   * )
   */

  public function all(Request $request)
  {
    $query = (new Order)->newQuery();

    if ($request->has("q") && $request->get("q") != null) {
      $query->where(function ($q) use ($request) {
        $q->where('description', 'LIKE', '%' . $request->get("q") . '%')
          ->orWhere('address', 'LIKE', '%' . $request->get("q") . '%')
          ->orWhere('invoice_id', 'LIKE', '%' . $request->get("q") . '%');
      });
    }


    if ($request->has("customer_id") && $request->get("customer_id") != 0) {
      $query->where(function ($q) use ($request) {

        $q->where('customer_id', $request->get("customer_id"));
      });
    }

    if ($request->has("driver_id") && $request->get("driver_id") != 0) {
      $query->where(function ($q) use ($request) {

        $q->where('driver_id', $request->get("driver_id"));
      });
    }

    if ($request->has("warehouse_id") && $request->get("warehouse_id") != 0) {
      $query->where(function ($q) use ($request) {

        $q->where('warehouse_id', $request->get("warehouse_id"));
      });
    }

    if ($request->has("completed")) {

      $query->where(function ($q) use ($request) {

        $q->where('completed', intval($request->get("completed")));
      });
    }

    if ($request->has("type") && in_array($request->get("type"), ['service', 'invoice', 'delivery'])) {
      $query->where(function ($q) use ($request) {

        $q->where('type', $request->get("type"));
      });
    }


    $query->with(['customer', 'driver', 'warehouse', 'checklist', 'createdBy', 'attachments', 'invoiceItems']);
    $query->orderBy('updated_at', 'DESC');
    $items = $query->paginate(15);

    return $this->res->withSuccessData($items);
  }

  /**
   * @OA\Get(
   *     path="/api/v1/order/{id}",
   *     summary="Get order details",
   *     description="Find a order",
   *     tags={"Order"},
   *     security={ {"bearer": {}} },
   *     @OA\Response(response=200,description="successful operation",@OA\JsonContent()),
   *     @OA\Response(response=400,description="validation/server error",),
   *     @OA\Response(response=401,description="validation/server error",)
   * )
   */

  public function show($id)
  {
    $order = Order::with(['customer', 'driver', 'createdBy', 'warehouse', 'checklist', 'notes', 'attachments', 'invoiceItems'])->find($id);

    if ($order == null) {
      return $this->res->withError("Invalid order", 400);
    }

    return $this->res->withSuccessData($order);
  }

  /**
   * @OA\Delete(
   *     path="/api/v1/order/{id}",
   *     summary="delete order",
   *     description="delete",
   *     tags={"Order"},
   *     security={ {"bearer": {}} },
   *     @OA\Response(response=200,description="successful operation",@OA\JsonContent()),
   *     @OA\Response(response=400,description="validation/server error",),
   *     @OA\Response(response=401,description="validation/server error",)
   * )
   */

  public function delete($id)
  {
    $order = Order::find($id);

    if ($order == null) {
      return $this->res->withError("Invalid order", 400);
    }

    $orderData = $order->toArray();
    $order->delete();

    // audit
    DeleteOrderAuditJob::dispatch(auth()->user()->id, $orderData);

    return $this->res->withSuccess("Deleted successfully");
  }


  /**
   * @OA\Post(
   *     path="/api/v1/order",
   *     summary="create order",
   *     description="create ",
   *     tags={"Order"},
   *     security={ {"bearer": {}} },
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\MediaType(
   *             mediaType="application/json",
   *             @OA\Schema(
   *                @OA\Property(
   *                   property="customer_id",
   *                   description="customer_id.",
   *                   type="integer",
   *                ),
   *                @OA\Property(
   *                   property="driver_id",
   *                   description="driver_id.",
   *                   type="integer",
   *                ),
   *                @OA\Property(
   *                   property="warehouse_id",
   *                   description="warehouse_id.",
   *                   type="integer",
   *                ),
   *                @OA\Property(
   *                   property="description",
   *                   description="description.",
   *                   type="string",
   *                ),
   *                @OA\Property(
   *                   property="checklist",
   *                   description="checklist is actually an array like ['1', '2' ,'3']",
   *                   type="string",
   *                ),
   *                @OA\Property(
   *                   property="lat",
   *                   description="latitude",
   *                   type="numeric",
   *                ),
   *                @OA\Property(
   *                   property="long",
   *                   description="Longitude",
   *                   type="numeric",
   *                ),
   *                @OA\Property(
   *                   property="signature",
   *                   description="signature.",
   *                   type="boolean",
   *                ),
   *                @OA\Property(
   *                   property="schedule_at",
   *                   description="datetimr like 2019-01-01 11:12:22",
   *                   type="string",
   *                ),
   *                @OA\Property(
   *                   property="type",
   *                   description="invoice or service",
   *                   type="string",
   *                ),
   *                @OA\Property(
   *                   property="address",
   *                   description="address.",
   *                   type="string",
   *                ),
   *             ),
   *         ),
   *     ),
   *     @OA\Response(response=200,description="successful operation",@OA\JsonContent()),
   *     @OA\Response(response=400,description="validation/server error",),
   *     @OA\Response(response=401,description="validation/server error",)
   * )
   */

  public function create(Request $request)
  {
    $data = request()->all();


    $validator = Validator::make($data, [
      'customer_id' => 'integer|required|exists:customers,id',
      'driver_id' => 'integer|nullable',
      'warehouse_id' => 'integer|nullable',
      'description'  => 'string|nullable',
      'checklist'  => 'array|nullable',
      'lat'  => 'numeric|nullable',
      'long'  => 'numeric|nullable',
      'address'  => 'string|nullable',
      'schedule_at'  => 'date|required|date_format:Y-m-d H:i:s',
      'signature'  => 'boolean|nullable',
      'type' => [
        'required',
        Rule::in(['service', 'invoice', 'delivery']),
      ],
    ]);

    if ($validator->fails()) {
      return $this->res->withError($validator->errors()->toArray(), 400);
    }

    try {
      DB::beginTransaction();


      //save initial order
      $order = new Order;
      $order->customer_id = $request->get("customer_id");
      $order->driver_id = $request->get("driver_id");
      $order->warehouse_id = $request->get("warehouse_id");
      $order->description = $request->get("description");
      $order->lat = $request->get("lat");
      $order->long = $request->get("long");
      $order->address = $request->get("address");
      $order->type =  $request->get("type");
      $order->created_by =  auth()->user()->id;
      $order->schedule_at =  $request->get('schedule_at');
      if ($request->has("signature")) {
        $order->signature = $request->get('signature');
      }
      $order->save();

      //check invoice items

      if ($order->type == 'invoice') {
        //process invoice items
      }

      if ($request->has("checklist") && is_array($request->get("checklist"))) {
        if (count($request->get("checklist")) > 0) {
          foreach ($request->get("checklist") as $checklist_item) {
            $order_checklist_item = new OrderChecklist;
            $order_checklist_item->name = $checklist_item;
            $order_checklist_item->order_id = $order->id;
            $order_checklist_item->save();
          }
        }
      }

      DB::commit();

      $order->load(['customer', 'createdBy', 'driver', 'notes', 'warehouse', 'checklist', 'attachments', 'invoiceItems']);

      //send push
      if ($request->header('source') != "mobile" && env('APP_ENV') != 'local')
        SendPushNotification::sendToDriver($order->driver_id, 'order_added', $order);

      // audit
      CreateOrderAuditJob::dispatch(auth()->user()->id, $order->toArray());

      return $this->res->withSuccessData($order);
    } catch (\Exception $e) {
      DB::rollback();
      dd($e->getMessage());
      throw $e;
      return $this->res->withError("We could not create this order at this time", 400);
    }



    //return $this->res->withSuccessData($warehouse);


  }

  /**
   * @OA\Put(
   *     path="/api/v1/order/{id}",
   *     summary="update order",
   *     description="update ",
   *     tags={"Order"},
   *     security={ {"bearer": {}} },
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\MediaType(
   *             mediaType="application/json",
   *             @OA\Schema(
   *                @OA\Property(
   *                   property="customer_id",
   *                   description="customer_id.",
   *                   type="integer",
   *                ),
   *                @OA\Property(
   *                   property="driver_id",
   *                   description="driver_id.",
   *                   type="integer",
   *                ),
   *                @OA\Property(
   *                   property="warehouse_id",
   *                   description="warehouse_id.",
   *                   type="integer",
   *                ),
   *                @OA\Property(
   *                   property="description",
   *                   description="description.",
   *                   type="string",
   *                ),
   *                @OA\Property(
   *                   property="signature",
   *                   description="signature.",
   *                   type="boolean",
   *                ),
   *                @OA\Property(
   *                   property="lat",
   *                   description="latitude",
   *                   type="numeric",
   *                ),
   *                @OA\Property(
   *                   property="long",
   *                   description="Longitude",
   *                   type="numeric",
   *                ),
   *                @OA\Property(
   *                   property="schedule_at",
   *                   description="datetimr like 2019-01-01 11:12:22",
   *                   type="string",
   *                ),
   *                @OA\Property(
   *                   property="address",
   *                   description="address.",
   *                   type="string",
   *                ),
   *             ),
   *         ),
   *     ),
   *     @OA\Response(response=200,description="successful operation",@OA\JsonContent()),
   *     @OA\Response(response=400,description="validation/server error",),
   *     @OA\Response(response=401,description="validation/server error",)
   * )
   */

  public function update($id, Request $request)
  {
    $data = request()->all();


    $validator = Validator::make($data, [
      'customer_id' => 'integer|required|exists:customers,id',
      'driver_id' => 'integer|nullable',
      'warehouse_id' => 'integer|nullable',
      'description'  => 'string|nullable',
      'lat'  => 'numeric|nullable',
      'long'  => 'numeric|nullable',
      'address'  => 'string|nullable',
      'schedule_at'  => 'date|required|date_format:Y-m-d H:i:s',
      'signature'  => 'boolean|nullable',
    ]);

    if ($validator->fails()) {
      return $this->res->withError($validator->errors()->toArray(), 400);
    }

    $order = Order::find($id);

    if ($order == null) {
      return $this->res->withError("Invalid order", 400);
    }

    // save for audit lob before it's mutated
    $orderData = $order->toArray();

    try {
      DB::beginTransaction();

      $order->customer_id = $request->get("customer_id");
      $order->driver_id = $request->get("driver_id");
      $order->warehouse_id = $request->get("warehouse_id");
      $order->description = $request->get("description");
      $order->lat = $request->get("lat");
      $order->long = $request->get("long");
      $order->address = $request->get("address");
      $order->schedule_at =  $request->get('schedule_at');

      if ($request->has("signature")) {
        $order->signature = $request->get('signature');
      }

      $order->save();

      DB::commit();

      $order->load([
        'customer', 'createdBy', 'driver', 'notes', 'warehouse', 'checklist', 'attachments',
        'invoiceItems'
      ]);

      if ($order->complete == false && env('APP_ENV') != 'local') {
        SendPushNotification::dispatch($order->driver_id, 'order_updated', $order);
      }

      UpdateOrderAuditJob::dispatch($order->id, auth()->user()->id, $orderData, $request->all());

      return $this->res->withSuccessData($order);
    } catch (\Exception $e) {
      DB::rollback();
      dd($e->getMessage());
      return $this->res->withError("We could not update this order at this time", 400);
    }

    //return $this->res->withSuccessData($warehouse);


  }




  /**
   * @OA\Post(
   *     path="/api/v1/order/{id}/complete",
   *     summary="update order",
   *     description="set as compelete ",
   *     tags={"Order"},
   *     security={ {"bearer": {}} },
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\MediaType(
   *             mediaType="application/json",
   *             @OA\Schema(
   *                @OA\Property(
   *                   property="signature_data",
   *                   description="signature data",
   *                   type="string",
   *                ),
   *                @OA\Property(
   *                   property="signee",
   *                   description="Name of person signing",
   *                   type="string",
   *                ),
   *             ),
   *         ),
   *     ),
   *     @OA\Response(response=200,description="successful operation",@OA\JsonContent()),
   *     @OA\Response(response=400,description="validation/server error",),
   *     @OA\Response(response=401,description="validation/server error",)
   * )
   */


  public function complete($id, Request $request)
  {
    $order = Order::with('customer')->find($id);
    if ($order == null) {
      return $this->res->withError("Invalid order", 400);
    }

    $order->completed = true;
    $order->completed_by = auth()->user()->id;

    if ($request->has("signature_data")) {
      $order->signature_data = $request->get("signature_data");
    } else {
      $order->signature_data = "";
    }

    if ($request->has("signee")) {
      $order->signee = $request->get("signee");
    } else {
      $order->signee = "";
    }

    // datetime, lat and long
    if ($request->has("datetime")) {
      $order->completed_at = $request->get("datetime");
    } else {
      $order->completed_at = Carbon::now()->toDateTimeString();
    }

    if ($request->has("lat")) {
      $order->signature_lat = $request->get("lat");
    }
    if ($request->has("lon")) {
      $order->signature_long = $request->get("lon");
    }

    $order->save();

    //EmailCustomerOrderCompleteJob::dispatch($order->id);
    if ($request->has("lat") && $request->has("lon"))
      CalculateOrderClientAddressJob::dispatch($order->id, $order->signature_lat, $order->signature_long);
    else {
      $order->signature_address = null;
      $order->save();
    }

    return $this->res->withSuccess("ok");
  }


  /**
   * @OA\Put(
   *     path="/api/v1/order/{id}/incomplete",
   *     summary="update order",
   *     description="set as incompelete ",
   *     tags={"Order"},
   *     security={ {"bearer": {}} },
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\MediaType(
   *             mediaType="application/json",
   *             @OA\Schema(
   *             ),
   *         ),
   *     ),
   *     @OA\Response(response=200,description="successful operation",@OA\JsonContent()),
   *     @OA\Response(response=400,description="validation/server error",),
   *     @OA\Response(response=401,description="validation/server error",)
   * )
   */


  public function incomplete($id)
  {
    $order = Order::find($id);
    if ($order == null) {
      return $this->res->withError("Invalid order", 400);
    }

    $order->completed = false;
    $order->completed_by = null;
    $order->completed_at = null;

    $order->save();

    if (env('APP_ENV') != 'local')
      SendPushNotification::sendToDriver($order->driver_id, 'incomplete_order', $order);

    return $this->res->withSuccess("ok");
  }

  /**
   * @OA\Get(
   *     path="/api/v1/order/my_orders",
   *     summary="Get orders assigned to me",
   *     description="You can send ?filter=service|invoice to filter",
   *     tags={"Order"},
   *     security={ {"bearer": {}} },
   *     @OA\Response(response=200,description="successful operation",@OA\JsonContent()),
   *     @OA\Response(response=400,description="validation/server error",),
   *     @OA\Response(response=401,description="validation/server error",)
   * )
   */

  public function myorders(Request $request, Order $order)
  {
    $query = $order->newQuery();
    $query->with(['customer', 'driver', 'createdBy', 'warehouse', 'checklist', 'notes']);

    if ($request->has("filter") && in_array($request->get("filter"), ["invoice", "service"])) {
      $query->where('type', $request->get("filter"))->get();
    }

    if ($request->has("q") && $request->get("q") != null) {
      $query->where(function ($q) use ($request) {
        $q->where('description', 'LIKE', '%' . $request->get("q") . '%')
          ->orWhere('address', 'LIKE', '%' . $request->get("q") . '%')
          ->orWhere('type', 'LIKE', '%' . $request->get("q") . '%')
          ->orWhereHas("customer", function ($q) use ($request) {

            $q->where('name', 'LIKE', '%' . $request->get("q") . '%');
          })
          ->orWhereHas("warehouse", function ($q) use ($request) {

            $q->where('name', 'LIKE', '%' . $request->get("q") . '%');
          });
      });
    }


    $query->where('driver_id', auth()->user()->id)->where('completed', false)->orderBy('schedule_at', 'DESC')->with(['customer', 'warehouse', 'checklist', 'notes', 'attachments', 'invoiceItems']);


    $orders = $query->get();
    return $this->res->withSuccessData($orders);
  }

  /**
   * @OA\Post(
   *     path="/api/v1/order/{id}/warehouse_signature",
   *     summary="",
   *     description="Add signature from warehouse to order",
   *     tags={"Order"},
   *     security={ {"bearer": {}} },
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\MediaType(
   *             mediaType="application/json",
   *             @OA\Schema(
   *               @OA\Property(
   *                   property="warehouse_signee",
   *                   description="Name of person in warehouse who signed",
   *                   type="string",
   *                ),
   *               @OA\Property(
   *                   property="warehouse_signature_data",
   *                   description="Signature image as base64",
   *                   type="string",
   *                ),
   *             ),
   *         ),
   *     ),
   *     @OA\Response(response=200,description="successful operation",@OA\JsonContent()),
   *     @OA\Response(response=400,description="validation/server error",),
   *     @OA\Response(response=401,description="validation/server error",)
   * )
   */

  public function warehouse_signature($id, Request $request)
  {
    $data = request()->all();

    $validator = Validator::make($data, [
      'warehouse_signee'          => 'string|required',
      'warehouse_signature_data'  => 'string|required',
    ]);

    if ($validator->fails()) {
      return $this->res->withError($validator->errors()->toArray(), 400);
    }

    $order = Order::find($id);
    if ($order == null) {
      return $this->res->withError("Invalid order", 400);
    }

    $order->warehouse_signature_data = $request->get("warehouse_signature_data");
    $order->warehouse_signee         = $request->get("warehouse_signee");
    $order->warehouse_signed         = 1;

    // datetime, lat and long
    if ($request->has("datetime")) {
      $order->warehouse_datetime = $request->get("datetime");
    }
    if ($request->has("lat")) {
      $order->warehouse_lat = $request->get("lat");
    }
    if ($request->has("lon")) {
      $order->warehouse_long = $request->get("lon");
    }

    $order->save();

    if ($request->has("lat") || $request->has("lon"))
      CalculateOrderWarehouseAddressJob::dispatch($order->id, $order->warehouse_lat, $order->warehouse_long);

    return $this->res->withSuccess("ok");
  }

  public function bulk_warehouse_signature(Request $request)
  {
    $data = request()->all();

    $validator = Validator::make($data, [
      'warehouse_signee'          => 'string|required',
      'warehouse_signature_data'  => 'string|required',
      'orders'                    => 'array|required'
    ]);

    if ($validator->fails()) {
      return $this->res->withError($validator->errors()->toArray(), 400);
    }

    // $order->warehouse_signature_data = $request->get("warehouse_signature_data");
    // $order->warehouse_signee         = $request->get("warehouse_signee");
    // $order->warehouse_signed         = 1;

    if ($request->has("datetime")) {
      $warehouse_datetime = $request->get("datetime");
    } else {
      $warehouse_datetime = Carbon::now()->format("Y-m-d H:i:s");
    }
    if ($request->has("lat")) {
      $warehouse_lat = $request->get("lat");
    } else {
      $warehouse_lat = 0;
    }
    if ($request->has("lon")) {
      $warehouse_long = $request->get("lon");
    } else {
      $warehouse_long = 0;
    }

    $orders = $data["orders"];
    Order::whereIn('id', $orders)->update(
      array(
        'warehouse_signature_data' => $request->get("warehouse_signature_data"),
        'warehouse_signee' => $request->get("warehouse_signee"),
        'warehouse_signed' => 1,
        'warehouse_datetime' => $warehouse_datetime,
        'warehouse_lat' => $warehouse_lat,
        'warehouse_long' => $warehouse_long,
      )
    );

    return $this->res->withSuccess("ok");
  }

  public function invoice_upload(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'id' => 'string|required',
      'customerId' => 'string|required',
      'customerName' => 'string|required',
      'name' => 'string|required',
      'address' => 'string|nullable',
      'total' => 'numeric|required',
      'type' => [
        'required',
        Rule::in(['service', 'invoice', 'delivery']),
      ],
      // 'salesTax' => 'numeric|required',
      'schedule_at' => 'date|required',
      'items' => 'array',
      'items.*.id' => 'integer|nullable',
      'items.*.item' => 'string|nullable',
      'items.*.description' => 'string|nullable',
      'items.*.quantity' => 'numeric',
      'items.*.deleted' => 'boolean|nullable',
      'items.*.price' => 'numeric',
      'tasks' => 'array',
    ]);

    if ($validator->fails()) {
      return $this->res->withError($validator->errors()->toArray(), 400);
    }

    try {
      DB::beginTransaction();

      // if this invoice already exists, return success
      $order = Order::where('invoice_id', $request->get('id'))->first();
      if ($order)
        return $this->res->withSuccess("Already Exists");

      // if customer doesnt exist, create it
      $customer = Customer::where('customer_id', $request->get('customerId'))->first();
      if (!$customer) {
        $customer = new Customer();
        $customer->name = $request->get('customerName');
        $customer->customer_id = $request->get('customerId');
        $customer->save();
      }

      // if driver doesnt exist, create it
      // if ($request->has('assigneeId')) {
      //   $assignee_id = $request->get('assigneeId');
      //   $assignee = User::where('external_id', $assignee_id)->first();
      //   if (!$customer) {
      //     $customer = new Customer();
      //     $customer->name = $request->get('customerName');
      //     $customer->customer_id = $request->get('customerId');
      //     $customer->save();
      //   }
      // }

      // create order and add items
      $order = new Order();
      $order->type = $request->get('type');
      $order->invoice_id = $request->get('id');
      $order->description = $request->get('name');
      $order->address = $request->get('address');
      $order->invoice_sales_tax = $request->get('salesTax');
      $order->invoice_total = $request->get('total');
      //$order->date = $request->get('date');
      $order->created_by =  auth()->user()->id;
      $order->schedule_at = $request->get('schedule_at');
      $order->customer_id = $customer->id;
      $order->warehouse_id = null; // ?
      $order->driver_id = null; // ?
      $order->signature = 1;
      $order->save();

      // add items
      if ($request->has('tasks')) {
        if (count($request->get('items')) > 0) {
          foreach ($request->get('items') as $data) {
            $quantity = $data['quantity'];
            $price = $data['price'];

            //if ($price == 0 || $quantity==0) {
            $item = new OrderInvoiceItem();
            $item->order_id = $order->id;

            $item->item = $data['item'] ? $data['item'] : "";
            $item->description = $data['description'] ? $data['description'] : "";
            $item->quantity = $data['quantity'];
            $item->price = $data['price'];
            $item->hash = (string) Str::uuid();
            $item->save();
            // } else {
            //   $check = new OrderChecklist();
            //   $check->name = $data['item'] ? $data['item'] : "";
            //   $check->save();
            // }
          }
        }
      }

      if ($request->has('tasks')) {
        $tasks = $request->get('tasks');
        foreach ($tasks as $task) {
          $check = new OrderChecklist();
          $check->order_id = $order->id;
          $check->name = $task;
          $check->save();
        }
      }


      DB::commit();

      $order->load(['customer', 'createdBy', 'driver', 'warehouse', 'checklist', 'attachments', 'invoiceItems']);

      // audit
      CreateOrderAuditJob::dispatch(auth()->user()->id, $order->toArray());

      return $this->res->withSuccess($order);
    } catch (\Exception $e) {
      DB::rollback();
      throw $e;
      //return $this->res->withError($e->getTrace(), 400);
    }
  }

  public function bulk_set_driver(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'driverId' => 'numeric|required',
      'orders' => 'array|required',
    ]);

    if ($validator->fails()) {
      return $this->res->withError($validator->errors()->toArray(), 400);
    }

    $driverId = $request->get('driverId');
    $orders = $request->get('orders');

    try {
      Order::whereIn('id', $orders)->update(array('driver_id' => $driverId));

      $orders = Order::whereIn('id', $orders)->get();
      //foreach($orders as $order)
      //  SendPushNotification::dispatch('omsu-'.$driverId, 'order_updated', $order);

      return $this->res->withSuccess("");
    } catch (\Exception $e) {
      DB::rollback();
      throw $e;
      //return $this->res->withError($e->getTrace(), 400);
    }
  }

  public function bulk_set_warehouse(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'warehouseId' => 'numeric|required',
      'orders' => 'array|required',
    ]);

    if ($validator->fails()) {
      return $this->res->withError($validator->errors()->toArray(), 400);
    }

    $warehouseId = $request->get('warehouseId');
    $orders = $request->get('orders');

    try {
      //Order::whereIn('id',$orders)->update(array('warehouse_id'=>$driverId));
      Order::whereIn('id', $orders)->update(array('driver_id' => $warehouseId));

      $orders = Order::whereIn('id', $orders)->get();
      foreach ($orders as $order)
        SendPushNotification::sendToDriver($order->driver_id, 'order_updated', $order);
    } catch (\Exception $e) {
      DB::rollback();
      throw $e;
      //return $this->res->withError($e->getTrace(), 400);
    }
  }

  public function bulk_set_incomplete(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'orders' => 'array|required',
    ]);

    if ($validator->fails()) {
      return $this->res->withError($validator->errors()->toArray(), 400);
    }

    $orders = $request->get('orders');

    try {
      Order::whereIn('id', $orders)->update(array('completed' => false));
      $orders = Order::whereIn('id', $orders)->get();
      //foreach($orders as $order)
      //  SendPushNotification::dispatch('omsu-'.$order->driver_id, 'order_updated', $order);
    } catch (\Exception $e) {
      DB::rollback();
      throw $e;
      //return $this->res->withError($e->getTrace(), 400);
    }
  }

  public function bulk_delete(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'orders' => 'array|required',
    ]);

    if ($validator->fails()) {
      return $this->res->withError($validator->errors()->toArray(), 400);
    }

    $orders = $request->get('orders');

    try {
      $orders = Order::whereIn('id', $orders)->delete();
    } catch (\Exception $e) {
      DB::rollback();
      throw $e;
      //return $this->res->withError($e->getTrace(), 400);
    }
  }

  public function order_complete_email($id)
  {
    $order = Order::with('customer', 'invoiceItems', 'checklist')->find($id);

    $data = new stdClass;
    $data->customerName = $order->customer->name;
    $data->order = $order;

    $data->invoiceItems = $order->invoiceItems;
    $data->invoiceTotal = $order->invoice_total;
    $data->tasks = $order->checklist;

    $dt = Carbon::parse($order->completed_at);
    $data->orderDate = $dt->format('d M, Y');
    $data->orderTime = $dt->format('h:m A');

    //dd($data);

    return view('mail.order_complete', (array) $data);
  }
}
