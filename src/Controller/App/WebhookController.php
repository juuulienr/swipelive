<?php

namespace App\Controller\App;

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


class WebhookController extends Controller {

  /**
   * Webhooks Bambuser
   *
   * @Route("/api/bambuser/webhooks", name="api_bambuser_webhooks", methods={"POST"})
   */
  public function bambuser(Request $request, ClipRepository $clipRepo, LiveRepository $liveRepo, ObjectManager $manager, LiveProductsRepository $liveProductRepo) {
    $result = json_decode($request->getContent(), true);
    
    // try {
    //   $this->functionFailsForSure();
    // } catch (\Throwable $exception) {
     // \Sentry\captureMessage("Bambuser error");
    //   \Sentry\captureException($exception);
    // }

    // broadcast
    if ($result["collection"] == "broadcast") {

      // broadcast add
      if ($result["action"] == "add") {
        $broadcastId = $result["payload"]["id"];
        $clip = $clipRepo->findOneByBroadcastId($broadcastId);

        if ($clip) {
          $clip->setResourceUri($result["payload"]["resourceUri"]);
          $clip->setEventId($result["eventId"]);

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

          if (array_key_exists("status", $result["payload"])) {
            $clip->setStatus("available");
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
   * Sendcloud Weebhook
   *
   * @Route("/api/sendcloud/webhooks", name="api_sendcloud_webhooks")", methods={"POST"})
   */
  public function sendcloud(Request $request, ObjectManager $manager, OrderRepository $orderRepo, OrderStatusRepository $statusRepo) {
    $result = json_decode($request->getContent(), true);

    // update parcel status
    if ($result["action"] == "parcel_status_changed") {
      $parcelId = $result["parcel"]["id"];
      $order = $orderRepo->findOneByParcelId($parcelId);

      if ($order) {
        if ($order->getTrackingNumber()) {
          $url = "https://panel.sendcloud.sc/api/v2/tracking" . '?' . $order->getTrackingNumber();
          $curl = curl_init();

          curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
              "Authorization: Basic MzgyNjY4NmYyZGJjNDE4MzgwODk4Y2MyNTRmYzBkMjg6MDk2ZTQ0Y2I5YjI2NDMxYjkwY2M1YjVkZWZjOWU5MTU=",
              "Content-Type: application/json"
            ],
          ]);

          $response = curl_exec($curl);
          $result = json_decode($response);
          curl_close($curl);

          if ($result && array_key_exists("expected_delivery_date", $result)) {
            $order->setExpectedDelivery(new \Datetime($result->expected_delivery_date));
            $manager->flush();

            foreach ($result->statuses as $status) {
              $orderStatus = $statusRepo->findOneByStatusId($status->parcel_status_history_id);

              if (!$orderStatus) {
                $orderStatus = new OrderStatus();
                $orderStatus->setUpdateAt(new \Datetime($status->carrier_update_timestamp));
                $orderStatus->setMessage($status->carrier_message);
                $orderStatus->setStatus($status->parent_status);
                $orderStatus->setCode($status->carrier_code);
                $orderStatus->setStatusId($status->parcel_status_history_id);
                $orderStatus->setShipping($order);
                $order->setShippingStatus($status->parent_status);
                $order->setUpdatedAt(new \Datetime($status->carrier_update_timestamp));

                $manager->persist($orderStatus);
                $manager->flush();
              }
            }
          }
        }
      }
    }
    return $this->json(true, 200);
  }
}


