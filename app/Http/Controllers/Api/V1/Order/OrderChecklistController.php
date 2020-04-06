<?php

namespace App\Http\Controllers\Api\V1\Order;

use Illuminate\Support\Str;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\ApiResponse;
use App\Order;
use App\OrderChecklist;
use App\User;
use DB;
use Validator;
use Illuminate\Support\Facades\Hash;
use Mail;
use App\Jobs\SendPushNotification;
use App\Jobs\OrderChecklistAuditJob;
use App\Jobs\OrderChecklistCheckedAuditJob;


class OrderChecklistController extends Controller
{
  //

  public function __construct()
  {
    $this->res = new ApiResponse();
  }

  public function create($order_id, Request $request)
  {
    $data = request()->all();

    $validator = Validator::make($data, [
      'name' => 'string|required',
    ]);

    if ($validator->fails()) {
      return $this->res->withError($validator->errors()->toArray(), 400);
    }

    $order = Order::find($order_id);

    if ($order == null) {
      return $this->res->withError("Invalid Order", 400);
    }

    $order_checklist_item = new OrderChecklist;
    $order_checklist_item->order_id = $order_id;
    $order_checklist_item->name  = $request->get("name");
    $order_checklist_item->save();

    if ($order->complete == false) {
      if ($request->header('source') != "mobile")
        SendPushNotification::sendToDriver($order->driver_id, 'task_added', $order_checklist_item);
    }

    return $this->res->withSuccessData($order_checklist_item);
  }


  public function getAll($order_id)
  {
    $checks = OrderChecklist::where("order_id", $order_id)->get();

    return $this->res->withSuccessData($checks);
  }

  public function update($order_id, Request $request)
  {
    $validator = Validator::make($request->all(), [
      'data' => 'array|required',
      'data.*.id' => 'integer|nullable',
      'data.*.name' => 'string|nullable',
      'data.*.hash' => 'string|required',
      'data.*.deleted' => 'boolean|nullable',
      'data.*.checked' => 'boolean|nullable',
    ]);

    $order = Order::find($order_id);

    if ($validator->fails()) {
      return $this->res->withError($validator->errors()->toArray(), 400);
    }

    try {
      DB::beginTransaction();
      if (count($request->get('data')) > 0) {
        $index = 0;
        foreach ($request->get('data') as $data) {
          // if (!isset($data["hash"]) && !isset($data["id"])) {
          //   DB::rollback();
          //   return $this->res->withError("Item at index $index has no id nor hash", 400);
          //   //continue; // throw error
          // }

          $check = OrderChecklist::where(function ($q) use ($data) {
            //if (isset($data["hash"]))
            $q->where('hash', $data['hash']);
            //if (isset($data["id"]))
            //  $q->orWhere('id', $data["id"]);
          })->where('order_id', $order->id)->first();

          if ($check) {
            if (isset($data['deleted']) && $data['deleted'] == true) {

              $check->delete();
              $index += 1;
              continue;
            }
          } else {
            $check = new OrderChecklist();
            $check->order_id = $order->id;
          }

          $check->name = $data['name'] == null ? "" : $data['name'];
          $check->checked = $data['checked'] ? 1 : 0;

          if (isset($data["hash"])) {
            $check->hash = $data["hash"];
          } else {
            $check->hash = (string) Str::uuid();
          }

          $check->save();

          $index += 1;
        }

        $order->load(['customer', 'createdBy', 'driver', 'warehouse', 'checklist', 'attachments', 'invoiceItems']);
        if ($request->header('source') != "mobile" && env('APP_ENV') != 'local')
          SendPushNotification::sendToDriver($order->driver_id, 'tasks_updated', $order);
      }

      DB::commit();

      $checks = OrderChecklist::where("order_id", $order_id)->get();

      // audit
      OrderChecklistAuditJob::dispatch(auth()->user()->id, $order->id, $request->get('data'));

      return $this->res->withSuccessData($checks);
    } catch (\Exception $e) {
      DB::rollback();
      return $this->res->withError($e->getTrace(), 400);
    }
  }

  public function delete($order_id, $id, Request $request)
  {
    $order = Order::find($order_id);

    $order_checklist_item = OrderChecklist::find($id);

    if ($order_checklist_item == null || $order_checklist_item->order_id != $order_id) {
      return $this->res->withError("Invalid Checklist item", 400);
    }

    if ($order->complete == false && $request->header('source') != "mobile" && env('APP_ENV') != 'local')
      SendPushNotification::sendToDriver($order->driver_id, 'task_deleted', $order_checklist_item);


    $order_checklist_item->delete();

    return $this->res->withSuccess("ok");
  }

  /**
   * @OA\Put(
   *     path="api/v1/order/{order_id}/task/{id}/check",
   *     summary="ucheck task",
   *     description="umark task ",
   *     tags={"OrderChecklist"},
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

  public function check($order_id, $id)
  {
    $order = Order::find($order_id);
    $order_checklist_item = OrderChecklist::find($id);

    if ($order_checklist_item == null || $order_checklist_item->order_id != $order_id) {
      return $this->res->withError("Invalid Checklist item", 400);
    }

    $order_checklist_item->checked = 1;
    $order_checklist_item->save();

    OrderChecklistCheckedAuditJob::dispatch(auth()->user()->id, $order->id, $order_checklist_item->id);

    return $this->res->withSuccess("ok");
  }


  /**
   * @OA\Put(
   *     path="api/v1/order/{order_id}/task/{id}/uncheck",
   *     summary="check task",
   *     description="mark task ",
   *     tags={"OrderChecklist"},
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

  public function uncheck($order_id, $id)
  {
    $order_checklist_item = OrderChecklist::find($id);

    if ($order_checklist_item == null || $order_checklist_item->order_id != $order_id) {
      return $this->res->withError("Invalid Checklist item", 400);
    }

    $order_checklist_item->checked = 0;
    $order_checklist_item->save();

    return $this->res->withSuccess("ok");
  }
}
