<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\OrderAttachment;
use Storage;

class UploadBase64Image implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public $order;
    public $base64;
    public $long;
    public $lat;

    public function __construct($order,$base64, $long, $lat)
    {
        //
        $this->order = $order;
        $this->base64 = $base64;
        $this->long = $long;
        $this->lat = $lat;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
        $image = base64_decode(str_replace(" ", '+',$this->base64));

        $extension = '.jpg';
        $filename = $this->order->id.'_'.time();

        $p = Storage::disk('s3')->put($filename.$extension, $image, 'public');

        $ret =  ((bool)$p ? $filename.$extension: false);

        if($ret!==false)
        {
            $orderAttachent  = new OrderAttachment;
            $orderAttachent->url = $ret;
            $orderAttachent->order_id = $this->order->id;
            $orderAttachent->long = $this->long;
            $orderAttachent->lat = $this->lat;
            $orderAttachent->save();
        }

    }
}
