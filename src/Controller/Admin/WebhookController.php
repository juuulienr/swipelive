<?php

namespace App\Controller\Admin;

use App\Entity\Clip;
use App\Entity\Live;
use App\Entity\User;
use App\Entity\Vendor;
use App\Entity\Message;
use App\Entity\Product;
use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\Follow;
use App\Entity\Order;
use App\Entity\Upload;
use App\Entity\OrderStatus;
use App\Entity\LiveProducts;
use App\Repository\ClipRepository;
use App\Repository\CommentRepository;
use App\Repository\OrderRepository;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\LiveRepository;
use App\Repository\FollowRepository;
use App\Repository\VendorRepository;
use App\Repository\UserRepository;
use App\Repository\OrderStatusRepository;
use App\Repository\LiveProductsRepository;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Filesystem\Filesystem;
use App\Service\FirebaseMessagingService;
use App\Service\VideoProcessor;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Client;


class WebhookController extends AbstractController {

  private $firebaseMessagingService;
  private $bugsnag;
  private $videoProcessor;

  public function __construct(FirebaseMessagingService $firebaseMessagingService, \Bugsnag\Client $bugsnag, VideoProcessor $videoProcessor) {
    $this->firebaseMessagingService = $firebaseMessagingService;
    $this->bugsnag = $bugsnag;
    $this->videoProcessor = $videoProcessor;
  }



/**
 * Webhooks Agora RTC Channel Event
 *
 * @Route("/api/agora/rtc/channel/event/webhooks", name="api_agora_rtc_channel_event", methods={"POST"})
 */
public function agoraRTCChannelEvent(Request $request, ObjectManager $manager, LiveRepository $liveRepo, UserRepository $userRepo)
{
  try {
    $result = json_decode($request->getContent(), true);

    // test_webhook
    if (isset($result['payload']['channelName']) && $result['payload']['channelName'] == "test_webhook") {
      return $this->json(true, 200);
    }

    // check noticeId
    $noticeId = $result['noticeId'] ?? null;
    $live = $liveRepo->findOneByNoticeId($noticeId);

    if ($live) {
      return $this->json(true, 200);
    }

    if (isset($result['eventType'])) {
      switch ($result['eventType']) {
        case 103:
        // broadcaster join channel
        $cname = $result['payload']['channelName'];
        $live = $liveRepo->findOneByCname($cname);

        if ($live) {
          // Vérifier si un enregistrement est déjà en cours
          if ($live->getStatus() != 2 && !$live->getResourceId() && !$live->getSid()) {
            // Met à jour le statut à "en cours"
            $live->setStatus(1);
            $manager->flush();

            if ($live && $noticeId) {
              $live->setNoticeId($noticeId);
              $manager->flush();
            }

            $client = new Client();
            $vname = "vendor" . $live->getVendor()->getId();
            $appId = $this->getParameter('agora_app_id');

            // 1. Récupérer le token via votre propre route API
            $tokenUrl = $this->generateUrl('generate_agora_token_record', ['id' => $live->getId()], 0);
            $tokenResponse = $client->request('GET', $tokenUrl);
            $tokenData = json_decode($tokenResponse->getBody(), true);

            if (!isset($tokenData['token'])) {
              throw new \Exception('Impossible de récupérer le token Agora.');
            }

            $tokenAgora = $tokenData['token'];

            // 2. Requête pour acquérir un resourceId
            $urlAcquire = sprintf('https://api.agora.io/v1/apps/%s/cloud_recording/acquire', $appId);
            $headers = ['Content-Type' => 'application/json'];
            $bodyAcquire = json_encode([
              'cname' => $cname,
              'uid' => '123456789',
              'clientRequest' => new \stdClass()
            ]);

            $resAcquire = $client->request('POST', $urlAcquire, [
              'headers' => $headers,
              'auth' => [$this->getParameter('agora_customer_id'), $this->getParameter('agora_customer_secret')],
              'body' => $bodyAcquire
            ]);

            $acquireData = json_decode($resAcquire->getBody(), true);
            if (!isset($acquireData['resourceId'])) {
              throw new \Exception('resourceId manquant lors de l\'acquisition.');
            }

            // Récupérer le resourceId
            $resourceId = $acquireData['resourceId'];
            $live->setResourceId($resourceId);
            $manager->flush();

            // 3. Démarrer l'enregistrement en utilisant le tokenAgora
            $urlStart = sprintf('https://api.agora.io/v1/apps/%s/cloud_recording/resourceid/%s/mode/mix/start', $appId, $resourceId);
            $bodyStart = json_encode([
              'cname' => $cname,
              'uid' => '123456789',
              'clientRequest' => [
                'token' => $tokenAgora,
                'recordingConfig' => [
                  'maxIdleTime' => 120,
                  'streamTypes' => 2,
                  'channelType' => 0,
                  'videoStreamType' => 0,
                  'transcodingConfig' => [
                    'width' => 1080,
                    'height' => 1920,
                    'bitrate' => 3150,
                    'fps' => 30
                  ]
                ],
                'snapshotConfig' => [
                  'captureInterval' => 3600,
                  'fileType' => [
                    'jpg'
                  ]
                ],
                'recordingFileConfig' => [
                  'avFileType' => [
                    'hls'
                  ]
                ],
                'storageConfig' => [
                  'vendor' => $this->getParameter('s3_vendor'),
                  'region' => $this->getParameter('s3_region'),
                  'bucket' => $this->getParameter('s3_bucket'),
                  'accessKey' => $this->getParameter('s3_access_key'),
                  'secretKey' => $this->getParameter('s3_secret_key'),
                  'fileNamePrefix' => [$vname, $cname]
                ]
              ]
            ]);

            $resStart = $client->request('POST', $urlStart, [
              'headers' => $headers,
              'auth' => [$this->getParameter('agora_customer_id'), $this->getParameter('agora_customer_secret')],
              'body' => $bodyStart
            ]);

            $responseStart = json_decode($resStart->getBody(), true);

            if (isset($responseStart['sid'])) {
              $sid = $responseStart['sid'];
              $live->setSid($sid);
              $manager->flush();
            } else {
              throw new \Exception('Impossible de démarrer l\'enregistrement.');
            }

            // Envoi de notifications push
            $followers = $userRepo->findUserFollowers($live->getVendor()->getUser());
            if ($followers) {
              foreach ($followers as $follower) {
                if ($follower->getPushToken()) {
                  try {
                    $this->firebaseMessagingService->sendNotification(
                      "SWIPE LIVE",
                      "🔴 " . $live->getVendor()->getPseudo() . " est actuellement en direct",
                      $follower->getPushToken()
                    );
                  } catch (\Exception $error) {
                    $this->bugsnag->notifyException($error);
                  }
                }
              }
            }
          } else {
            // Ignorer si l'enregistrement est déjà en cours
            return $this->json(['message' => 'Enregistrement déjà lancé.'], 200);
          }
        }
        break;

        case 104:
        // broadcaster leave channel
        $cname = $result['payload']['channelName'];
        $reason = $result['payload']['reason'];
        $live = $liveRepo->findOneByCname($cname);

        if ($live) {
          $live->setStatus(2);
          $live->setReason($reason);
          $manager->flush();
        }

        break;
      }
    }
  } catch (\Exception $e) {
    $this->bugsnag->notifyException($e);
  }

  return $this->json(true, 200);
}




