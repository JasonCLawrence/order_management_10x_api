<?php

namespace App\Http\Middleware;

use App\Http\ApiResponse;

use Closure;
use Redis;

class HashRequest
{

    const TWO_DAYS = 3600 * 48;
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($request->header('hash')) {
            $hash = $request->header('hash');

            // check redis for hash
            if (Redis::get("hashedrequests:$hash")) {
                $response = new ApiResponse;
                return  $response->withSuccess("Request already sent");
            }

            Redis::set("hashedrequests:$hash",$hash,'EX', self::TWO_DAYS);
        }

        return $next($request);
    }
}
