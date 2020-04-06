<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use sngrl\PhpFirebaseCloudMessaging\Client;
use sngrl\PhpFirebaseCloudMessaging\Message;
use sngrl\PhpFirebaseCloudMessaging\Recipient\Topic;
use sngrl\PhpFirebaseCloudMessaging\Notification;

class SendPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public $topic;
    public $payload;
    public $action;
    public function __construct($topic, $action, $payload)
    {
        //
        $this->topic = $topic;
        $this->payload = $payload;
        $this->action = $action;
    }

    public static function sendToDriver($driverId, $action, $payload)
    {
        $hash = md5(self::getAppUrl());
        $topic = 'omsu-' . $hash . '-' . $driverId;

        self::dispatch($topic, $action, $payload);
    }

    private static function getAppUrl()
    {
        // https://stackoverflow.com/questions/4357668/how-do-i-remove-http-https-and-slash-from-user-input-in-php/14369057
        $url = config('app.url');
        $disallowed = array('http://', 'https://');
        foreach ($disallowed as $d) {
            if (strpos($url, $d) === 0) {
                return str_replace($d, '', $url);
            }
        }
        return $url;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
        $server_key = env('FIREBASE_SERVER_KEY');
        $client = new Client();
        $client->setApiKey($server_key);
        $client->injectGuzzleHttpClient(new \GuzzleHttp\Client());

        $message = new Message();
        $message->setPriority('high');
        $message->addRecipient(new Topic($this->topic));
        $message
            ->setData(['action' => $this->action, 'data' => $this->payload]);;

        $response = $client->send($message);
    }
}
