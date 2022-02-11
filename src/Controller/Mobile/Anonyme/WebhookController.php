<?php

namespace App\Controller\Mobile\Anonyme;

use App\Entity\Clip;
use App\Entity\Live;
use App\Entity\User;
use App\Entity\Vendor;
use App\Entity\Message;
use App\Entity\Product;
use App\Entity\Category;
use App\Repository\ClipRepository;
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
    // $this->get('bugsnag')->notifyException(new Exception($result["payload"]["id"]));

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
          $manager->remove($clip);
          $manager->flush();
        }

        $live = $liveRepo->findOneByBroadcastId($broadcastId);

        if ($live) {
          $liveProducts = $live->getLiveProducts();

          if ($liveProducts) {
            foreach ($liveProducts as $liveProduct) {
              $manager->remove($liveProduct);
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
  public function stripe(Request $request) {
    $result = json_decode($request->getContent(), true);
    $this->get('bugsnag')->notifyException(new Exception($result));

    // This is your Stripe CLI webhook secret for testing your endpoint locally.
    $endpoint_secret = 'whsec_143bc4785520f2730325d443bc883c3fb44fbeb568626c0216cd7a0adab70b5a';

    $payload = @file_get_contents('php://input');
    $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
    $event = null;

    try {
      $event = \Stripe\Webhook::constructEvent(
        $payload, $sig_header, $endpoint_secret
      );
    } catch(\UnexpectedValueException $e) {
  // Invalid payload
      http_response_code(400);
      exit();
    } catch(\Stripe\Exception\SignatureVerificationException $e) {
  // Invalid signature
      http_response_code(400);
      exit();
    }

  // Handle the event
    switch ($event->type) {
      case 'account.updated':
      $account = $event->data->object;
      case 'account.external_account.created':
      $externalAccount = $event->data->object;
      case 'account.external_account.deleted':
      $externalAccount = $event->data->object;
      case 'account.external_account.updated':
      $externalAccount = $event->data->object;
      case 'payment_intent.amount_capturable_updated':
      $paymentIntent = $event->data->object;
      case 'payment_intent.canceled':
      $paymentIntent = $event->data->object;
      case 'payment_intent.created':
      $paymentIntent = $event->data->object;
      case 'payment_intent.payment_failed':
      $paymentIntent = $event->data->object;
      case 'payment_intent.processing':
      $paymentIntent = $event->data->object;
      case 'payment_intent.requires_action':
      $paymentIntent = $event->data->object;
      case 'payment_intent.succeeded':
      $paymentIntent = $event->data->object;
  // ... handle other event types
      default:
      echo 'Received unknown event type ' . $event->type;
    }

    return $this->json(true, 200);
  }
  
}