  /**
   * Webhooks Agora Cloud Recording
   *
   * @Route("/api/agora/cloud/recording/webhooks", name="api_agora_webhooks", methods={"POST"})
   */
  public function agoraCloudRecording(Request $request, ObjectManager $manager, LiveRepository $liveRepo)
  {
    try {
      $result = json_decode($request->getContent(), true);

      if (isset($result['eventType'])) {
        switch ($result['eventType']) {
          case 1:
            throw new \Exception('An error occurs during the recording (cloud recording service)');
          break;

          case 2:
            throw new \Exception('A warning occurs during the recording (cloud recording service)');
          break;

          case 31:
             // All the recorded files are uploaded to the specified third-party cloud storage
            $fileList = $result['payload']['details']['fileList'] ?? [];
            $cname = $result['payload']['cname'];
            $live = $liveRepo->findOneByCname($cname);

            if ($live && !empty($fileList)) {
                $live->setFileList($fileList[0]["fileName"]);
                $manager->flush();

                // Process clips after receiving fileList
                $clips = $live->getClips();
                foreach ($clips as $clip) {
                    $this->videoProcessor->processClip($clip);
                }
            }
          break;

          case 45:
            // The screenshot is captured successfully
            $fileName = $result['payload']['details']['fileName'] ?? [];
            $cname = $result['payload']['cname'];
            $live = $liveRepo->findOneByCname($cname);

            if ($live && $fileName) {
              $live->setPreview($fileName);
              $manager->flush();
            }
          break;
        }
      }
    } catch (\Exception $e) {
      $this->bugsnag->notifyException($e);
    }

    return $this->json(true, 200);
  }



