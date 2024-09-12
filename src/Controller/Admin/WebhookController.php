<?php

namespace App\Controller\Admin;

use App\Entity\Clip;
use App\Entity\Live;
use App\Entity\User;
use App\Entity\Vendor;
use App\Entity\Message;
use App\Entity\Product;
use App\Entity\Category;
use App\Entity\OrderStatus;
use App\Repository\ClipRepository;
use App\Repository\CommentRepository;
use App\Repository\OrderRepository;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\LiveRepository;
use App\Repository\OrderStatusRepository;
use App\Repository\LiveProductsRepository;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use App\Service\NotifPushService;
use Psr\Log\LoggerInterface;


class WebhookController extends AbstractController {

  private $notifPushService;
  private $bugsnag;
  private $logger;

  public function __construct(NotifPushService $notifPushService, \Bugsnag\Client $bugsnag, LoggerInterface $logger) {
    $this->notifPushService = $notifPushService;
    $this->bugsnag = $bugsnag;
    $this->logger = $logger;
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
              $this->notifPushService->send("SWIPE LIVE", "CLING 💰! Nouvelle commande pour un montant de " . str_replace('.', ',', $pending) . "€", $vendor->getUser()->getPushToken());
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
   * Webhooks Agora
   *
   * @Route("/api/agora/webhooks", name="api_agora_webhooks", methods={"POST"})
   */
  public function agora(Request $request, ObjectManager $manager, OrderRepository $orderRepo)
  {
    try {
          // Décoder le contenu JSON envoyé par Agora
      $result = json_decode($request->getContent(), true);

          // Laisser un breadcrumb dans Bugsnag pour le suivi du webhook
      $this->bugsnag->leaveBreadcrumb("Agora Webhook Received", "request", [
        'headers' => $request->headers->all(),
        'body' => $result
      ]);

          // Enregistrer les informations dans les logs (vous pouvez changer la stratégie de log)
      $this->logger->info('Agora Webhook Received', [
        'headers' => $request->headers->all(),
        'body' => $result
      ]);

              // Bug intentionnel : exception générée même si tout est correct
      throw new \Exception("Simulated bug: this is a test exception for Bugsnag");

          // Analyser le contenu du webhook et logguer l'eventType
      if (isset($result['eventType'])) {
        $this->bugsnag->leaveBreadcrumb("Agora Event Type Detected", "info", [
          'eventType' => $result['eventType'],
          'eventData' => $result
        ]);

              // Enregistrer l'eventType dans les logs pour voir ce qui est reçu
        $this->logger->info('Agora Event Type', [
          'eventType' => $result['eventType'],
          'eventData' => $result
        ]);
      } else {
              // Si eventType est manquant, on l'enregistre dans les logs et on laisse un breadcrumb
        $this->bugsnag->leaveBreadcrumb("Missing eventType in Agora Webhook", "error");
        $this->logger->warning('Missing eventType in Agora Webhook', [
          'body' => $result
        ]);
      }


      return $this->json(true, 200);
    } catch (\Exception $e) {
          // Capturer les exceptions et notifier Bugsnag
      $this->bugsnag->notifyException($e);

      return $this->json([
        'status' => 'error',
        'message' => 'Webhook processing failed: ' . $e->getMessage()
      ], 500);
    }
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


