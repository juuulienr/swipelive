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
  public function sendNotification(string $title, string $body, string $token, array $data = []): ?string
  {
    $data['route'] = "ListMessages";

    $message = CloudMessage::withTarget('token', $token)
    ->withNotification([
      'title' => $title,
      'body'  => $body,
    ])
    ->withData($data)
    ->withApnsConfig(
      ApnsConfig::new()
      ->withSound('notif.aiff')
      ->withBadge(1)
    );
    // ->withAndroidConfig(
    //   AndroidConfig::fromArray([
    //     'notification' => [
    //       'sound' => 'default'
    //     ]
    //   ])
    // );

    try {
      // Envoyer le message via Firebase Messaging
      $this->messaging->send($message);
      return 'Notification envoyée avec succès';
    } catch (MessagingException $e) {
      // Gestion des erreurs liées au messaging
      return 'Erreur Messaging: ' . $e->getMessage();
    } catch (FirebaseException $e) {
      // Gestion des autres erreurs Firebase
      return 'Erreur Firebase: ' . $e->getMessage();
    }
  }
}
