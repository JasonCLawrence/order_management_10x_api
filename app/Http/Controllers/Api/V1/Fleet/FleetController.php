<?php

namespace App\Http\Controllers\Api\V1\Fleet;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\ApiResponse;
use App\Fleet;
use App\User;
use App\VehicleDriver;
use DB;
use Validator;
use Illuminate\Support\Facades\Hash;
use Mail;

class FleetController extends Controller
{
  //

  public function __construct()
  {

    $this->res = new ApiResponse();
  }

    /**
   * @OA\Get(
   *     path="/api/v1/fleet",
   *     summary="Get all fleet",
   *     description="fleet oms",
   *     tags={"Fleet"},
   *     security={ {"bearer": {}} },
   *     @OA\Response(response=200,description="successful operation",@OA\JsonContent()),
   *     @OA\Response(response=400,description="validation/server error",),
   *     @OA\Response(response=401,description="validation/server error",)
   * )
   */

  public function all(Request $request, Fleet $fleet)
  {

    $query = $fleet->newQuery();

    if($request->has("q") && $request->get("q")!=null)
    {
      $query->where(function($q) use($request){
        $q->where('name', 'LIKE', '%'.$request->get("q").'%')
          ->orWhere->where('model', 'LIKE', '%'.$request->get("q").'%');
      });
    }

    $query->orderBy('name', 'ASC')->with("driver");
    $users = $query->paginate(15);

    return $this->res->withSuccessData($users);

  }


  /**
   * @OA\Get(
   *     path="/api/v1/fleet/{id}",
   *     summary="Get fleet details",
   *     description="Find a fleet",
   *     tags={"Fleet"},
   *     security={ {"bearer": {}} },
   *     @OA\Response(response=200,description="successful operation",@OA\JsonContent()),
   *     @OA\Response(response=400,description="validation/server error",),
   *     @OA\Response(response=401,description="validation/server error",)
   * )
   */

  public function show($id)
  {
    $fleet = Fleet::with('driver')->find($id);

    if($fleet==null)
    {
      return $this->res->withError("Invalid vehicle", 400);
    }

    return $this->res->withSuccessData($fleet);

  }

  /**
   * @OA\Delete(
   *     path="/api/v1/fleet/{id}",
   *     summary="delete fleet",
   *     description="delete",
   *     tags={"Fleet"},
   *     security={ {"bearer": {}} },
   *     @OA\Response(response=200,description="successful operation",@OA\JsonContent()),
   *     @OA\Response(response=400,description="validation/server error",),
   *     @OA\Response(response=401,description="validation/server error",)
   * )
   */

  public function delete($id)
  {
    $vehicle = Fleet::find($id);

    if($vehicle==null)
    {
      return $this->res->withError("Invalid vehicle", 400);
    }

    $vehicle->delete();

    return $this->res->withSuccess("Deleted successfully");

  }



    /**
     * @OA\Put(
     *     path="/api/v1/fleet/{id}",
     *     summary="update fleet",
     *     description="update ",
     *     tags={"Fleet"},
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
     *                   property="description",
     *                   description="description.",
     *                   type="string",
     *                ),
     *                @OA\Property(
     *                   property="registration",
     *                   description="registration.",
     *                   type="string",
     *                ),
     *                @OA\Property(
     *                   property="model",
     *                   description="model.",
     *                   type="string",
     *                ),
     *                @OA\Property(
     *                   property="color",
     *                   description="color.",
     *                   type="string",
     *                ),
     *                @OA\Property(
     *                   property="driver_id",
     *                   description="can be nullable.",
     *                   type="integer",
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
            'registration'  => 'string|required',
            'model'  => 'string|nullable',
            'description'  => 'string|nullable',
            'color'  => 'string|nullable',
            'driver_id'  => 'integer|nullable',
        ]);

        if($validator->fails())
        {
            return $this->res->withError($validator->errors()->toArray(), 400);
        }

        $fleet = Fleet::find($id);

        if($fleet==null)
        {
          return $this->res->withError("Invalid fleet", 400);
        }

        DB::beginTransaction();

        $fleet->name = $request->get("name");
        $fleet->registration = $request->get("registration");
        $fleet->model = $request->get("model");
        $fleet->description = $request->get("description");
        $fleet->color = $request->get("color");
        $fleet->save();


        if($request->has("driver_id"))
        {
          $driver = User::find($request->get("driver_id"));
          if($driver==null)
          {
            DB::rollback();
            return $this->res->withError("Driver does not exists");
          }

          if(!$driver->hasRole("driver"))
          {
            DB::rollback();
            return $this->res->withError("User does not have driver role");
          }

          $vd = VehicleDriver::where('fleet_id', $fleet->id)->first();
          if($vd==null)
          {
              $vd = new VehicleDriver;
              $vd->fleet_id = $fleet->id;
          }

          $vd->user_id = $driver->id;
          $vd->save();

        }


        DB::commit();

        $fleet->driver = $fleet->driver;


        return $this->res->withSuccessData($fleet);


  }


      /**
     * @OA\Post(
     *     path="/api/v1/fleet",
     *     summary="create fleet",
     *     description="create ",
     *     tags={"Fleet"},
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
     *                   property="description",
     *                   description="description.",
     *                   type="string",
     *                ),
     *                @OA\Property(
     *                   property="registration",
     *                   description="registration.",
     *                   type="string",
     *                ),
     *                @OA\Property(
     *                   property="model",
     *                   description="model.",
     *                   type="string",
     *                ),
     *                @OA\Property(
     *                   property="color",
     *                   description="color.",
     *                   type="string",
     *                ),
     *                @OA\Property(
     *                   property="driver_id",
     *                   description="can be nullable.",
     *                   type="integer",
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
            'registration'  => 'string|required',
            'model'  => 'string|nullable',
            'description'  => 'string|nullable',
            'color'  => 'string|nullable',
            'driver_id'  => 'integer|nullable',
        ]);

        if($validator->fails())
        {
            return $this->res->withError($validator->errors()->toArray(), 400);
        }

        DB::beginTransaction();

        $fleet = new Fleet;

        $fleet->name = $request->get("name");
        $fleet->registration = $request->get("registration");
        $fleet->model = $request->get("model");
        $fleet->description = $request->get("description");
        $fleet->color = $request->get("color");
        $fleet->save();

        if($request->has("driver_id"))
        {
          $driver = User::find($request->get("driver_id"));
          if($driver==null)
          {
            DB::rollback();
            return $this->res->withError("Driver does not exists");
          }

          if(!$driver->hasRole("driver"))
          {
            DB::rollback();
            return $this->res->withError("User does not have driver role");
          }

          $vd = new VehicleDriver;
          $vd->fleet_id = $fleet->id;
          $vd->user_id = $driver->id;
          $vd->save();

        }

        DB::commit();

        $fleet->driver = $fleet->driver;

        return $this->res->withSuccessData($fleet);


  }



   
}
