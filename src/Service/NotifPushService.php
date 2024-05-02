<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use sngrl\PhpFirebaseCloudMessaging\Recipient\Device;
use sngrl\PhpFirebaseCloudMessaging\Recipient\Topic;
use sngrl\PhpFirebaseCloudMessaging\Notification;
use sngrl\PhpFirebaseCloudMessaging\Message;
use sngrl\PhpFirebaseCloudMessaging\Client;
use Doctrine\Persistence\ObjectManager;


class NotifPushService {
  private $manager;
  private $params;

  public function __construct(ObjectManager $manager, ParameterBagInterface $params) {
    $this->manager = $manager;
    $this->params = $params;
  }

  public function send($title, $body, $token) {
    $client = new Client();
    $client->setApiKey('AAAA6Ak76C0:APA91bH9aWTsN6yRcF7-gV7O-siNKXb1NK08EVZK_ePYeh60TvMtuJS7yr8lAZ3RLqrBY4QhgpEqS7OJivxKrRQ3cUGUtc7edxTtG5IpJPdl8ofVwN7kOl7mv__ytPZ3NBVyAI2R00UW');
    $client->injectGuzzleHttpClient(new \GuzzleHttp\Client());

    $notif = new Notification();
    $notif->setTitle($title);
    $notif->setBody($body);
    $notif->setSound(true);
    $notif->setBadge(1);

    $message = new Message();
    $message->setPriority('high');
    $message->addRecipient(new Device($token));
    $message->setNotification($notif);
    $message->setData(['key' => 'value']);

    $response = $client->send($message);
    $status = $response->getStatusCode();
    $content = $response->getBody()->getContents();

    return $status;
  }
}