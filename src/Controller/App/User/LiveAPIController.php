<?php

namespace App\Controller\App\User;

use App\Entity\Vendor;
use App\Entity\Clip;
use App\Entity\Live;
use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\Follow;
use App\Entity\Product;
use App\Entity\LiveProducts;
use App\Entity\Upload;
use App\Repository\LiveProductsRepository;
use App\Repository\FollowRepository;
use App\Repository\VendorRepository;
use App\Repository\ClipRepository;
use App\Repository\ProductRepository;
use App\Repository\CommentRepository;
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


class LiveAPIController extends Controller {

  /**
   * Préparer un live
   *
   * @Route("/user/api/prelive", name="user_api_prelive_step1", methods={"POST"})
   */
  public function prelive(Request $request, ObjectManager $manager, SerializerInterface $serializer) {
    if ($json = $request->getContent()) {
      $live = $serializer->deserialize($json, Live::class, "json");
      $live->setVendor($this->getUser()->getVendor());

      $manager->persist($live);
      $manager->flush();

      return $this->json($live, 200, [], ['groups' => 'live:read'], 200);
    }

    return $this->json([ "error" => "Une erreur est survenue"], 404);
  }


  /**
   * Editer un liveproduct   
   * 
   * @Route("/user/api/liveproducts/edit/{id}", name="user_api_liveproducts_edit", methods={"PUT"})
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
   * @Route("/user/api/live/{id}", name="user_api_live", methods={"GET"})
   */
  public function live(Live $live, Request $request, ObjectManager $manager) {
    return $this->json($live, 200, [], ['groups' => 'live:read'], 200);
  }


