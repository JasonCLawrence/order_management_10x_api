<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use App\Http\ApiResponse;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        // if ($exception instanceof MethodNotAllowedHttpException) {
        //     return $res->withError("TOKEN_EXPIRED", 405);
        // }

        if (app()->bound('sentry') && $this->shouldReport($exception) && env('APP_ENV') != 'local') {
            app('sentry')->captureException($exception);
        }

        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {

        $res = new ApiResponse();
        if ($exception instanceof UnauthorizedHttpException) {
            $preException = $exception->getPrevious();
            if ($preException instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException) {

                return $res->withError("TOKEN_EXPIRED", 401);
            } elseif ($preException instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException) {
                return $res->withError("TOKEN_INVALID", 500);
            } else if ($preException instanceof \Tymon\JWTAuth\Exceptions\TokenBlacklistedException) {
                return $res->withError("TOKEN_BLACKLISTED", 500);
            }
            if ($exception->getMessage() === 'Token not provided') {
                return $res->withError("TOKEN_EXPIRED", 402);
            }
        }


        //outside of Unauth

        if ($exception instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException) {

            return $res->withError("TOKEN_EXPIRED", 401);
        } elseif ($exception instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException) {
            return $res->withError("TOKEN_INVALID", 500);
        } else if ($exception instanceof \Tymon\JWTAuth\Exceptions\TokenBlacklistedException) {
            return $res->withError("TOKEN_BLACKLISTED", 500);
        }

        if ($exception instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException) {
            return $res->withError("METHOD NOT ALLOWED", 405);
        }

        return parent::render($request, $exception);
    }

    protected function unauthenticated($request, \Illuminate\Auth\AuthenticationException  $exception)
    {
        $res = new ApiResponse();
        return $res->withError("Unauthenticated", 401);
    }
}
