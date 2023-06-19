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
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use App\Service\NotifPushService;


class WebhookController extends Controller {

  private $notifPushService;

  public function __construct(NotifPushService $notifPushService) {
      $this->notifPushService = $notifPushService;
  }


  /**
   * Webhooks Bambuser
   *
   * @Route("/api/bambuser/webhooks", name="api_bambuser_webhooks", methods={"POST"})
   */
  public function bambuser(Request $request, ClipRepository $clipRepo, LiveRepository $liveRepo, ObjectManager $manager) {
    $result = json_decode($request->getContent(), true);
    $this->get('bugsnag')->notifyError('ErrorType', 'Webhooks Bambuser');

    // broadcast
    if ($result["collection"] == "broadcast") {

      // broadcast add
      if ($result["action"] == "add") {
        $broadcastId = $result["payload"]["id"];
        $clip = $clipRepo->findOneByBroadcastId($broadcastId);

        if ($clip) {
          $clip->setResourceUri($result["payload"]["resourceUri"]);
          $clip->setEventId($result["eventId"]);
          $clip->setTotalLikes($clip->getLive()->getTotalLikes());

          if (array_key_exists("preview", $result["payload"])) {
            $clip->setPreview($result["payload"]["preview"]);
          } else {
            $clip->setPreview($clip->getLive()->getPreview());
          }

          $manager->flush();
        }
      }

      // broadcast update
      if ($result["action"] == "update") {
        $broadcastId = $result["payload"]["id"];
        $live = $liveRepo->findOneByBroadcastId($broadcastId);

        if ($live) {
          $live->setEventId($result["eventId"]);

          if ($result["payload"]["type"] == "archived") {
            $live->setStatus(2);
          }

          $manager->flush();
        }

        $clip = $clipRepo->findOneByBroadcastId($broadcastId);

        if ($clip) {
          $clip->setEventId($result["eventId"]);
          $clip->setTotalLikes($clip->getLive()->getTotalLikes());

          if (array_key_exists("preview", $result["payload"])) {
            $clip->setPreview($result["payload"]["preview"]);
          }

          $manager->flush();
        }
      }

      // broadcast extract
      if ($result["action"] == "extract") {
        $broadcastId = $result["payload"]["id"];
        $clip = $clipRepo->findOneByBroadcastId($broadcastId);

        if ($clip) {
          $clip->setEventId($result["eventId"]);
          $clip->setTotalLikes($clip->getLive()->getTotalLikes());
          $manager->flush();
        }
      }

      // broadcast remove
      if ($result["action"] == "remove") {
        $broadcastId = $result["payload"]["id"];
        $clip = $clipRepo->findOneByBroadcastId($broadcastId);

        if ($clip) {
          $comments = $clip->getComments();

          if ($comments) {
            foreach ($comments as $comment) {
              $manager->remove($comment);
            }
          }

          $manager->remove($clip);
          $manager->flush();
        }

        $live = $liveRepo->findOneByBroadcastId($broadcastId);

        if ($live) {
          $liveProducts = $live->getLiveProducts();
          $comments = $live->getComments();

          if ($liveProducts) {
            foreach ($liveProducts as $liveProduct) {
              $manager->remove($liveProduct);
            }
          }

          if ($comments) {
            foreach ($comments as $comment) {
              $manager->remove($comment);
            }
          }

          $manager->remove($live);
          $manager->flush();
        }
      }
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
    $this->get('bugsnag')->notifyError('ErrorType', 'Webhooks Stripe');

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
                $this->get('bugsnag')->notifyError('ErrorType', $error);
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
    $this->get('bugsnag')->notifyError('ErrorType', 'Webhooks Stripe Connect');

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
    $this->get('bugsnag')->notifyError('ErrorType', 'Webhooks Upelgo');
    
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


