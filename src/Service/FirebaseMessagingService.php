<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Messaging\ApnsConfig;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging;
use Kreait\Firebase\Factory;

class FirebaseMessagingService
{
  private Messaging $messaging;
  private $params;

  public function __construct(ParameterBagInterface $params)
  {
    $firebaseCredentialsPath = $params->get('firebase_credentials_path');
    $factory = (new Factory)
    ->withServiceAccount($firebaseCredentialsPath);

    $this->messaging = $factory->createMessaging();
    $this->params = $params;
  }

  /**
   * Envoie une notification push à un appareil spécifique.
   *
   * @param string $token Le token de l'appareil cible
   * @param string $title Le titre de la notification
   * @param string $body Le corps de la notification
   * @param array $data (optionnel) Des données supplémentaires à envoyer
   * @return string|null
   */
  public function sendNotification(string $title, string $body, string $token, array $data = [], int $attempt = 1): ?string
  {
    if (isset($data['type']) && $data['type'] === 'vente') {
      $apnsConfig = ApnsConfig::new()->withSound('notif.wav')->withBadge(1);
      $androidConfig = AndroidConfig::fromArray([
        'notification' => [
          'sound' => 'notif'
        ]
      ]);
    } else {
      $apnsConfig = ApnsConfig::new()->withSound('default')->withBadge(1);
      $androidConfig = AndroidConfig::fromArray([
        'notification' => [
          'sound' => 'default'
        ]
      ]);
    }

    try {
      $message = CloudMessage::withTarget('token', $token)
      ->withNotification([
        'title' => $title,
        'body'  => $body,
      ])
      ->withData($data)
      ->withApnsConfig($apnsConfig)
      ->withAndroidConfig($androidConfig);

      $test = $this->messaging->send($message);
      return 'Notification envoyée avec succès';
    } catch (MessagingException $e) {
      if ($attempt < 3) {
        sleep(2);
        return $this->sendNotification($title, $body, $token, $data, $attempt + 1);
      } else {
        return 'Échec de l\'envoi après plusieurs tentatives : ' . $e->getMessage();
      }
    } catch (FirebaseException $e) {
      return 'Erreur Firebase: ' . $e->getMessage();
    }
  }
}

