<?php

namespace Tests\Feature;

use Response;
use Tests\TestCase;
use Tests\Utils\WithJwtAuth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

// 1) create order
// 2) release order from warehouse
// 3) add tasks
// 4) add pictures
// 5) change items
// 6) client signs order

class OrderFlowTest extends TestCase
{
    use RefreshDatabase;
    use WithJwtAuth;

    public function testFullFlow()
    {
        Storage::fake("s3");

        $admin = \factory(\App\User::class)->states('admin')->create();
        $driver = \factory(\App\User::class)->states('driver')->create();
        $customer = \factory(\App\Customer::class)->create();
        $warehouse = \factory(\App\Warehouse::class)->create();

        $res = $this->actingAs($admin)->post('api/v1/order', [
            'customer_id' => $customer->id,
            'driver_id' => $driver->id,
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
                    'driver_id' => $driver->id,
                    'warehouse_id' => $warehouse->id,
                    'description' => 'test description',
                ]
            ]
        ]);

        // fetch order from list
        // $res = $this->actingAs($admin)->get('api/v1/order');
        // $res->assertStatus(200);

        $json = $res->decodeResponseJson();
        $order = (object) $json['success']['data'];
        $orderId = $order->id;
        $this->dump($orderId);

        // RELEASE FROM WAREHOUSE

        // http://png-pixel.com/
        $res = $this->actingAs($driver)->post("api/v1/order/$orderId/warehouse_signature", [
            "warehouse_signee" => "John Doe",
            "warehouse_signature_data" => "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==",
            "datetime" => "2020-02-02 08:00:00",
            "lat" => "18.1096",
            "lon" => "77.2975"
        ]);

        $res->assertStatus(200);

        // ADD INVOICE ITEM

        // ADD NOTE
        $res = $this->actingAs($driver)->post("api/v1/order/$orderId/note", [
            "content" => "Hello this is a test note",
        ]);
        $res->assertStatus(200);

        // ADD IMAGE
        $res = $this->actingAs($driver)->post("api/v1/order/$orderId/attachment", [
            "data" => "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==",
            "lat" => "18.1096",
            "long" => "77.2975",
        ]);
        $res->assertStatus(200);

        // COMPLETE BY DRIVER
        $res = $this->actingAs($driver)->post("api/v1/order/$orderId/complete", [
            "signee" => "John Doe",
            "signature_data" => "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==",
            "datetime" => "2020-02-02 09:15:00",
            "lat" => "18.1096",
            "long" => "77.2975",
        ]);
        $res->assertStatus(200);

        // INCOMPLETE BY ADMIN
        $res = $this->actingAs($admin)->post("api/v1/order/$orderId/incomplete");
        $res->assertStatus(200);

        // COMPLETE BY ADMIN
        $res = $this->actingAs($admin)->post("api/v1/order/$orderId/complete");
        $res->assertStatus(200);

        // ENSURE ORDER IS COMPLETE BY ADMIN
        $res = $this->actingAs($admin)->get("api/v1/order/$orderId");
        $res->assertStatus(200);

        $order = (object) $res->decodeResponseJson()['success']['data'];
        // $this->dump($order);
        // $this->dump($order->id == $orderId);
        $this->assertTrue($order->id == $orderId, "Should fetch correct order");
        $this->assertTrue($order->completed_by == $admin->id, "Should fetch correct order");
    }
}