  /**
   * Mettre à jour un live
   *
   * @Route("/user/api/live/update/{id}", name="user_api_live_update", methods={"PUT"})
   */
  public function updateLive(Live $live, Request $request, ObjectManager $manager, SerializerInterface $serializer) {
    if ($json = $request->getContent()) {
      $param = json_decode($json, true);
      $broadcastId = $param["broadcastId"];

      // return $this->json($live, 200, [], ['groups' => 'live:read'], 200);

      if ($broadcastId && !$live->getBroadcastId() && $live->getStatus() != 2) {
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
          $gmdate = gmdate("d-m-Y H:i:s", $unix);
          $createdAt = new \DateTime($gmdate);
          $createdAt->modify('+1 hour');

          $live->setBroadcastId($broadcastId);
          $live->setResourceUri($result->resourceUri);
          $live->setPreview($result->preview);
          $live->setCreatedAt($createdAt);
          $live->setStatus(1);
          $manager->flush();

          $channel = "channel" . $live->getId();
          $event = "event" . $live->getId();
          $user = $this->getUser();

          $data = [
            "comment" => [
              "content" => "Début du live", 
              "user" => [
                "firstname" => $user->getFirstname(),
                "lastname" => $user->getLastname(),
                "picture" => $user->getPicture()
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
   * Mettre à jour le produit pendant le live
   *
   * @Route("/user/api/live/{id}/update/display", name="user_api_live_update_display", methods={"PUT"})
   */
  public function updateDisplay(Live $live, Request $request, ObjectManager $manager, SerializerInterface $serializer, LiveProductsRepository $liveProductRepo, CommentRepository $commentRepo) {
    if ($json = $request->getContent()) {
      $param = json_decode($json, true);
      $display = $param["display"];
      $user = $this->getUser();

      $live->setDisplay($display);
      $manager->flush();

      $pusher = new \Pusher\Pusher('7fb21964a6ad128ed1ae', 'edede4d885179511adc3', '1299503', [ 'cluster' => 'eu', 'useTLS' => true ]);
      $pusher->trigger($live->getChannel(), $live->getEvent(), [ "display" => $display ]);

      // créer le clip pour le produit précédent
      $display = $display - 1;
      $liveProduct = $liveProductRepo->findOneBy([ "live" => $live, "priority" => $display ]);

      if ($liveProduct) {
        if ($display == 1) {
          $start = 1;
        } else {
          $start = $live->getDuration() + 1;
        }

        $created = $live->getCreatedAt();
        $now = new \DateTime('now', timezone_open('Europe/Paris'));
        $diff = $now->diff($created);
        $end = $this->dateIntervalToSeconds($diff);
        $duration = $end - $start;

        if ($duration > 15) {
          $clip = new Clip();
          $clip->setVendor($user->getVendor());
          $clip->setLive($live);
          $clip->setProduct($liveProduct->getProduct());
          $clip->setPreview($live->getPreview());
          $clip->setStart($start);
          $clip->setEnd($end);
          $clip->setDuration($duration);

          $manager->persist($clip);
          $manager->flush();

          $live->setDuration($end);
          $comments = $commentRepo->findByLiveAndClipNull($live);

          foreach ($comments as $comment) {
            $comment->setClip($clip);
          }

          $manager->flush();
        }
      }
    
      return $this->json($live, 200, [], ['groups' => 'live:read'], 200);
    }
  }


  /**
   * Arreter un live
   *
   * @Route("/user/api/live/stop/{id}", name="user_api_live_stop", methods={"PUT"})
   */
  public function stopLive(Live $live, Request $request, ObjectManager $manager, SerializerInterface $serializer, LiveProductsRepository $liveProductRepo, CommentRepository $commentRepo) {
    $live->setStatus(2);
    $manager->flush();

    // créer le dernier clip
    $liveProduct = $liveProductRepo->findOneBy([ "live" => $live, "priority" => $live->getDisplay() ]);

    if ($liveProduct) {
      if ($display == 1) {
        $start = 1;
      } else {
        $start = $live->getDuration() + 1;
      }

      $url = "https://api.bambuser.com/broadcasts/" . $live->getBroadcastId();
      $ch = curl_init();

      curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "Accept: application/vnd.bambuser.v1+json", "Authorization: Bearer 2NJko17PqQdCDQ1DRkyMYr"]);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
      curl_setopt($ch, CURLOPT_URL, $url);

      $result = curl_exec($ch);
      $result = json_decode($result);
      curl_close($ch);

      if ($result && $result->id) {
        $end = $result->length - 1;
        $duration = $end - $start;

        if ($duration > 15) {
          $clip = new Clip();
          $clip->setVendor($this->getUser()->getVendor());
          $clip->setLive($live);
          $clip->setProduct($liveProduct->getProduct());
          $clip->setPreview($live->getPreview());
          $clip->setStart($start);
          $clip->setEnd($end);
          $clip->setDuration($duration);

          $manager->persist($clip);
          $manager->flush();

          $live->setDuration($end);
          $comments = $commentRepo->findByLiveAndClipNull($live);

          foreach ($comments as $comment) {
            $comment->setClip($clip);
          }

          $manager->flush();
        }
      }
    }

    return $this->json($live, 200, [], ['groups' => 'live:read'], 200);
  }



  /**
   * Ajouter un comment pendant le live
   *
   * @Route("/user/api/live/{id}/comment/add", name="user_api_live_comment_add", methods={"POST"})
   */
  public function addCommentLive(Live $live, Request $request, ObjectManager $manager, SerializerInterface $serializer) {
    if ($json = $request->getContent()) {
      $param = json_decode($json, true);
      $content = $param["content"];
      $user = $this->getUser();

      $comment = new Comment();
      $comment->setContent($content);
      $comment->setUser($user);
      $comment->setLive($live);
      $manager->persist($comment);
      $manager->flush();

      $data = [
        "comment" => [
          "content" => $content, 
          "user" => [
            "firstname" => $user->getFirstname(),
            "lastname" => $user->getLastname(),
            "picture" => $user->getPicture() 
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
   * @Route("/user/api/live/{id}/update/viewers", name="user_api_live_update_viewers", methods={"PUT"})
   */
  public function updateViewers(Live $live, Request $request, ObjectManager $manager, SerializerInterface $serializer) {
    $user = $this->getUser();
    $pusher = new \Pusher\Pusher('7fb21964a6ad128ed1ae', 'edede4d885179511adc3', '1299503', [ 'cluster' => 'eu', 'useTLS' => true ]);
    $info = $pusher->getChannelInfo($live->getChannel(), ['info' => 'subscription_count']);
    $count = $info->subscription_count;

    if ($count) {
      $count = $count - 1;
      $live->setViewers($count);
      $manager->flush();
    }

    $data = [ 
      "viewers" => $count
    ];

    $pusher->trigger($live->getChannel(), $live->getEvent(), $data);

    return $this->json($live, 200, [], ['groups' => 'live:read'], 200);
  }


  function dateIntervalToSeconds($dateInterval) {
    $reference = new \DateTimeImmutable;
    $endTime = $reference->add($dateInterval);

    return $reference->getTimestamp() - $endTime->getTimestamp();
  }
}
