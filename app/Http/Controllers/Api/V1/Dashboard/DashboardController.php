<?php

namespace App\Http\Controllers\Api\V1\Dashboard;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\ApiResponse;
use App\Order;
use App\User;
use App\Warehouse;
use App\Customer;
use App\Fleet;
use DB;
use Validator;
use Illuminate\Support\Facades\Hash;
use Mail;
use Carbon\Carbon;

class DashboardController extends Controller
{
  //

  public function __construct()
  {
    $this->res = new ApiResponse();
  }

   /**
   * @OA\Get(
   *     path="/api/v1/dashboard",
   *     summary="Get dashboard metrics",
   *     description="returns asset count, most recent orders and time series data for orders",
   *     tags={"Dashboard"},
   *     security={ {"bearer": {}} },
   *     @OA\Response(response=200,description="successful operation",@OA\JsonContent()),
   *     @OA\Response(response=400,description="validation/server error",),
   *     @OA\Response(response=401,description="validation/server error",)
   * )
   */

  public function __invoke()
  {
     $stats['users'] = User::count();
     $stats['orders'] = Order::count();
     $stats['warehouses'] = Warehouse::count();
     $stats['customers'] = Customer::count();
     $stats['fleet'] = Fleet::count();


     $stats['most_recent_orders'] = Order::with(['customer', 'driver', 'createdBy', 'warehouse','checklist'])->orderBy('updated_at', 'DESC')->limit(10)->get();


     $stats['time_series_orders'] = Order::selectRaw('COUNT(*) as count, YEAR(created_at) year, MONTH(created_at) month')
     ->where(function($query){

      $query->whereBetween('created_at', [
            Carbon::now()->startOfYear(),
            Carbon::now()->endOfYear(),
        ]);
     })
    ->groupBy('year', 'month')
    ->get();


     return $this->res->withSuccessData($stats);
  }



}
