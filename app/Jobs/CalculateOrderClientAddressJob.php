<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use maxh\Nominatim\Nominatim;

/*
Calculates client's geolocation fro lat-lon
*/

class CalculateOrderClientAddressJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    private $orderId;
    private $lat;
    private $lon;

    public function __construct($orderId, $lat, $lon)
    {
        $this->orderId = $orderId;
        $this->lat = $lat;
        $this->lon = $lon;
    }

    public function handle()
    {
        $order = Order::find($this->orderId);
        if (!$order)
            return;

        $url = "http://nominatim.openstreetmap.org/";
        $nominatim = new Nominatim($url);

        try {
            $reverse = $nominatim->newReverse()
                ->latlon($this->lat, $this->lon);

            $result = $nominatim->find($reverse);

            $order->signature_address = $result['display_name'];
        } catch (\Exception $e) {
            throw new \Exception("Unable to find coordinates: $this->lan $this->lon");
        }

        $order->save();
    }
}
