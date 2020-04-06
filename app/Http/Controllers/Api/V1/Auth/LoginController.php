<?php

namespace App\Http\Controllers\Api\V1\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\ApiResponse;
use Validator;
use Illuminate\Support\Facades\Auth;
use JWTAuth;
use Storage;


class LoginController extends Controller
{
    /*
    POST 
    accept email, password
    */

    public $res; //short for response

    public function __construct()
    {
    	$this->res = new ApiResponse();
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/login",
     *     summary="Login to oms",
     *     description="Login to oms ",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                @OA\Property(
     *                   property="email",
     *                   description="email associated with account.",
     *                   type="string",
     *                ),
     *                @OA\Property(
     *                   property="password",
     *                   description="password.",
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

    public function login(Request $request)
    {

        $credentials = request(['email', 'password']);

        $validator = Validator::make($credentials, [
            'email' => 'required|email',
            'password'  => 'required',
        ]);

        if($validator->fails())
        {
            return $this->res->withError($validator->errors()->toArray(), 400);
        }
    

    	if (!$token = auth('api')->attempt($credentials))
    	{
        	return $this->res->withError("Invalid credentials ", 400);
    	}

    	return $this->res->withSuccessData([
            'token' => $token,
            'token_type' => 'bearer',
            'expires' => auth('api')->factory()->getTTL() * 60,
        ]);
    }


    /**
     * @OA\Get(
     *     path="/api/v1/auth/refresh-token",
     *     summary="Refresh token oms",
     *     description="Refresh token oms ",
     *     tags={"Authentication"},
     *     security={ {"bearer": {}} },
     *     @OA\Response(response=200,description="successful operation",@OA\JsonContent()),
     *     @OA\Response(response=400,description="validation/server error",),
     *     @OA\Response(response=401,description="validation/server error",)
     * )
     */

    public function refresh()
    {
        $token = JWTAuth::getToken();
        $new_token = JWTAuth::refresh($token);

        return $this->res->withSuccessData([
            'token' => $new_token,
            'token_type' => 'bearer',
            'expires' => auth('api')->factory()->getTTL() * 60
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/auth/logout",
     *     summary="logout oms",
     *     description="logout oms",
     *     tags={"Authentication"},
     *     security={ {"bearer": {}} },
     *     @OA\Response(response=200,description="successful operation",@OA\JsonContent()),
     *     @OA\Response(response=400,description="validation/server error",),
     *     @OA\Response(response=401,description="validation/server error",)
     * )
     */

    public function logout()
    {
        auth()->logout();

        return $this->res->withSuccess("Successfully logged out");
    }

}
