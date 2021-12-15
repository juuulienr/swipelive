<?php

namespace App\Controller\Vendor;

use App\Entity\Vendor;
use App\Entity\Clip;
use App\Entity\Live;
use App\Entity\Category;
use App\Entity\Message;
use App\Entity\Follow;
use App\Entity\Product;
use App\Entity\LiveProducts;
use App\Entity\Upload;
use App\Repository\FollowRepository;
use App\Repository\VendorRepository;
use App\Repository\ClipRepository;
use App\Repository\ProductRepository;
use App\Repository\LiveRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Carbon\Carbon; 


class LiveAPIController extends Controller {

  /**
   * Préparer un live
   *
   * @Route("/vendor/api/prelive", name="vendor_api_prelive_step1", methods={"POST"})
   */
  public function prelive(Request $request, ObjectManager $manager, SerializerInterface $serializer) {
    if ($json = $request->getContent()) {
      $live = $serializer->deserialize($json, Live::class, "json");
      $live->setVendor($this->getUser());

      $manager->persist($live);
      $manager->flush();

      return $this->json($live, 200, [], ['groups' => 'live:read'], 200);
    }

    return $this->json([ "error" => "Une erreur est survenue"], 404);
  }


  /**
   * Editer un liveproduct   
   * 
   * @Route("/vendor/api/liveproducts/edit/{id}", name="vendor_api_liveproducts_edit", methods={"PUT"})
   */
  public function prelive2(LiveProducts $liveProduct, Request $request, ObjectManager $manager, SerializerInterface $serializer) {
    if ($json = $request->getContent()) {
      $serializer->deserialize($json, LiveProducts::class, "json", [AbstractNormalizer::OBJECT_TO_POPULATE => $liveProduct]);
      $manager->flush();

      return $this->json(true, 200);
    }

    return $this->json(false, 404);
  }


  /**
   * Récupérer un live
   *
   * @Route("/vendor/api/live/{id}", name="vendor_api_live", methods={"GET"})
   */
  public function live(Live $live, Request $request, ObjectManager $manager) {
    return $this->json($live, 200, [], ['groups' => 'live:read'], 200);
  }


  /**
   * Mettre à jour le produit pendant le live
   *
   * @Route("/vendor/api/live/{id}/update/display", name="vendor_api_live_update_display", methods={"PUT"})
   */
  public function updateDisplay(Live $live, Request $request, ObjectManager $manager, SerializerInterface $serializer) {
    if ($json = $request->getContent()) {
      $param = json_decode($json, true);
      $display = $param["display"];
      $vendor = $this->getUser();

      $live->setDisplay($display);
      $manager->flush();

      // créer le clip
      $liveProduct = $repo->findOneBy([ "live" => $live, "display" => $display ]);

      if ($liveProduct) {
        $clip = new Clip();
        $clip->setVendor($vendor);
        $clip->setLive($live);
        $clip->setThumbnail($live->getThumbnail());
        $clip->setProduct($liveProduct->getProduct());

        if ($display < 2) {
          $start = 0;
        } else {
          $start = $live->getDuration() + 1;
        }

        $created = $live->getCreatedAt();
        $now = Carbon::now();
        $end = $now->diffInSeconds($created);

        $clip->setStart($start);
        $clip->setEnd($end);
        $clip->setDuration($end - $start);

        $data = [
          "source" => [
            "broadcastId" => $live->getBroadcastId(), 
            "start" => $start, 
            "end" => $end
          ]
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "Accept: application/vnd.bambuser.v1+json", "Authorization: Bearer 2NJko17PqQdCDQ1DRkyMYr"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_URL, "https://api.bambuser.com/broadcasts");

        $result = curl_exec($ch);
        $result = json_decode($result);
        curl_close($ch);

        if ($result && $result->newBroadcastId) {
          $clip->setBroadcastId($result->newBroadcastId);
          $clip->setStatus($result->status);
        }

        $live->setDuration($end);

        $manager->persist($clip);
        $manager->flush();
      }

      $pusher = new \Pusher\Pusher('7fb21964a6ad128ed1ae', 'edede4d885179511adc3', '1299503', [ 'cluster' => 'eu', 'useTLS' => true ]);
      $pusher->trigger($live->getChannel(), $live->getEvent(), [ "display" => $display ]);
    
      return $this->json($live, 200, [], ['groups' => 'live:read'], 200);
    }
  }


