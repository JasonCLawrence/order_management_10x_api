<?php

namespace App\Http\Controllers\Api\V1\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use App\Http\ApiResponse;
use App\User;
use App\UserRole;
use App\Mail\AccountCreated;
use DB;
use Validator;
use Mail;

class UserController extends Controller
{
  public function __construct()
  {
    //$this->middleware('user.current_company');

    $this->res = new ApiResponse();
  }

  /**
   * @OA\Get(
   *     path="/api/v1/user/all",
   *     summary="Get users",
   *     description="users oms",
   *     tags={"User"},
   *     security={ {"bearer": {}} },
   *     @OA\Response(response=200,description="successful operation",@OA\JsonContent()),
   *     @OA\Response(response=400,description="validation/server error",),
   *     @OA\Response(response=401,description="validation/server error",)
   * )
   */

  public function all(Request $request, User $user)
  {

    $query = $user->newQuery();

    $query->orderBy('last_name', 'ASC')->with("roles");
    $users = $query->get();

    return $this->res->withSuccessData($users);

  }

  /**
   * @OA\Get(
   *     path="/api/v1/user",
   *     summary="Get users",
   *     description="users oms",
   *     tags={"User"},
   *     security={ {"bearer": {}} },
   *     @OA\Response(response=200,description="successful operation",@OA\JsonContent()),
   *     @OA\Response(response=400,description="validation/server error",),
   *     @OA\Response(response=401,description="validation/server error",)
   * )
   */

  public function query(Request $request, User $user)
  {

    $query = $user->newQuery();

    if($request->has("q") && $request->get("q")!=null)
    {
      $query->where(function($q) use($request){
        $q->where('first_name', 'LIKE', '%'.$request->get("q").'%')
          ->orWhere->where('last_name', 'LIKE', '%'.$request->get("q").'%')
          ->orWhere->where('email', 'LIKE', '%'.$request->get("q").'%');
      });
    }

    $query->orderBy('last_name', 'ASC')->with("roles");
    $users = $query->paginate(15);

    return $this->res->withSuccessData($users);

  }

  /**
   * @OA\Get(
   *     path="/api/v1/user/me",
   *     summary="Get my details",
   *     description="me endpoint oms",
   *     tags={"User"},
   *     security={ {"bearer": {}} },
   *     @OA\Response(response=200,description="successful operation",@OA\JsonContent()),
   *     @OA\Response(response=400,description="validation/server error",),
   *     @OA\Response(response=401,description="validation/server error",)
   * )
   */
  public function me()
  {
    $user = auth()->user();

    return $this->res->withSuccessData([
      'user'=>$user,
    ]);
  }


  /**
   * @OA\Get(
   *     path="/api/v1/user/roles",
   *     summary="Get my roles",
   *     description="roles endpoint oms",
   *     tags={"User"},
   *     security={ {"bearer": {}} },
   *     @OA\Response(response=200,description="successful operation",@OA\JsonContent()),
   *     @OA\Response(response=400,description="validation/server error",),
   *     @OA\Response(response=401,description="validation/server error",)
   * )
   */
  public function roles()
  {
    $user = auth()->user();

    return $this->res->withSuccessData([
      'roles'=>$user->roles,
    ]);
  }

