<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Utils\WithJwtAuth;
use Response;

class AuthTest extends TestCase
{
    use RefreshDatabase;
    use WithJwtAuth;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testLogin()
    {
        $user = \factory(\App\User::class)->create();

        $res = $this->json('post', 'api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password'
        ]);
        //$res->dumpHeaders();
        //$res->dump();

        $res->assertStatus(200);
    }

    public function testAuthMe()
    {
        $user = \factory(\App\User::class)->create();
        // $res = $this->json('post', 'api/v1/auth/login', [
        //     'email' => $user->email,
        //     'password' => 'password'
        // ]);

        // $res->assertStatus(200);

        // $json = $res->decodeResponseJson();
        // //dd($json->success->data);
        // $token = $json['success']['data']['token'];

        // $res = $this->withHeaders([
        //     'Authentication' => "Bearer " . $token
        // ])->json('get', 'api/v1/user/me');
        // //$res->dump();

        // $res->assertStatus(200);

        $res = $this->actingAs($user)->get('api/v1/user/me');
        $res->assertStatus(200);
    }
}
