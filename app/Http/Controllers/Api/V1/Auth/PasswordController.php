<?php

namespace App\Http\Controllers\Api\V1\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\ApiResponse;
use Validator;
use Illuminate\Support\Facades\Auth;
use JWTAuth;
use Hash;
use App\PasswordReset;
use App\Mail\SendPasswordLink;
use Mail;
use App\User;


class PasswordController extends Controller
{
 
    public $res; //short for response

    public function __construct()
    {
    	$this->res = new ApiResponse();
    }

   /**
   * @OA\Put(
   *     path="/api/v1/auth/change_password",
   *     summary="Update password",
   *     description="",
   *     tags={"Password"},
   *     security={ {"bearer": {}} },
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\MediaType(
   *             mediaType="application/json",
   *             @OA\Schema(
   *                @OA\Property(property="current_password", description="Current password", type="string",),
   *                @OA\Property(property="password", description="new password", type="string",),
   *                @OA\Property(property="password_confirmation", description="confirm new password", type="string",),
   *             ),
   *         ),
   *     ),
   *     @OA\Response(response=200,description="successful operation",@OA\JsonContent()),
   *     @OA\Response(response=400,description="validation/server error",),
   *     @OA\Response(response=401,description="validation/server error",)
   * )
   */

    public function change(Request $request)
    {
        $validator = Validator::make($request->all(), [
        'current_password'  => 'required',
        'password'=>'required|min:6|confirmed',
        'password_confirmation'=>'sometimes|min:6|required_with:password',

        ]);

    
        $validator->after(function($validator)use($request){

            if(!Hash::check($request->get("current_password"), auth()->user()->password))
            {
                $validator->errors()->add("current_password", "Current password invalid");
            }
        });

        if($validator->fails())
        {
                if($validator->fails())
                {
                    return $this->res->withError($validator->errors()->toArray(), 400);
                }
        }

        $u = auth()->user();

        $u->password = Hash::make($request->get("password"));


        if($u->change_password && $u->email_verified_at==null)
        {
          $u->email_verified_at = \Carbon\Carbon::now()->format("Y-m-d H:i:s");
        }
        
        $u->change_password = false;
        $u->save();

        return $this->res->withSuccess("Password changed successfully");

    }



   /**
   * @OA\Post(
   *     path="/api/v1/auth/pre_forgot",
   *     summary="forgot password",
   *     description="",
   *     tags={"Password"},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\MediaType(
   *             mediaType="application/json",
   *             @OA\Schema(
   *                @OA\Property(property="email", description="Email on account", type="string",),
   *             ),
   *         ),
   *     ),
   *     @OA\Response(response=200,description="successful operation",@OA\JsonContent()),
   *     @OA\Response(response=400,description="validation/server error",),
   *     @OA\Response(response=401,description="validation/server error",)
   * )
   */

    public function forgot(Request $request)
    {


        $validator = Validator::make($request->all(), [
        'email'  => 'required|email',
        ]);

        $account = User::where("email", $request->email)->first();

        $validator->after(function($validator)use($request, $account){
            
            if($account==null)
            {
                $validator->errors()->add("email", "Invalid email.");
            }
        });

        if($validator->fails())
        {
                if($validator->fails())
                {
                    return $this->res->withError($validator->errors()->toArray(), 400);
                }
        }

        //send email

        PasswordReset::where('email', $request->email)->delete();

        $password_reset = new PasswordReset;
        $password_reset->token = str_random(64);
        $password_reset->email = $request->email;
        $password_reset->created_at = \Carbon\Carbon::now();
        $password_reset->save();

        $password_reset->mobile = $request->has("mobile") ? true : false;

    
         Mail::to($request->get("email"))->queue(new SendPasswordLink($account, $password_reset));

         return $this->res->withSuccess("Password recovery link has been sent to your email");

    }

   /**
   * @OA\Put(
   *     path="/api/v1/auth/post_forgot",
   *     summary="forgot password change",
   *     description="",
   *     tags={"Password"},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\MediaType(
   *             mediaType="application/json",
   *             @OA\Schema(
   *                @OA\Property(property="email", description="Email on account", type="string",),
   *                @OA\Property(property="token", description="token from email", type="token",),
   *                @OA\Property(property="password", description="new password", type="token",),
   *                @OA\Property(property="password_confirmation", description="confirm new password", type="token",),
   *             ),
   *         ),
   *     ),
   *     @OA\Response(response=200,description="successful operation",@OA\JsonContent()),
   *     @OA\Response(response=400,description="validation/server error",),
   *     @OA\Response(response=401,description="validation/server error",)
   * )
   */

    public function forgotPost(Request $request)
    {

        $validator = Validator::make($request->all(), [
        'email'  => 'required|email',
        'token'  => 'required|string',
        'password'=>'required|min:6|confirmed',
        'password_confirmation'=>'sometimes|min:6|required_with:password',

        ]);

    
        $validator->after(function($validator)use($request){

            $password_reset = PasswordReset::where('email', $request->email)->where('token', $request->token)->first();

            if($password_reset==null)
            {
                $validator->errors()->add("token", "Invalid password reset token.");
            }
        });

        $account = User::where("email", $request->email)->first();

         $validator->after(function($validator)use($request, $account){

            if($account==null)
            {
                $validator->errors()->add("token", "Invalid user account");
            }
        });

        if($validator->fails())
        {
                if($validator->fails())
                {
                    return $this->res->withError($validator->errors()->toArray(), 400);
                }
        }

        $account->password = Hash::make($request->password);
        $account->save();

        return $this->res->withSuccess("You're password has been reset. You may now login");



    }



}