  /**
   * Webhooks Stripe
   *
   * @Route("/api/stripe/webhooks", name="api_stripe_webhooks", methods={"POST"})
   */
  public function stripe(Request $request, ObjectManager $manager, OrderRepository $orderRepo, LiveRepository $liveRepo) {
    $result = json_decode($request->getContent(), true);

    // payment_intent
    if ($result["object"] == "event" && $result["data"]["object"]["object"] == "payment_intent") {
      $order = $orderRepo->findOneByPaymentId($result["data"]["object"]["id"]);

      if ($order) {
        switch ($result["type"]) {
          case 'payment_intent.succeeded':
          $order->setStatus("succeeded");
          $vendor = $order->getVendor();
          $pending = $order->getTotal() - $order->getFees() - $order->getShippingPrice();
          $vendor->setPending($pending);

          foreach ($order->getLineItems() as $lineItem) {
            if ($lineItem->getVariant()) {
              $variant = $lineItem->getVariant();
              $variant->setQuantity($variant->getQuantity() - $lineItem->getQuantity());
            } else {
              $product = $lineItem->getProduct();
              $product->setQuantity($product->getQuantity() - $lineItem->getQuantity());
            }
          }

          $order->setEventId($result["id"]);
          $order->setUpdatedAt(new \DateTime('now', timezone_open('Europe/Paris')));
          $manager->flush();

          $live = $liveRepo->vendorIsLive($vendor);

          if (!$live && $vendor->getUser()->getPushToken()) {
            try {
              $data = [
                'route' => 'ListOrders',
                'type' => 'vente',
                'isOrder' => true,
                'orderId' => $order->getId()
              ];

              $this->firebaseMessagingService->sendNotification(
                "SWIPE LIVE", 
                "CLING 💰! Nouvelle commande pour un montant de " . str_replace('.', ',', $order->getTotal()) . "€", 
                $vendor->getUser()->getPushToken(),
                $data
              );
            } catch (\Exception $error) {
              $this->bugsnag->notifyException($error);
            }
          }

          break;

          default:
          break;
        }
      }
    }

    // balance available
    if ($result["object"] == "event" && $result["data"]["object"]["object"] == "balance") {
      if ($result["type"] == "balance.available") {
        $pending = $result["data"]["object"]["pending"][0]["amount"];
        $available = $result["data"]["object"]["available"][0]["amount"];
        $connect_reserved = $result["data"]["object"]["connect_reserved"][0]["amount"];
        $livemode = $result["data"]["object"]["livemode"];
      }
    }

    return $this->json(true, 200);
  }


  /**
   * Webhooks Stripe Connect
   *
   * @Route("/api/stripe/webhooks/connect", name="api_stripe_webhooks_connect", methods={"POST"})
   */
  public function stripeConnect(Request $request, ObjectManager $manager, OrderRepository $orderRepo) {
    $result = json_decode($request->getContent(), true);

    // account
    if ($result["object"] == "event") {
      switch ($result["type"]) {
        case 'account.updated':
          // $account = $result["data"]["object"];
        break;

        case 'person.updated':
          // $person = $result["data"]["object"];
        break;

        case 'payout.failed':
          // $payout = $result["data"]["object"];
        break;
        
        default:
        break;
      }
    }

    return $this->json(true, 200);
  }




  /**
   * Webhooks Upelgo
   *
   * @Route("/api/upelgo/webhooks", name="api_upelgo_webhooks", methods={"POST"})
   */
  public function upelgo(Request $request, ObjectManager $manager, OrderRepository $orderRepo, OrderStatusRepository $statusRepo) {
    $result = json_decode($request->getContent(), true);
    
    if ($result["action"]) {
      switch ($result["action"]) {
        case 'ship':
        break;

        case 'delivery':
        break;

        case 'track':
          // Livraison Expédition : Bonne nouvelle ! (Shop name) a envoyé ton colis.
          // Livraison En transit : Ton colis vient d’être pris en charge par le transporteur.
          // Livraison Disponible en Point Relai : Ton colis est disponible au point relai : ( Nom du PR).
          // Livraison Terminée : Colis livré ! Tu a 48h pour vérifier ta commande et cliquez sur «tout est correct» pour la clôturer.
          // Litiges : blocage des fond + Ouverture du chat directement ( amiable ) + problème résolu ? Oui Non
          // Si oui : clôturer le chat + déblocage des fonds soit pour le client soit pour le vendeur.
          // Si non : transfert du litige vers nous
          // Exemple de litige : Colis non reçu, colis non conforme, Contrefaçon….
        

          // if ($result->success) {
          //   $order->setDelivered($result->delivered);

          //   if ($result->incident_date != "") {
          //     $order->setIncidentDate(new \Datetime($result->incident_date));
          //   }

          //   if ($result->delivery_date != "") {
          //     $order->setDeliveryDate(new \Datetime($result->delivery_date));
          //   }

          //   // update orderStatus
          //   if ($result->events) {
          //     foreach ($result->events as $event) {
          //       $orderStatus = $statusRepo->findOneByShipping($order);

          //       if (!$orderStatus && $event->date) {
          //         $orderStatus = new OrderStatus();
          //         $orderStatus->setDate(new \Datetime($event->date_unformatted));
          //         $orderStatus->setDescription($event->description);
          //         $orderStatus->setCode($event->code);
          //         $orderStatus->setShipping($order);

          //         if ($event->location) {
          //           foreach ($event->location as $location) {
          //             $orderStatus->setPostcode($location->postcode);
          //             $orderStatus->setCity($location->city);
          //             $orderStatus->setLocation($location->location);
          //           }
          //         }

          //         $order->setUpdatedAt(new \Datetime($event->date_unformatted));
        
          //         $manager->persist($orderStatus);
          //         $manager->flush();
          //       }
          //     }
          //   }

          //   $manager->flush();
          // }
        break;

        case 'multirate':
        break;

        case 'cancel':
        break;
        
        default:
        break;
      }
    }

    return $this->json(true, 200);
  }
}