  /**
   * @OA\Post(
   *     path="/api/v1/user",
   *     summary="update user password",
   *     description="update user password",
   *     tags={"User"},
   *     security={ {"bearer": {}} },
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\MediaType(
   *             mediaType="application/json",
   *             @OA\Schema(
   *                @OA\Property(
   *                   property="first_name",
   *                   description="first name",
   *                   type="string",
   *                ),
   *                @OA\Property(
   *                   property="last_name",
   *                   description="last name",
   *                   type="string",
   *                ),
   *                @OA\Property(
   *                   property="email",
   *                   description="email",
   *                   type="string",
   *                ),
   *                @OA\Property(
   *                   property="password",
   *                   description="password",
   *                   type="string",
   *                ),
  *                @OA\Property(
   *                   property="roles",
   *                   description="roles array like ['administrator','driver'].",
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

    // return $request;
     $data = request(['roles','first_name', 'last_name', 'password', 'email']);

        $validator = Validator::make($data, [
            'roles' => 'array|required',
            'email' => 'email|required|unique:users',
            'first_name' => 'string|required',
            'last_name' => 'string|required',
            'password' => 'string|required|min:5',
        ]);

        if($validator->fails())
        {
            return $this->res->withError($validator->errors()->toArray(), 400);
        }


        $roles = ["administrator", "driver"];

        $roles_to_give = array_intersect($roles, $request->get("roles"));

        if(count($roles_to_give)==0)
        {
          return $this->res->withError("Please provide one or the combination of following roles ".implode(",", $roles), 400);
        }

        // create user
        $user = new User;
        $user->first_name = $request->get("first_name");
        $user->last_name = $request->get("last_name");
        $user->email = $request->get("email");
        $user->password = \Hash::make($request->get("password"));
        $user->save();

        // assign roles
        $user->assignRoles($roles_to_give);
        $user->roles = $user->roles;

        // email user about account
        Mail::to($user)->send(new AccountCreated($user));

        return $this->res->withSuccessData($user);
  }

  /**
   * @OA\Put(
   *     path="/api/v1/user",
   *     summary="update user password",
   *     description="update user password",
   *     tags={"User"},
   *     security={ {"bearer": {}} },
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\MediaType(
   *             mediaType="application/json",
   *             @OA\Schema(
   *                @OA\Property(
   *                   property="first_name",
   *                   description="first name",
   *                   type="string",
   *                ),
   *                @OA\Property(
   *                   property="last_name",
   *                   description="last name",
   *                   type="string",
   *                ),
  *                @OA\Property(
   *                   property="roles",
   *                   description="roles array like ['administrator','driver'].",
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

  public function updateUser($id, Request $request)
  {
     $data = request(['roles','first_name', 'last_name']);

        $validator = Validator::make($data, [
            'roles' => 'array|required',
            'first_name' => 'string|required',
            'last_name' => 'string|required',
        ]);

        if($validator->fails())
        {
            return $this->res->withError($validator->errors()->toArray(), 400);
        }


        $user = User::find($id);
        if($user==null)
        {
          return $this->res->withError("Invalid User", 400);
        }

        if($user->id==auth()->user()->id && $user->hasRole("administrator") && !in_array("administrator", $request->get("roles")))
        {
          return $this->res->withError("You cannot remove administrator role from yourself", 400);
        }

        $roles = ["administrator", "driver"];

        $roles_to_update = array_intersect($roles, $request->get("roles"));

        if(count($roles_to_update)==0)
        {
          return $this->res->withError("Please provide one or the combination of following roles ".implode(",", $roles), 400);
        }

        // update user
        $user->first_name = $request->get("first_name");
        $user->last_name = $request->get("last_name");
        $user->save();

        // assign roles
        $user->assignRoles($roles_to_update);
        $user->roles = $user->roles;

        return $this->res->withSuccessData($user);


  }

  /**
   * @OA\Get(
   *     path="/api/v1/user/{id}",
   *     summary="Get user by id",
   *     description="user oms",
   *     tags={"User"},
   *     security={ {"bearer": {}} },
   *     @OA\Response(response=200,description="successful operation",@OA\JsonContent()),
   *     @OA\Response(response=400,description="validation/server error",),
   *     @OA\Response(response=401,description="validation/server error",)
   * )
   */

  public function findById($id, Request $request)
  {
    $user = User::find($id);
    if ($user != null) {
      $user->roles = $user->roles;
      return $this->res->withSuccessData($user);
    }
    return $this->res->withError("user doesnt exist", 404);

  }

