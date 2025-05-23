<?php

declare(strict_types=1);

namespace App\Service;

use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\ApnsConfig;
use Kreait\Firebase\Messaging\CloudMessage;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class FirebaseMessagingService
{
  public function __construct(private readonly Messaging $messaging, ParameterBagInterface $params)
  {
    $firebaseCredentialsPath = $params->get('firebase_credentials_path');
    (new Factory())
    ->withServiceAccount($firebaseCredentialsPath);
  }

  /**
   * Envoie une notification push à un appareil spécifique.
   *
   * @param string $token Le token de l'appareil cible
   * @param string $title Le titre de la notification
   * @param string $body Le corps de la notification
   * @param array $data (optionnel) Des données supplémentaires à envoyer
   */
  public function sendNotification(string $title, string $body, string $token, array $data = [], int $attempt = 1): ?string
  {
    try {
      if (isset($data['type']) && 'vente' === $data['type']) {
        $apnsConfig    = ApnsConfig::new()->withSound('sales.wav');
        $androidConfig = AndroidConfig::new()->withSound('sales.wav');
      } else {
        $apnsConfig    = ApnsConfig::new()->withDefaultSound();
        $androidConfig = AndroidConfig::new()->withDefaultSound();
      }

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
        \sleep(2);

        return $this->sendNotification($title, $body, $token, $data, $attempt + 1);
      }

      return 'Échec de l\'envoi après plusieurs tentatives : ' . $e->getMessage();
    } catch (FirebaseException $e) {
      return 'Erreur Firebase: ' . $e->getMessage();
    }
  }
}
