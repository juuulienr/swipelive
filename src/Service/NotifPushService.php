<?php

namespace App\Service;

use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Session\Session;
use sngrl\PhpFirebaseCloudMessaging\Client;
use sngrl\PhpFirebaseCloudMessaging\Message;
use sngrl\PhpFirebaseCloudMessaging\Recipient\Device;
use sngrl\PhpFirebaseCloudMessaging\Recipient\Topic;
use sngrl\PhpFirebaseCloudMessaging\Notification;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;



class NotifPushService {

  private $manager;
  private $params;

  public function __construct(ObjectManager $manager, ParameterBagInterface $params) {
    $this->manager = $manager;
    $this->params = $params;
  }

  public function send($title, $body, $type, $device = null) {
    // if ($type == "presta") {
    //   $server_key = 'AAAAd11X9bc:APA91bHZjyGXAbeWAHQFIXXtuD6Pk9J-Wh4JpyQ5yGGxcjtPF5J-SjnUaBxGuuIqZpAi86ZcB9alF4ov4DtoV_qdCRupZdC7ddbXOe_OeGaolbZGIFQRGh54_UKZr4TYcssCKpDZaLN1';
    // } else {
    //   $server_key = 'AAAAYwvTJo0:APA91bHjq2DdbKev4ks_xPZQGnJACLM8hUoSsxElB72mn-NdbtH2FLjZ0Jqd8NoPMcXnQlGakPoyVAg8e-1EPfxqt-53WC0FUXZkFXhbT8GjFXI8SGLQV7Tyq2qB6vPLVZ2i7mQJFb4s';
    // }

    $client = new Client();
    $client->setApiKey($server_key);
    $client->injectGuzzleHttpClient(new \GuzzleHttp\Client());

    $message = new Message();
    $notifPush = new Notification();
    $notifPush->setTitle($title);
    $notifPush->setBody($body);
    $notifPush->setBadge(1);
    $notifPush->setSound(true);
    $message->setPriority('high');

    if ($device) {
      $message->addRecipient(new Device($device));
    } else {
      $topic = "" . $type . "app";
      $message->addRecipient(new Topic($topic));
    }

    $message->setNotification($notifPush)
    ->setData(['key' => 'value']);

    $response = $client->send($message);
    $status = $response->getStatusCode();
    $content = $response->getBody()->getContents();

    return $status;
  }


  public function addTopicSubscription($topic, $device) {

    if ($device) {
      $client = new Client();
      $client->setApiKey($this->server_key);
      $client->injectGuzzleHttpClient(new \GuzzleHttp\Client());

      $response = $client->addTopicSubscription($topic, $device);
      $status = $response->getStatusCode();

      return $status;
    }

    return true;
  }


  public function removeTopicSubscription($topic, $device) {

    if ($device) {
      $client = new Client();
      $client->setApiKey($this->server_key);
      $client->injectGuzzleHttpClient(new \GuzzleHttp\Client());

      $response = $client->removeTopicSubscription($topic, $device);
      $status = $response->getStatusCode();

      return $status;
    }

    return true;
  }
}