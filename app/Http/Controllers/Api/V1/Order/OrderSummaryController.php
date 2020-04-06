<?php

namespace App\Http\Controllers\Api\V1\Order;

// use Illuminate\Http\Request;
use DB;
use Mail;
use PDF;
use App\User;
use App\Order;
use App\OrderCompany;
use App\Company;
use Validator;
use App\OrderNote;
use App\Http\ApiResponse;
use Illuminate\Http\Request;
use App\Jobs\SendPushNotification;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;

class OrderSummaryController extends Controller
{
    public function __construct()
    {
        $this->res = new ApiResponse();
    }

    public function download($order_id, Request $request)
    {
        $data = request()->all();

        $validator = Validator::make($data, [
            'content' => 'string|required',
        ]);

        if ($validator->fails()) {
            return $this->res->withError($validator->errors()->toArray(), 400);
        }

        $order = Order::find($order_id);

        if ($order == null) {
            return $this->res->withError("Invalid Order", 400);
        }

        $order_note = new OrderNote;
        $order_note->order_id = $order_id;

        $order_note->content = $request->get("content");

        $order_note->save();

        return $this->res->withSuccessData($order_note);
    }

    public function downloadSummary(int $order_id, Request $request)
    {
        $company = Company::first();
        $order = Order::with(['customer', 'createdBy', 'driver', 'notes', 'warehouse', 'checklist', 'attachments', 'invoiceItems', 'auditLogs'])->find($order_id);

        $view = view('pdf.summary', ['order' => $order, 'company' => $company]);
        $pdf = PDF::loadHTML($view);

        return $pdf->stream('invoice.pdf');
    }
}
