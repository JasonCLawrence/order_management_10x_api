<?php

namespace App\Http\Middleware;

use Closure;
use App\Http\ApiResponse;

class HasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $role)
    {

        //check if multiple roles were passed
        if (strpos($role, '|') !== false) {
            $role = array_map("trim", explode("|", $role));
        }

        if(!auth()->user()->hasRole($role))
        {
            $response = new ApiResponse;

            return  $response->withError("User cannot perform this action");
        }
        return $next($request);
    }
}
