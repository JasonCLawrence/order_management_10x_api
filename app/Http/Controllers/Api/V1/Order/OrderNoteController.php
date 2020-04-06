<?php

namespace App\Http\Controllers\Api\V1\Order;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\ApiResponse;
use App\Order;
use App\OrderNote;
use App\User;
use DB;
use Validator;
use Illuminate\Support\Facades\Hash;
use Mail;
use App\Jobs\SendPushNotification;

class OrderNoteController extends Controller
{
  //

  public function __construct()
  {

    $this->res = new ApiResponse();
  }

      /**
     * @OA\Post(
     *     path="api/v1/order/{order_id}/note",
     *     summary="create order note",
     *     description="create ",
     *     tags={"Order"},
     *     security={ {"bearer": {}} },
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                @OA\Property(
     *                   property="content",
     *                   description="content.",
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

  public function create($order_id, Request $request)
  {
       $data = request()->all();


        $validator = Validator::make($data, [
            'content' => 'string|required',
        ]);

        if($validator->fails())
        {
            return $this->res->withError($validator->errors()->toArray(), 400);
        }

        $order = Order::find($order_id);

        if($order==null)
        {
          return $this->res->withError("Invalid Order", 400);
        }

        $order_note = new OrderNote;
        $order_note->order_id = $order_id;

        $order_note->content = $request->get("content");

        $order_note->save();

        return $this->res->withSuccessData($order_note);


  }

  
  /**
   * @OA\Delete(
   *     path="api/v1/order/{order_id}/note/{id}",
   *     summary="delete note",
   *     description="delete",
   *     tags={"Order"},
   *     security={ {"bearer": {}} },
   *     @OA\Response(response=200,description="successful operation",@OA\JsonContent()),
   *     @OA\Response(response=400,description="validation/server error",),
   *     @OA\Response(response=401,description="validation/server error",)
   * )
   */

  public function delete($order_id, $id)
  {
    $order = Order::find($order_id);
    $order_note =  OrderNote::find($id);

    if($order==null || $order_note==null)
    {
      return $this->res->withError("Invalid Order", 400);
    }

     if($order->complete==false)
     {
        $order->load(['customer','createdBy', 'driver', 'warehouse', 'checklist','attachments','invoiceItems']);
        SendPushNotification::sendToDriver($order->driver_id, 'note_deleted', $order);
     }

     $order_note->delete();

    return $this->res->withSuccess("Deleted successfully");

  }




   
}