  /**
   * @OA\Put(
   *     path="/api/v1/user/{id}/roles",
   *     summary="update user roles",
   *     description="update user roles",
   *     tags={"User"},
   *     security={ {"bearer": {}} },
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\MediaType(
   *             mediaType="application/json",
   *             @OA\Schema(
   *                @OA\Property(
   *                   property="roles",
   *                   description="roles array like ['administrator','driver'].",
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


  public function updateRoles($id, Request $request)
  {

      $data = request(['roles']);

        $validator = Validator::make($data, [
            'roles' => 'array|required',
        ]);

        if($validator->fails())
        {
            return $this->res->withError($validator->errors()->toArray(), 400);
        }

        $user = User::find($id);
        if($user==null)
        {
          return $this->res->withError("Invalid User", 400);
        }

        if($user->id==auth()->user()->id && $user->hasRole("administrator") && !in_array("administrator", $request->get("roles")))
        {
          return $this->res->withError("You cannot remove administrator role from yourself", 400);
        }

        $roles = ["administrator", "driver"];

        $roles_to_update = array_intersect($roles, $request->get("roles"));

        if(count($roles_to_update)==0)
        {
          return $this->res->withError("Please provide one or the combination of following roles ".implode(",", $roles), 400);
        }

        $user->assignRoles($roles_to_update);

        return $this->res->withSuccess("Roles updated");
  }

  /**
   * @OA\Put(
   *     path="/api/v1/user/{id}/password",
   *     summary="update user password",
   *     description="update user password",
   *     tags={"User"},
   *     security={ {"bearer": {}} },
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\MediaType(
   *             mediaType="application/json",
   *             @OA\Schema(
   *                @OA\Property(
   *                   property="password",
   *                   description="password",
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


  public function changePassword($id, Request $request)
  {
    $data = request(['password']);

        $validator = Validator::make($data, [
            'password' => 'string|required|min:5',
        ]);

        if($validator->fails())
        {
            return $this->res->withError($validator->errors()->toArray(), 400);
        }

        $user = User::find($id);
        if($user==null)
        {
          return $this->res->withError("Invalid User", 400);
        }

        $user->password = \Hash::make($request->get("password"));
        $user->save();

        return $this->res->withSuccess("Password changed");


  }

    /**
   * @OA\Post(
   *     path="/api/v1/user/{id}/password",
   *     summary="delete",
   *     description="delete user",
   *     tags={"User"},
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

  public function delete($id)
  {
       $user = User::find($id);
      if($user==null)
      {
        return $this->res->withError("Invalid User", 400);
      }

      if($user->id==auth()->user()->id)
      {
        return $this->res->withError("You cannot delete yourself");
      }

      $user->delete();

      return $this->res->withSuccess("User deleted");


  }

   /**
   * @OA\Post(
   *     path="/api/v1/user/{id}/email",
   *     summary="email",
   *     description="change user email",
   *     tags={"User"},
   *     security={ {"bearer": {}} },
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\MediaType(
   *             mediaType="application/json",
   *             @OA\Schema(
   *                @OA\Property(
   *                   property="email",
   *                   description="email",
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

  public function updateEmail($id, Request $request)
  {
      $data = request(['email']);

      $validator = Validator::make($data, [
        'email' => 'email|required',
      ]);

      if($validator->fails())
      {
        return $this->res->withError($validator->errors()->toArray(), 400);
      }

      $user = User::find($id);
      if($user==null)
      {
        return $this->res->withError("Invalid User", 400);
      }

      //check to see if anyone else has this email

      $has_email = User::where('email', $request->get("email"))->where("id", "<>", $user->id)->first();

      if($has_email!=null)
      {
        return $this->res->withError("Email already taken", 400);
      }

      $user->email = $request->get("email");
      $user->save();



      return $this->res->withSuccess("User email updated");


  }


   /**
   * @OA\Put(
   *     path="/api/v1/user/me/update_location",
   *     summary="email",
   *     description="change user email",
   *     tags={"User"},
   *     security={ {"bearer": {}} },
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\MediaType(
   *             mediaType="application/json",
   *             @OA\Schema(
   *                @OA\Property(
   *                   property="long",
   *                   description="long",
   *                   type="integer",
   *                ),
   *                @OA\Property(
   *                   property="lat",
   *                   description="lat",
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

  public function updateLocation(Request $request)
  {
      $data = request(['long', 'lat']);

      $validator = Validator::make($data, [
        'long' => 'numeric|required',
        'lat' => 'numeric|required',
      ]);

      if($validator->fails())
      {
        return $this->res->withError($validator->errors()->toArray(), 400);
      }

      $user = User::find(auth()->user()->id);
      $user->long = $request->get("long");
      $user->lat = $request->get("lat");
      $user->save();

      return $this->res->withSuccess("OK");

  }

   /**
   * @OA\Get(
   *     path="/api/v1/user/drivers/location",
   *     summary="Get my details",
   *     description="me endpoint oms",
   *     tags={"User"},
   *     security={ {"bearer": {}} },
   *     @OA\Response(response=200,description="successful operation",@OA\JsonContent()),
   *     @OA\Response(response=400,description="validation/server error",),
   *     @OA\Response(response=401,description="validation/server error",)
   * )
   */

  public function driverLocation()
  {
      $users =  UserRole::join('roles', 'roles.id','=','user_roles.role_id')
        ->where('roles.name', "driver")
        ->join("users", "user_roles.user_id", '=', 'users.id')
        ->select("users.first_name", "users.last_name", "users.long", "users.lat")
        ->get();

        return $this->res->withSuccessData($users);
  }




}
