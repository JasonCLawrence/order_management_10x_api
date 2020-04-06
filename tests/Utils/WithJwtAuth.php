<?php

namespace Tests\Utils;

use Illuminate\Contracts\Auth\Authenticatable;
use \Tymon\JWTAuth\Facades\JWTAuth;

trait WithJwtAuth
{
    function actingAs(Authenticatable $user, $driver = null)
    {
        $token = JWTAuth::fromUser($user);
        $this->withHeader('Authorization', 'Bearer ' . $token);
        return $this;
    }
}
