<?php

namespace App\Http\Controllers\Api\V1\Report;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\ApiResponse;
use App\Order;
use App\User;
use DB;
use Validator;
use Illuminate\Support\Facades\Hash;
use Mail;
use Excel;

use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportController extends Controller
{
	//

	public function __construct()
	{
		$this->res = new ApiResponse();
	}

	/**
	 * @OA\Get(
	 *     path="/api/v1/report/headers",
	 *     summary="Generate report",
	 *     description="Generate report, check code for params",
	 *     tags={"Report"},
	 *     security={ {"bearer": {}} },
	 *     @OA\Response(response=200,description="successful operation",@OA\JsonContent()),
	 *     @OA\Response(response=400,description="validation/server error",),
	 *     @OA\Response(response=401,description="validation/server error",)
	 * )
	 */

	public function headers(Request $request, Order $order)
	{
		$query = $order->newQuery();

		if ($request->has("customer_id") && $request->get("customer_id") != 0) {
			$query->where(function ($q) use ($request) {

				$q->where('customer_id', $request->get("customer_id"));
			});
		}

		if ($request->has("driver_id") && $request->get("driver_id") != 0) {
			$driverId = $request->get("driver_id");

			// -1 is for any assigned
			// -2 is for unassigned
			if ($driverId == -1) {
				$query->where(function ($q) use ($request) {

					$q->where('driver_id', "!=", null);
				});
			} else if ($driverId == -2) {
				$query->where(function ($q) use ($request) {

					$q->where('driver_id', null);
				});
			} else {
				$query->where(function ($q) use ($request) {

					$q->where('driver_id', $request->get("driver_id"));
				});
			}
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

		if ($request->has("type") && in_array($request->get("type"), ['service', 'invoice'])) {
			$query->where(function ($q) use ($request) {

				$q->where('type', $request->get("type"));
			});
		}

		if ($request->has("due")) {
			$query->where(function ($q) use ($request) {

				$q->whereDate('schedule_at', $request->get("due"));
			});
		}


		if ($request->has("created_start")  && $request->has("created_end")) {
			$query->where(function ($q) use ($request) {


				$q->whereBetween('created_at', [$request->get("created_start"), $request->get("created_end")]);
			});
		}

		$orders = $query->with(['driver', 'warehouse', 'customer', 'createdBy'])->get();

		$orderArray = [];

		$orders->transform(function ($order) use (&$orderArray) {
			$od = [];
			$od['id'] = $order->id;
			$od['created_at'] = date("F j, Y, g:i a", strtotime($order->created_at));
			$od['updated_at'] = date("F j, Y, g:i a", strtotime($order->updated_at));
			$od['type'] = $order->type;
			$od['due'] = date("F j, Y, g:i a", strtotime($order->schedule_at));
			$od['completed'] = $order->completed ? "Yes" : "No";

			if ($order->driver)
				$od['driver'] = $order->driver->first_name . ' ' . $order->driver->last_name;
			else
				$od['driver'] = "";

			$od['customer'] = $order->customer->name;

			if ($order->warehouse)
				$od['warehouse'] = $order->warehouse->name;
			else
				$od['warehouse'] = "";

			$od['created_by'] = $order->createdBy->first_name . ' ' . $order->createdBy->last_name;
			$orderArray[] = $od;

			return $order;
		});


		if ($request->has("xls")) {
			//return here as an exel
			Excel::create('report-' . time(), function ($excel) use ($orderArray) {

				$excel->sheet('Report', function ($sheet) use ($orderArray) {

					$sheet->fromArray($orderArray);
				});
			})->export('xls');
		}


		return $this->res->withSuccessData($orderArray);
	}

	public function details(Request $request, Order $order)
	{
		$query = $order->newQuery();

		if ($request->has("customer_id") && $request->get("customer_id") != 0) {
			$query->where(function ($q) use ($request) {

				$q->where('customer_id', $request->get("customer_id"));
			});
		}

		if ($request->has("driver_id") && $request->get("driver_id") != 0) {
			$driverId = $request->get("driver_id");

			// -1 is for any assigned
			// -2 is for unassigned
			if ($driverId == -1) {
				$query->where(function ($q) use ($request) {

					$q->where('driver_id', "!=", null);
				});
			} else if ($driverId == -2) {
				$query->where(function ($q) use ($request) {

					$q->where('driver_id', null);
				});
			} else {
				$query->where(function ($q) use ($request) {

					$q->where('driver_id', $request->get("driver_id"));
				});
			}
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

		if ($request->has("type") && in_array($request->get("type"), ['service', 'invoice'])) {
			$query->where(function ($q) use ($request) {

				$q->where('type', $request->get("type"));
			});
		}

		if ($request->has("due")) {
			$query->where(function ($q) use ($request) {

				$q->whereDate('schedule_at', $request->get("due"));
			});
		}


		if ($request->has("created_start")  && $request->has("created_end")) {
			$query->where(function ($q) use ($request) {


				$q->whereBetween('created_at', [$request->get("created_start"), $request->get("created_end")]);
			});
		}

		$orders = $query->with(['driver', 'warehouse', 'customer', 'createdBy'])->get();

		$orderArray = [];

		$orders->transform(function ($order) use (&$orderArray) {
			$od = [];
			$od['Id'] = $order->id;
			$od['Order Id'] = $order->invoice_id;
			$od['Created At'] = date("F j, Y, g:i a", strtotime($order->created_at));
			$od['Updated At'] = date("F j, Y, g:i a", strtotime($order->updated_at));
			$od['Type'] = $order->type;
			$od['Due'] = date("F j, Y, g:i a", strtotime($order->schedule_at));
			$od['Completed At'] = $order->completed ? "Yes" : "No";

			if ($order->driver)
				$od['Driver'] = $order->driver->first_name . ' ' . $order->driver->last_name;
			else
				$od['Driver'] = "";

			$od['Customer'] = $order->customer->name;

			if ($order->warehouse) {
				$od['Warehouse'] = $order->warehouse->name;
				$od["Checked Off By"] = $order->warehouse_signee;
			} else
				$od['Warehouse'] = "";

			$od['Created By'] = $order->createdBy->first_name . ' ' . $order->createdBy->last_name;

			$od['Received By'] = $order->signee;
			$orderArray[] = $od;

			return $order;
		});

		$options = (object) $request->only(['includeTasks', 'includeInvoice', 'includeNotes', 'includeAuditLogs']);

		$spreadsheet = new Spreadsheet();
		$sheet = $spreadsheet->getActiveSheet();
		$writer = new ReportDetailWriter();
		$rowIndex = 1;
		foreach ($orders as $order) {
			$writer->writeOrder($sheet, $order, $rowIndex, $options);
			$rowIndex++;
		}
		//$sheet->setCellValue('A1', 'Hello World !');

		$excel = new Xlsx($spreadsheet);
		//$excel->save('php://download');

		// https://stackoverflow.com/questions/50993968/download-phpspreadsheet-file-without-save-it-before
		//return $this->res->withSuccessData($orderArray);

		$response =  new StreamedResponse(
			function () use ($excel) {
				$excel->save('php://output');
			}
		);
		$response->headers->set('Content-Type', 'application/vnd.ms-excel');
		$response->headers->set('Content-Disposition', 'attachment;filename="Report.xls"');
		$response->headers->set('Cache-Control', 'max-age=0');
		return $response;
	}
}
