<?php

namespace App\Http\Controllers\Api\V1\Company;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\ApiResponse;
use App\Company;
use DB;
use Validator;
use Illuminate\Support\Facades\Hash;
use Mail;

class CompanyController extends Controller
{
  //

  public function __construct()
  {
    //$this->middleware('user.current_company');

    $this->res = new ApiResponse();
  }

  /**
   * @OA\Get(
   *     path="/api/v1/company",
   *     summary="Get company details",
   *     description="company show endpoint oms",
   *     tags={"Company"},
   *     security={ {"bearer": {}} },
   *     @OA\Response(response=200,description="successful operation",@OA\JsonContent()),
   *     @OA\Response(response=400,description="validation/server error",),
   *     @OA\Response(response=401,description="validation/server error",)
   * )
   */

  public function show()
  {
    $company = Company::first();

    return $this->res->withSuccessData(
      [
        
              'name' => @$company->name,
              'email' => @$company->email,
              'street' => @$company->street,
              'city' => @$company->city,
              'country' => @$company->country,
              'postal' => @$company->postal,
              'logo' => @$company->logo,
             
      ]);

  }



    /**
     * @OA\Put(
     *     path="/api/v1/company",
     *     summary="update company",
     *     description="update ",
     *     tags={"Company"},
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
     *                   property="street",
     *                   description="street.",
     *                   type="string",
     *                ),
     *                @OA\Property(
     *                   property="city",
     *                   description="city.",
     *                   type="string",
     *                ),
     *                @OA\Property(
     *                   property="country",
     *                   description="country.",
     *                   type="string",
     *                ),
     *                @OA\Property(
     *                   property="postal",
     *                   description="postal.",
     *                   type="string",
     *                ),
     *                @OA\Property(
     *                   property="email",
     *                   description="email.",
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

  public function update(Request $request)
  {
      $data = request(['name', 'street', 'city', 'country', 'country', 'postal', 'email']);

        $validator = Validator::make($data, [
            'name' => 'string|required',
            'street'  => 'string|nullable',
            'city'  => 'string|nullable',
            'country'  => 'string|nullable',
            'postal'  => 'string|nullable',
            'email'  => 'string|nullable',
        ]);

        if($validator->fails())
        {
            return $this->res->withError($validator->errors()->toArray(), 400);
        }

        $company = Company::first();

        if($company==null)
        {
          $company = new Company;
        }

        $company->name = $request->get("name");
        $company->street = $request->get("street");
        $company->city = $request->get("city");
        $company->country = $request->get("country");
        $company->email = $request->get("email");
        $company->logo = null;
        $company->save();

        return $this->res->withSuccessData($company);


  }


  

   
}
