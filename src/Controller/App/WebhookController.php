<?php

namespace App\Controller\App;

use App\Entity\Clip;
use App\Entity\Live;
use App\Entity\User;
use App\Entity\Vendor;
use App\Entity\Message;
use App\Entity\Product;
use App\Entity\Category;
use App\Repository\ClipRepository;
use App\Repository\OrderRepository;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\LiveRepository;
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


class WebhookController extends Controller {

  /**
   * Webhooks Bambuser
   *
   * @Route("/api/bambuser/webhooks", name="api_bambuser_webhooks", methods={"POST"})
   */
  public function bambuser(Request $request, ClipRepository $clipRepo, LiveRepository $liveRepo, ObjectManager $manager, LiveProductsRepository $liveProductRepo) {
    $result = json_decode($request->getContent(), true);

    // broadcast
    if ($result["collection"] == "broadcast") {

      // broadcast add
      if ($result["action"] == "add") {
        $broadcastId = $result["payload"]["id"];
        $clip = $clipRepo->findOneByBroadcastId($broadcastId);

        if ($clip) {
          $clip->setResourceUri($result["payload"]["resourceUri"]);
          $clip->setEventId($result["eventId"]);

          if ($result["payload"]["preview"]) {
            $clip->setPreview($result["payload"]["preview"]);
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

          if ($result["payload"]["preview"]) {
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

          if ($result["payload"]["status"]) {
            $clip->setStatus($result["payload"]["status"]);
          }
          
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

        case 'account.external_account.updated':
          // $externalAccount = $result["data"]["object"];
          break;

        case 'balance.available':
          // $balance = $result["data"]["object"];
          break;

        case 'payout.failed':
          // $payout = $result["data"]["object"];
          break;

        case 'person.updated':
          // $person = $result["data"]["object"];
          break;
          
        default:
          $this->get('bugsnag')->notifyException(new Exception($result["type"]));
          break;
      }
    }

    return $this->json(true, 200);
  }


  /**
   * Sendcloud Weebhook
   *
   * @Route("/api/sendcloud/webhooks", name="api_sendcloud_webhooks")", methods={"POST"})
   */
  public function sendcloud(Request $request, ObjectManager $manager, OrderRepository $orderRepo) {
    $result = json_decode($request->getContent(), true);
    $this->get('bugsnag')->notifyException(new Exception("Sendcloud"));

    // update parcel status
    if ($result["action"] == "parcel_status_changed") {
      $this->get('bugsnag')->notifyException(new Exception($result["parcel"]["id"]));
      $parcelId = $result["parcel"]["id"];
      $tracking_number = $result["parcel"]["tracking_number"];
      $statusId = $result["parcel"]["status"]["id"];
      $statusMessage = $result["parcel"]["status"]["message"];
    }

    return $this->json(true, 200);
  }
}