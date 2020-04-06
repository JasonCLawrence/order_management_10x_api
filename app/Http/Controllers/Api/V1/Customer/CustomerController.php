<?php

namespace App\Http\Controllers\Api\V1\Customer;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\ApiResponse;
use App\Customer;
use DB;
use Validator;
use Illuminate\Support\Facades\Hash;
use Mail;

class CustomerController extends Controller
{
  //

  public function __construct()
  {

    $this->res = new ApiResponse();
  }

  /**
   * @OA\Get(
   *     path="/api/v1/customer/all",
   *     summary="Get customers",
   *     description="customers oms",
   *     tags={"Customer"},
   *     security={ {"bearer": {}} },
   *     @OA\Response(response=200,description="successful operation",@OA\JsonContent()),
   *     @OA\Response(response=400,description="validation/server error",),
   *     @OA\Response(response=401,description="validation/server error",)
   * )
   */

  public function all(Request $request, Customer $customer)
  {

    $query = $customer->newQuery();

    $query->orderBy('name', 'ASC');
    $customers = $query->get();

    return $this->res->withSuccessData($customers);

  }

  /**
   * @OA\Get(
   *     path="/api/v1/customer",
   *     summary="Get customers",
   *     description="customers oms",
   *     tags={"Customer"},
   *     security={ {"bearer": {}} },
   *     @OA\Response(response=200,description="successful operation",@OA\JsonContent()),
   *     @OA\Response(response=400,description="validation/server error",),
   *     @OA\Response(response=401,description="validation/server error",)
   * )
   */

  public function query(Request $request, Customer $customer)
  {

    $query = $customer->newQuery();

    if($request->has("q") && $request->get("q")!=null)
    {
      $query->where(function($q) use($request){
        $q->where('name', 'LIKE', '%'.$request->get("q").'%')
          ->orWhere->where('email', 'LIKE', '%'.$request->get("q").'%');
      });
    }

    $query->orderBy('name', 'ASC');
    $customers = $query->paginate(15);

    return $this->res->withSuccessData($customers);

  }

  /**
   * @OA\Get(
   *     path="/api/v1/customer/{id}",
   *     summary="Get customer details",
   *     description="Find a customer",
   *     tags={"Customer"},
   *     security={ {"bearer": {}} },
   *     @OA\Response(response=200,description="successful operation",@OA\JsonContent()),
   *     @OA\Response(response=400,description="validation/server error",),
   *     @OA\Response(response=401,description="validation/server error",)
   * )
   */

  public function show($id)
  {
    $customer = Customer::find($id);

    if($customer==null)
    {
      return $this->res->withError("Invalid customer", 400);
    }

    return $this->res->withSuccessData($customer);

  }

  /**
   * @OA\Delete(
   *     path="/api/v1/customer/{id}",
   *     summary="Get customer details",
   *     description="Find a customer",
   *     tags={"Customer"},
   *     security={ {"bearer": {}} },
   *     @OA\Response(response=200,description="successful operation",@OA\JsonContent()),
   *     @OA\Response(response=400,description="validation/server error",),
   *     @OA\Response(response=401,description="validation/server error",)
   * )
   */

  public function delete($id)
  {
    $customer = Customer::find($id);

    if($customer==null)
    {
      return $this->res->withError("Invalid customer", 400);
    }

    $customer->delete();

    return $this->res->withSuccess("Deleted successfully");

  }



    /**
     * @OA\Put(
     *     path="/api/v1/customer/{id}",
     *     summary="update customer",
     *     description="update ",
     *     tags={"Customer"},
     *     security={ {"bearer": {}} },
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                @OA\Property(
     *                   property="name",
     *                   description="name.",
     *                   type="string",
     *                ),
     *                @OA\Property(
     *                   property="email",
     *                   description="email.",
     *                   type="string",
     *                ),
     *                @OA\Property(
     *                   property="address",
     *                   description="address.",
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

  public function update($id, Request $request)
  {
      $data = request(['name','email']);

        $validator = Validator::make($data, [
            'name' => 'string|required',
            'email'  => 'string|nullable',
            'address'  => 'string|nullable',
            'lat'  => 'numeric|nullable',
            'long'  => 'numeric|nullable',
        ]);

        if($validator->fails())
        {
            return $this->res->withError($validator->errors()->toArray(), 400);
        }

        $customer = Customer::find($id);

        if($customer==null)
        {
          return $this->res->withError("Invalid customer", 400);
        }

        $customer->name = $request->get("name");
        $customer->email = $request->get("email");
        $customer->address = $request->get("address");
        $customer->lat = $request->get("lat");
        $customer->long = $request->get("long");
        $customer->save();

        return $this->res->withSuccessData($customer);


  }


      /**
     * @OA\Post(
     *     path="/api/v1/customer",
     *     summary="create customer",
     *     description="create ",
     *     tags={"Customer"},
     *     security={ {"bearer": {}} },
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                @OA\Property(
     *                   property="name",
     *                   description="name.",
     *                   type="string",
     *                ),
     *                @OA\Property(
     *                   property="email",
     *                   description="email.",
     *                   type="string",
     *                ),
     *                @OA\Property(
     *                   property="address",
     *                   description="address.",
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

  public function create(Request $request)
  {
      $data = request(['name','email']);

        $validator = Validator::make($data, [
            'name' => 'string|required',
            'email'  => 'string|nullable',
            'address'  => 'string|nullable',
            'lat'  => 'numeric|nullable',
            'long'  => 'numeric|nullable',
        ]);

        if($validator->fails())
        {
            return $this->res->withError($validator->errors()->toArray(), 400);
        }

        $customer = new Customer;

        $customer->name = $request->get("name");
        $customer->email = $request->get("email");
        $customer->address = $request->get("address");
        $customer->lat = $request->get("lat");
        $customer->long = $request->get("long");
        $customer->save();

        return $this->res->withSuccessData($customer);


  }


  

   
}
