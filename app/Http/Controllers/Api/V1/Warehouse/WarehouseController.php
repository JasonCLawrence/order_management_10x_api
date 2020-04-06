<?php

namespace App\Http\Controllers\Api\V1\Warehouse;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\ApiResponse;
use App\Warehouse;
use App\User;
use DB;
use Validator;
use Illuminate\Support\Facades\Hash;
use Mail;

class WarehouseController extends Controller
{
  //

  public function __construct()
  {

    $this->res = new ApiResponse();
  }

   /**
   * @OA\Get(
   *     path="/api/v1/warehouse/all",
   *     summary="Get all warehouse",
   *     description="warehouse oms",
   *     tags={"Warehouse"},
   *     security={ {"bearer": {}} },
   *     @OA\Response(response=200,description="successful operation",@OA\JsonContent()),
   *     @OA\Response(response=400,description="validation/server error",),
   *     @OA\Response(response=401,description="validation/server error",)
   * )
   */

  public function all(Request $request, Warehouse $warehouse)
  {
    $query = $warehouse->newQuery();

    $query->orderBy('name', 'ASC');
    $item = $query->get();

    return $this->res->withSuccessData($item);

  }

    /**
   * @OA\Get(
   *     path="/api/v1/warehouse",
   *     summary="Get all warehouse",
   *     description="warehouse oms",
   *     tags={"Warehouse"},
   *     security={ {"bearer": {}} },
   *     @OA\Response(response=200,description="successful operation",@OA\JsonContent()),
   *     @OA\Response(response=400,description="validation/server error",),
   *     @OA\Response(response=401,description="validation/server error",)
   * )
   */

  public function query(Request $request, Warehouse $warehouse)
  {
    $query = $warehouse->newQuery();

    if($request->has("q") && $request->get("q")!=null)
    {
      $query->where(function($q) use($request){
        $q->where('name', 'LIKE', '%'.$request->get("q").'%')
          ->orWhere->where('address', 'LIKE', '%'.$request->get("q").'%');
      });
    }

    $query->orderBy('name', 'ASC');
    $item = $query->paginate(15);

    return $this->res->withSuccessData($item);

  }

  /**
   * @OA\Get(
   *     path="/api/v1/warehouse/{id}",
   *     summary="Get warehouse details",
   *     description="Find a warehouse",
   *     tags={"Warehouse"},
   *     security={ {"bearer": {}} },
   *     @OA\Response(response=200,description="successful operation",@OA\JsonContent()),
   *     @OA\Response(response=400,description="validation/server error",),
   *     @OA\Response(response=401,description="validation/server error",)
   * )
   */

  public function show($id)
  {
    $warehouse = Warehouse::find($id);

    if($warehouse==null)
    {
      return $this->res->withError("Invalid warehouse", 400);
    }

    return $this->res->withSuccessData($warehouse);

  }

  /**
   * @OA\Delete(
   *     path="/api/v1/warehouse/{id}",
   *     summary="delete warehouse",
   *     description="delete",
   *     tags={"Warehouse"},
   *     security={ {"bearer": {}} },
   *     @OA\Response(response=200,description="successful operation",@OA\JsonContent()),
   *     @OA\Response(response=400,description="validation/server error",),
   *     @OA\Response(response=401,description="validation/server error",)
   * )
   */

  public function delete($id)
  {
    $warehouse = Warehouse::find($id);

    if($warehouse==null)
    {
      return $this->res->withError("Invalid warehouse", 400);
    }

    $warehouse->delete();

    return $this->res->withSuccess("Deleted successfully");

  }



    /**
     * @OA\Put(
     *     path="/api/v1/warehouse/{id}",
     *     summary="update fleet",
     *     description="update ",
     *     tags={"Warehouse"},
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
            'name' => 'string|required',
            'address'  => 'string|nullable',
        ]);

        if($validator->fails())
        {
            return $this->res->withError($validator->errors()->toArray(), 400);
        }

        $warehouse = Warehouse::find($id);

        if($warehouse==null)
        {
          return $this->res->withError("Invalid warehouse", 400);
        }

        $warehouse->name = $request->get("name");
        $warehouse->address = $request->get("address");
        $warehouse->save();

        return $this->res->withSuccessData($warehouse);


  }


      /**
     * @OA\Post(
     *     path="/api/v1/warehouse",
     *     summary="create warehouse",
     *     description="create ",
     *     tags={"Warehouse"},
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
            'name' => 'string|required',
            'address'  => 'string|nullable',
        ]);

        if($validator->fails())
        {
            return $this->res->withError($validator->errors()->toArray(), 400);
        }

        $warehouse = new Warehouse;

        $warehouse->name = $request->get("name");
        $warehouse->address = $request->get("address");

        $warehouse->save();

        return $this->res->withSuccessData($warehouse);


  }


   
}
