<?php

namespace App\Http\Controllers\Api\V1\Order;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\ApiResponse;
use App\Order;
use App\OrderAttachment;
use App\User;
use DB;
use Validator;
use Illuminate\Support\Facades\Hash;
use Mail;
use App\Jobs\UploadBase64Image;
use App\Jobs\SendPushNotification;

class OrderAttachmentController extends Controller
{
  //

  public function __construct()
  {
    $this->res = new ApiResponse();
  }

  /**
   * @OA\Post(
   *     path="/api/v1/order/{order_id}/attachment",
   *     summary="create attachment",
   *     description="create attachment ",
   *     tags={"Order"},
   *     security={ {"bearer": {}} },
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\MediaType(
   *             mediaType="application/json",
   *             @OA\Schema(
   *                @OA\Property(
   *                   property="data",
   *                   description="data.",
   *                   type="string",
   *                ),
   *                @OA\Property(
   *                   property="lat",
   *                   description="lat.",
   *                   type="decimal",
   *                ),
   *                @OA\Property(
   *                   property="long",
   *                   description="long.",
   *                   type="decimal",
   *                ),
   *             ),
   *         ),
   *     ),
   *     @OA\Response(response=200,description="successful operation",@OA\JsonContent()),
   *     @OA\Response(response=400,description="validation/server error",),
   *     @OA\Response(response=401,description="validation/server error",)
   * )
   */


  public function create($order_id, Request $request)
  {
    $data = request()->all();


    $validator = Validator::make($data, [
      'data'  => 'string|required',
      'lat'  => 'numeric|required',
      'long'  => 'numeric|required',

    ]);

    if ($validator->fails()) {
      return $this->res->withError($validator->errors()->toArray(), 400);
    }

    $order = Order::find($order_id);


    if ($order == null) {
      return $this->res->withError("Invalid order id");
    }


    UploadBase64Image::dispatch($order, $request->get("data"), $request->get("long"), $request->get("lat"));

    return $this->res->withSuccess("OK");
  }

  /**
   * @OA\Delete(
   *     path="/api/v1/order/{order_id}/attachment/{attachment_id}",
   *     summary="delete an order attachment",
   *     description="delete an order attachment",
   *     tags={"Order"},
   *     security={ {"bearer": {}} },
   *     @OA\Response(response=200,description="successful operation",@OA\JsonContent()),
   *     @OA\Response(response=400,description="validation/server error",),
   *     @OA\Response(response=401,description="validation/server error",)
   * )
   */


  public function delete(Request $request, $order_id, $attachment_id)
  {
    $order = Order::find($order_id);


    $orderAttachment = OrderAttachment::where('id', $attachment_id)->where('order_id', $order_id)->first();

    if ($orderAttachment == null) {
      return $this->res->withError("Invalid order attachment");
    }


    if ($order->complete == false) {
      $order->load(['customer', 'createdBy', 'driver', 'warehouse', 'checklist', 'attachments', 'invoiceItems']);
      if ($request->header('source') == "mobile")
        SendPushNotification::sendToDriver($order->driver_id, 'attachment_deleted', $order);
    }

    $orderAttachment->delete();

    return $this->res->withSuccess("OK");
  }
}
