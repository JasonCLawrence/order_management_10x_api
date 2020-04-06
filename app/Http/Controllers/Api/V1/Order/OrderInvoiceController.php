<?php

namespace App\Http\Controllers\Api\V1\Order;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

use App\Http\Controllers\Controller;
use App\Http\ApiResponse;
use App\Order;
use App\OrderInvoiceItem;
use App\User;
use App\Company;
use DB;
use PDF;
use Validator;
use Mail;
use App\Mail\SendClientInvoice;
use App\Jobs\SendPushNotification;
use App\Jobs\OrderInvoiceAuditJob;

class OrderInvoiceController extends Controller
{
  //

  public function __construct()
  {

    $this->res = new ApiResponse();
  }

  public function create()
  {

  }

  public function getAll($order_id)
  {
    $checks = OrderInvoiceItem::where("order_id", $order_id)->get();

    return $this->res->withSuccessData($checks);
  }

  public function update($order_id, Request $request)
  {
      $validator = Validator::make($request->all(), [
        'data' => 'array|required',
        'data.*.id' =>'integer|nullable',
        'data.*.item' =>'string|nullable',
        'data.*.hash' =>'string|required',
        'data.*.description' =>'string|nullable',
        'data.*.quantity' =>'numeric',
        'data.*.deleted' =>'boolean|nullable',
        'data.*.price' =>'numeric',
    ]);

    $order = Order::find($order_id);

    if($validator->fails())
    {
        return $this->res->withError($validator->errors()->toArray(), 400);
    }

    try 
    {
        DB::beginTransaction();

        if (count($request->get('data')) > 0) {
          foreach($request->get('data') as $data)
          {
              $item = OrderInvoiceItem::where('hash', $data['hash'])->where('order_id', $order->id)->first();
              
              if ($item) {
                if(isset($data['deleted']) && $data['deleted'] == true) {

                  $item->delete();
                  continue;
                }
              } else {
                $item = new OrderInvoiceItem();
                $item->order_id = $order->id;
              }
              
              $item->item = $data['item'] ? $data['item'] : "";
              $item->description = $data['description'] ? $data['description'] : "";
              $item->quantity = $data['quantity'];
              $item->price = $data['price'];

              if (isset($data["hash"])) {
                $item->hash = $data["hash"];
              } else {
                $item->hash = (string) Str::uuid();
              }

              $item->save();
          }
          
          $order->load(['customer','createdBy', 'driver', 'warehouse', 'checklist','attachments','invoiceItems']);
          if ($request->header('source') != 'mobile' && env('APP_ENV')!='local')
            SendPushNotification::sendToDriver($order->driver_id, 'invoice_items_updated', $order);
        }

        

        $items = OrderInvoiceItem::where("order_id", $order_id)->get();

        // calculate invoice total
        $total = 0;
        foreach($items as $item) {
          $total += $item->quantity * $item->price;
        }
        
        $order->invoice_total = $total;
        $order->save();

        DB::commit();

        // audit
        OrderInvoiceAuditJob::dispatch(auth()->user()->id, $order->id, $request->get('data'));

        return $this->res->withSuccessData($items);

    }
    catch(\Exception $e)
    {
          DB::rollback();
          throw $e;
      return $this->res->withError($e->getTrace(), 400);
    }
  }

    /**
     * @OA\Put(
     *     path="/api/v1/order/{order_id}/invoice/item/{id}/quantity",
     *     summary="update invoice item quanity",
     *     description="update quantity ",
     *     tags={"Order"},
     *     security={ {"bearer": {}} },
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                @OA\Property(
     *                   property="quantity",
     *                   description="quantity.",
     *                   type="integer",
     *                )
     *             ),
     *         ),
     *     ),
     *     @OA\Response(response=200,description="successful operation",@OA\JsonContent()),
     *     @OA\Response(response=400,description="validation/server error",),
     *     @OA\Response(response=401,description="validation/server error",)
     * )
     */

  public function updateQuantity($order_id, $invoice_id, Request $request)
  {
    $order = Order::find($order_id);

  	$item = OrderInvoiceItem::where('id', $invoice_id)->where('order_id', $order_id)->first();

    if($item==null)
    {
      return $this->res->withError("Invalid invoice item", 400);
    }

    if($order->driver_id!=auth()->user()->id)
    {
      return $this->res->withError("Invalid order", 400);
    }

    $data = request()->all();


    $validator = Validator::make($data, [
        'quantity'  => 'integer|required',

    ]);

    if($validator->fails())
    {
        return $this->res->withError($validator->errors()->toArray(), 400);
    }


    $item->quantity = $request->get("quantity");
    $item->save();


    return $this->res->withSuccessData("OK");


  }

  public function emailToClient($id, Request $request)
  {
    $order = Order::find($id);
    if (!$order)
      return $this->res->withError("Order not found"); // should be 404?

    Mail::to('')->send(new SendClientInvoice($order));

    return $this->res->withSuccess("email sent");
  }

  public function download($id)
  {
    $company = Company::first();
    $order = Order::with('customer')->find($id);
    $items = OrderInvoiceItem::where("order_id", $id)->get();

    $view = view('pdf.invoice', ['items'=>$items,'order'=>$order, 'company'=>$company]);
    $pdf = PDF::loadHTML($view);
    
    return $pdf->stream('invoice.pdf');
    //return view('pdf.invoice', ['items'=>$items,'order'=>$order, 'company'=>$company]);
  }
   
}