  /**
   * Mettre à jour un live
   *
   * @Route("/vendor/api/live/update/{id}", name="vendor_api_live_update", methods={"PUT"})
   */
  public function updateLive(Live $live, Request $request, ObjectManager $manager, SerializerInterface $serializer) {
    if ($json = $request->getContent()) {
      $param = json_decode($json, true);
      $broadcastId = $param["broadcastId"];

      if ($broadcastId) {
        $url = "https://api.bambuser.com/broadcasts/" . $broadcastId;
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "Accept: application/vnd.bambuser.v1+json", "Authorization: Bearer 2NJko17PqQdCDQ1DRkyMYr"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_URL, $url);

        $result = curl_exec($ch);
        $result = json_decode($result);
        curl_close($ch);

        if ($result && $result->id) {
          $unix = $result->created;
          $createdAt = new Carbon($unix, 'Europe/Paris');
          $createdAt->add('1', "hour");

          $live->setBroadcastId($broadcastId);
          $live->setResourceUri($result->resourceUri);
          $live->setThumbnail($result->preview);
          $live->setCreatedAt($createdAt);
          $live->setStatus(1);
          $manager->flush();

          $channel = "channel" . $live->getId();
          $event = "event" . $live->getId();
          $vendor = $this->getUser();

          $data = [
            "message" => [
              "content" => "Début du live", 
              "user" => null, 
              "vendor" => [
                "company" => $vendor->getCompany(),
                "firstname" => $vendor->getFirstname(),
                "lastname" => $vendor->getLastname(),
                "picture" => $vendor->getPicture()
              ]
            ]
          ];

          $pusher = new \Pusher\Pusher('7fb21964a6ad128ed1ae', 'edede4d885179511adc3', '1299503', [ 'cluster' => 'eu', 'useTLS' => true ]);
          $pusher->trigger($channel, $event, $data);

          $live->setChannel($channel);
          $live->setEvent($event);
          $manager->flush();

          return $this->json($live, 200, [], ['groups' => 'live:read'], 200);
        } else {
          return $this->json(false, 404);
        }
      }
    }
  }


  /**
   * Arreter un live
   *
   * @Route("/vendor/api/live/stop/{id}", name="vendor_api_live_stop", methods={"PUT"})
   */
  public function stopLive(Live $live, Request $request, ObjectManager $manager, SerializerInterface $serializer) {
    if ($json = $request->getContent()) {
      $live->setStatus(2);
      $manager->flush();

      return $this->json($live, 200, [], ['groups' => 'live:read'], 200);
    }

    return $this->json(false, 404);
  }



  /**
   * Ajouter un message pendant le live
   *
   * @Route("/vendor/api/live/{id}/message/add", name="vendor_api_live_message_add", methods={"POST"})
   */
  public function addMessageLive(Live $live, Request $request, ObjectManager $manager, SerializerInterface $serializer) {
    if ($json = $request->getContent()) {
      $param = json_decode($json, true);
      $content = $param["content"];
      $vendor = $this->getUser();

      $message = new Message();
      $message->setContent($content);
      $message->setVendor($vendor);
      $message->setLive($live);
      $manager->persist($message);
      $manager->flush();

      $data = [
        "message" => [
          "content" => $content, 
          "user" => null, 
          "vendor" => [
            "company" => $vendor->getCompany(),
            "firstname" => $vendor->getFirstname(),
            "lastname" => $vendor->getLastname(),
            "picture" => $vendor->getPicture()
          ]
        ]
      ];
      
      $pusher = new \Pusher\Pusher('7fb21964a6ad128ed1ae', 'edede4d885179511adc3', '1299503', [ 'cluster' => 'eu', 'useTLS' => true ]);
      $pusher->trigger($live->getChannel(), $live->getEvent(), $data);

      return $this->json($live, 200, [], ['groups' => 'live:read'], 200);
    }
  }


  /**
   * Mettre à jour les vues sur un live
   *
   * @Route("/vendor/api/live/{id}/update/viewers", name="vendor_api_live_update_viewers", methods={"PUT"})
   */
  public function updateViewers(Live $live, Request $request, ObjectManager $manager, SerializerInterface $serializer) {
    $vendor = $this->getUser();
    $pusher = new \Pusher\Pusher('7fb21964a6ad128ed1ae', 'edede4d885179511adc3', '1299503', [ 'cluster' => 'eu', 'useTLS' => true ]);
    $info = $pusher->getChannelInfo($live->getChannel(), ['info' => 'subscription_count']);
    $count = $info->subscription_count;

    if ($count) {
      $count = $count - 1;
      $live->setViewers($count);
      $manager->flush();
    }

    $data = [ 
      "viewers" => $count,
      "entrances" => [
        "user" => null, 
        "vendor" => [
          "company" => $vendor->getCompany(),
          "firstname" => $vendor->getFirstname(),
          "lastname" => $vendor->getLastname(),
          "picture" => $vendor->getPicture()
        ]
      ]
    ];

    $pusher->trigger($live->getChannel(), $live->getEvent(), $data);

    return $this->json($live, 200, [], ['groups' => 'live:read'], 200);
  }
}
