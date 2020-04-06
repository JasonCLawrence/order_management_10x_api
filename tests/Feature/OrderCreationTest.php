<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Utils\WithJwtAuth;
use Response;

class OrderCreationTest extends TestCase
{
    use RefreshDatabase;
    use WithJwtAuth;

    public function testFullOrderCreation()
    {
        $user = \factory(\App\User::class)->create();
        $customer = \factory(\App\Customer::class)->create();
        $warehouse = \factory(\App\Warehouse::class)->create();

        // Invoice
        $res = $this->actingAs($user)->post('api/v1/order', [
            'customer_id' => $customer->id,
            'driver_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'description' => 'test description',
            'lat' => '100',
            'long' => '100',
            'schedule_at' => '2020-02-02 09:00:00',
            'signature' => 1,
            'type' => 'invoice'
        ]);

        $res->assertStatus(200);

        $res->assertJson([
            'success' => [
                'data' => [
                    'customer_id' => $customer->id,
                    'driver_id' => $user->id,
                    'warehouse_id' => $warehouse->id,
                    'description' => 'test description',
                    'type' => 'invoice'
                ]
            ]
        ]);
    }

    public function testCreateOrderWithOnlyCustomer()
    {
        $user = \factory(\App\User::class)->create();
        $customer = \factory(\App\Customer::class)->create();

        $res = $this->actingAs($user)->post('api/v1/order', [
            'customer_id' => $customer->id,
            'lat' => '100',
            'long' => '100',
            'schedule_at' => '2020-02-02 09:00:00',
            'signature' => 1,
            'type' => 'invoice'
        ]);

        $res->assertStatus(200);

        $res->assertJson([
            'success' => [
                'data' => [
                    'customer_id' => $customer->id
                ]
            ]
        ]);
    }

    // should throw a 400
    public function testCreateOrderWithoutCustomer()
    {
        $user = \factory(\App\User::class)->create();

        $res = $this->actingAs($user)->post('api/v1/order', [
            'customer_id' => null,
            'lat' => '100',
            'long' => '100',
            'schedule_at' => '2020-02-02 09:00:00',
            'signature' => 1,
            'type' => 'invoice'
        ]);

        $res->assertStatus(400);
    }

    // create order types
    public function testCreateOrderTypes()
    {
        $user = \factory(\App\User::class)->create();
        $customer = \factory(\App\Customer::class)->create();
        $warehouse = \factory(\App\Warehouse::class)->create();

        // Invoice
        $res = $this->actingAs($user)->post('api/v1/order', [
            'customer_id' => $customer->id,
            'driver_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'description' => 'test description',
            'lat' => '100',
            'long' => '100',
            'schedule_at' => '2020-02-02 09:00:00',
            'signature' => 1,
            'type' => 'invoice'
        ]);

        $res->assertStatus(200);

        $res->assertJson([
            'success' => [
                'data' => [
                    'customer_id' => $customer->id,
                    'driver_id' => $user->id,
                    'warehouse_id' => $warehouse->id,
                    'description' => 'test description',
                    'type' => 'invoice'
                ]
            ]
        ]);

        // Service
        $res = $this->actingAs($user)->post('api/v1/order', [
            'customer_id' => $customer->id,
            'driver_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'description' => 'test description',
            'lat' => '100',
            'long' => '100',
            'schedule_at' => '2020-02-02 09:00:00',
            'signature' => 1,
            'type' => 'service'
        ]);

        $res->assertStatus(200);

        $res->assertJson([
            'success' => [
                'data' => [
                    'customer_id' => $customer->id,
                    'driver_id' => $user->id,
                    'warehouse_id' => $warehouse->id,
                    'description' => 'test description',
                    'type' => 'service'
                ]
            ]
        ]);

        // Delivery
        $res = $this->actingAs($user)->post('api/v1/order', [
            'customer_id' => $customer->id,
            'driver_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'description' => 'test description',
            'lat' => '100',
            'long' => '100',
            'schedule_at' => '2020-02-02 09:00:00',
            'signature' => 1,
            'type' => 'delivery'
        ]);

        $res->assertStatus(200);

        $res->assertJson([
            'success' => [
                'data' => [
                    'customer_id' => $customer->id,
                    'driver_id' => $user->id,
                    'warehouse_id' => $warehouse->id,
                    'description' => 'test description',
                    'type' => 'delivery'
                ]
            ]
        ]);
    }
}
