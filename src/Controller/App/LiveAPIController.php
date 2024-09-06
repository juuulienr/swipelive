<?php

namespace App\Controller\App;

use App\Entity\Vendor;
use App\Entity\Clip;
use App\Entity\Live;
use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\Follow;
use App\Entity\Product;
use App\Entity\Order;
use App\Entity\LiveProducts;
use App\Entity\Upload;
use App\Repository\LiveProductsRepository;
use App\Repository\FollowRepository;
use App\Repository\VendorRepository;
use App\Repository\UserRepository;
use App\Repository\ClipRepository;
use App\Repository\ProductRepository;
use App\Repository\CommentRepository;
use App\Repository\LiveRepository;
use App\Repository\OrderRepository;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Service\NotifPushService;


class LiveAPIController extends AbstractController {

  private $notifPushService;
  private $httpClient;

  public function __construct(NotifPushService $notifPushService, HttpClientInterface $httpClient) {
    $this->httpClient = $httpClient;
    $this->notifPushService = $notifPushService;
  }


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

      $liveProducts = $live->getLiveProducts()->toArray();

      if (sizeof($liveProducts) == 1) {
        $liveProducts[0]->setPriority(1);
        $manager->flush();
      }

	    return $this->json($live, 200, [], [
	    	'groups' => 'live:read', 
	    	'circular_reference_limit' => 1, 
	    	'circular_reference_handler' => function ($object) {
	    		return $object->getId();
	    	} 
	    ]);
    }

    return $this->json("Une erreur est survenue", 404);
  }


  /**
   * Modifier l'ordre des produits   
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
    return $this->json($live, 200, [], [
    	'groups' => 'live:read', 
    	'circular_reference_limit' => 1, 
    	'circular_reference_handler' => function ($object) {
    		return $object->getId();
    	} 
    ]);
  }


  /**
   * Mettre à jour un live
   *
   * @Route("/user/api/live/update/{id}", name="user_api_live_update", methods={"PUT"})
   */
  public function updateLive(Live $live, Request $request, ObjectManager $manager, SerializerInterface $serializer, UserRepository $userRepo) {
    if ($json = $request->getContent()) {
      $param = json_decode($json, true);

      $live->setCreatedAt(new \DateTime('now', timezone_open('UTC')));
      $live->setBroadcastId("test");
      $live->setResourceUri("test");
      $live->setPreview("test");
      $live->setStatus(1);
      $manager->flush();
   
      $channel = "channel" . $live->getId();
      $event = "event" . $live->getId();
      $user = $this->getUser();
      $businessName = $user->getVendor()->getBusinessName();

      $data = [
        "comment" => [
          "content" => "Début du live", 
          "user" => [
            "vendor" => [
              "businessName" => $businessName,
            ],
            "firstname" => $user->getFirstname(),
            "lastname" => $user->getLastname(),
            "picture" => $user->getPicture()
          ]
        ]
      ];

      $pusher = new \Pusher\Pusher('55da4c74c2db8041edd6', 'd61dc5df277d1943a6fa', '1274340', [ 'cluster' => 'eu', 'useTLS' => true ]);
      $pusher->trigger($channel, $event, $data);

      $live->setChannel($channel);
      $live->setEvent($event);
      $manager->flush();

      $followers = $userRepo->findUserFollowers($user);

      if ($followers) {
        foreach ($followers as $follower) {
          if ($follower->getPushToken()) {
            try {
              $this->notifPushService->send("SWIPE LIVE", "🔴 " . $businessName . " est actuellement en direct", $follower->getPushToken());
            } catch (\Exception $error) {
              $this->get('bugsnag')->notifyError('ErrorType', $error);
            }
          }
        }
      }


      try {
        // Appel à l'API `acquire` pour obtenir un `resourceId`
        $acquireUrl = sprintf('https://api.agora.io/v1/apps/%s/cloud_recording/acquire', $this->getParameter('agora_app_id'));
        $this->get('bugsnag')->notifyError('ErrorType', $this->getParameter('agora_app_id'));
        $this->get('bugsnag')->notifyError('ErrorType', $this->getParameter('agora_app_certificate'));
        $this->get('bugsnag')->notifyError('ErrorType', $live->getVendor()->getId());
        $this->get('bugsnag')->notifyError('ErrorType', $channel);

        // Appel à l'API `acquire`
        $acquireResponse = $this->httpClient->request('POST', $acquireUrl, [
          'auth_basic' => [$this->getParameter('agora_app_id'), $this->getParameter('agora_app_certificate')],
          'json' => [
            'cname' => $channel,
            'uid' => $live->getVendor()->getId(),
            'clientRequest' => []
          ],
        ]);

        // Récupérer la réponse et le `resourceId`
        $acquireData = $acquireResponse->toArray();
        $resourceId = $acquireData['resourceId'];

        // Une fois `resourceId` obtenu, appeler l'API `start`
        $startUrl = sprintf('https://api.agora.io/v1/apps/%s/cloud_recording/resourceid/%s/mode/mix/start', $this->getParameter('agora_app_id'), $resourceId);

        $startBody = [
          'cname' => $channel,
          'uid' => $live->getVendor()->getId(),
          'clientRequest' => [
            'recordingConfig' => [
              'maxIdleTime' => 30,
              'streamTypes' => 2,
              'channelType' => 1,
              'videoStreamType' => 0,
              'transcodingConfig' => [
                'width' => 1280,
                'height' => 720,
                'fps' => 30,
                'bitrate' => 800
              ]
            ],
            'storageConfig' => [
              'vendor' => 2,  // 2 pour AWS S3
              'region' => 0,
              'bucket' => 'swipe-live-app-storage',
              'accessKey' => 'AKIAYXWBN6DLIY2K6V4X',
              'secretKey' => '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'
            ]
          ]
        ];

        // Appel à l'API `start` pour démarrer l'enregistrement
        $startResponse = $this->httpClient->request('POST', $startUrl, [
          'json' => $startBody,
          'auth_basic' => [$this->getParameter('agora_app_id'), $this->getParameter('agora_app_certificate')],
        ]);

        // Récupérer la réponse de l'API `start`
        $startData = $startResponse->toArray();
        $this->get('bugsnag')->notifyError('ErrorType', $startData);

        // Retourner une réponse JSON contenant les informations de démarrage
        return new JsonResponse([
          'success' => true,
          'message' => 'Recording started successfully',
          'startData' => $startData
        ], JsonResponse::HTTP_OK);
      } catch (\Exception $e) {
        // Gestion des erreurs
        return new JsonResponse([
          'error' => 'Failed to start recording',
          'message' => $e->getMessage()
        ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
      }


      return $this->json($live, 200, [], [
        'groups' => 'live:read', 
        'circular_reference_limit' => 1, 
        'circular_reference_handler' => function ($object) {
          return $object->getId();
        } 
      ]);
    }
    
    return $this->json(false, 404);
  }



  /**
   * Stream sur facebook
   *
   * @Route("/user/api/live/update/stream/{id}", name="user_api_live_update_stream", methods={"PUT"})
   */
  public function updateStream(Live $live, Request $request, ObjectManager $manager, SerializerInterface $serializer) {
    if ($json = $request->getContent()) {
      $param = json_decode($json, true);
      $fbIdentifier = $param["fbIdentifier"];
      $showGroupsPage = $param["showGroupsPage"];
      $fbPageIdentifier = $param["fbPageIdentifier"];
      $fbToken = $param["fbToken"];
      $fbTokenPage = $param["fbTokenPage"];
      $pages = $param["pages"];
      $groups = $param["groups"];


      // create fb stream
      $fb = new \Facebook\Facebook([
        'app_id' => '936988141017522',
        'app_secret' => '025a5522cd75e464437fb048ee3cfe23',
        'default_graph_version' => 'v2.10',
      ]);

      $data = [
        'title' => 'Live sur Swipe Live',
        'description' => 'Live sur Swipe Live',
        'status' => 'LIVE_NOW',
        // 'privacy' => [
          // 'value' => "EVERYONE"
        // ]
      ];


      try {
        if ($fbTokenPage && $fbPageIdentifier) {
          $url = $fbPageIdentifier . "/live_videos?fields=id,permalink_url,secure_stream_url";
          $response = $fb->post($url, $data, $fbTokenPage);
        } else {
          $url = $fbIdentifier . "/live_videos?fields=id,permalink_url,secure_stream_url";
          $response = $fb->post($url, $data, $fbToken);
        }
      } catch(\Facebook\Exceptions\FacebookResponseException $e) {
        return $this->json("Graph returned an error: " . $e->getMessage(), 404);
      } catch(\Facebook\Exceptions\FacebookSDKException $e) {
        return $this->json("Facebook SDK returned an error: " . $e->getMessage(), 404);
      }

      $result = $response->getGraphNode();

      if ($result) {
        $fbStreamId = $result["id"];
        $fbStreamUrl = $result["secure_stream_url"];
        $fbPermalinkUrl = $result["permalink_url"];
        $postUrl = 'https://www.facebook.com' . $fbPermalinkUrl;

        if ($groups && sizeof($groups) > 0) {
          foreach ($groups as $group) {
            if ($group["name"] == "Test Live") {
              $url = '/' . $group['id'] . '/feed';

              try {
                $response = $fb->post($url, [ 'link' => $postUrl, "message" => "Partage du live" ], $fbToken);
              } catch (Facebook\Exceptions\FacebookResponseException $e) {
                return $this->json("Facebook SDK returned an error: " . $e->getMessage(), 404);
              } catch (Facebook\Exceptions\FacebookSDKException $e) {
                return $this->json("Facebook SDK returned an error: " . $e->getMessage(), 404);
              }
            }
          }
        }
      }

      $live->setFbStreamId($fbStreamId);
      $live->setFbStreamUrl($fbStreamUrl);
      $live->setPostUrl($postUrl);
      $manager->flush();

      return $this->json([ "fbStreamId" => $fbStreamId ], 200, [], [
        'groups' => 'live:read',
        'circular_reference_limit' => 1,
        'circular_reference_handler' => function ($object) {
          return $object->getId();
        } 
      ]);
    }
    
    return $this->json(false, 404);
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

      $pusher = new \Pusher\Pusher('55da4c74c2db8041edd6', 'd61dc5df277d1943a6fa', '1274340', [ 'cluster' => 'eu', 'useTLS' => true ]);
      $pusher->trigger($live->getChannel(), $live->getEvent(), [ "display" => $display ]);

      // créer le clip pour le produit précédent
      $display = $display - 1;
      $liveProduct = $liveProductRepo->findOneBy([ "live" => $live, "priority" => $display ]);

      if ($liveProduct) {
        if ($display == 1) {
          $start = 5;
        } else {
          $start = $live->getDuration() + 1;
        }

        $created = $live->getCreatedAt();
        $now = new \DateTime('now', timezone_open('UTC'));
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
    
  		return $this->json($live, 200, [], [
	    	'groups' => 'live:read', 
	    	'circular_reference_limit' => 1, 
	    	'circular_reference_handler' => function ($object) {
	    		return $object->getId();
	    	} 
	    ]);
    }
  }


  /**
   * Arreter un live
   *
   * @Route("/user/api/live/stop/{id}", name="user_api_live_stop", methods={"PUT"})
   */
  public function stopLive(Live $live, Request $request, ObjectManager $manager, SerializerInterface $serializer, LiveProductsRepository $liveProductRepo, CommentRepository $commentRepo) {
    $json = $request->getContent();
    $param = json_decode($json, true);
    $live->setStatus(2);
    $manager->flush();
    $fbStreamId = $param["fbStreamId"];
    $fbToken = $param["fbToken"];


    // if ($live->getBroadcastId()) {
    //   // créer le dernier clip
    //   $liveProduct = $liveProductRepo->findOneBy([ "live" => $live, "priority" => $live->getDisplay() ]);

    //   if ($liveProduct) {
    //     if ($live->getDisplay() == 1) {
    //       $start = 5;
    //     } else {
    //       $start = $live->getDuration() + 1;
    //     }

    //     $url = "https://api.bambuser.com/broadcasts/" . $live->getBroadcastId();
    //     $ch = curl_init();

    //     curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "Accept: application/vnd.bambuser.v1+json", "Authorization: Bearer RkbHZdUPzA8Rcu2w4b1jn9"]);
    //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //     curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    //     curl_setopt($ch, CURLOPT_URL, $url);

    //     $result = curl_exec($ch);
    //     $result = json_decode($result);
    //     curl_close($ch);

    //     if ($result && $result->id) {
    //       $end = $result->length - 5;
    //       $duration = $end - $start;

    //       if ($duration > 15) {
    //         $clip = new Clip();
    //         $clip->setVendor($this->getUser()->getVendor());
    //         $clip->setLive($live);
    //         $clip->setProduct($liveProduct->getProduct());
    //         $clip->setPreview($live->getPreview());
    //         $clip->setStart($start);
    //         $clip->setEnd($end);
    //         $clip->setDuration($duration);

    //         $manager->persist($clip);
    //         $manager->flush();

    //         $live->setDuration($end);
    //         $comments = $commentRepo->findByLiveAndClipNull($live);

    //         foreach ($comments as $comment) {
    //           $comment->setClip($clip);
    //         }

    //         $manager->flush();
    //       }
    //     }
    //   }
    // }

      // stop stream sur facebook
      if ($fbStreamId) {
        $url = "/" . $fbStreamId . "/?end_live_video=true";
        $fb = new \Facebook\Facebook([
          'app_id' => '936988141017522',
          'app_secret' => '025a5522cd75e464437fb048ee3cfe23',
          'default_graph_version' => 'v2.10',
        ]);

        try {
          $response = $fb->post($url, [], $fbToken);
        } catch(\Facebook\Exceptions\FacebookResponseException $e) {
          return $this->json("Graph returned an error: " . $e->getMessage(), 404);
        } catch(\Facebook\Exceptions\FacebookSDKException $e) {
          return $this->json("Facebook SDK returned an error: " . $e->getMessage(), 404);
        }
      }

    return $this->json($this->getUser(), 200, [], [
      'groups' => 'user:read', 
      'circular_reference_limit' => 1, 
      'circular_reference_handler' => function ($object) {
        return $object->getId();
      } 
    ]);
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

      if ($user->getVendor()) {
        $vendor = [
          "businessName" => $user->getVendor()->getBusinessName(),
        ];
      } else {
        $vendor = null;
      }

      $data = [
        "comment" => [
          "content" => $content, 
          "user" => [
            "vendor" => $vendor,
            "firstname" => $user->getFirstname(),
            "lastname" => $user->getLastname(),
            "picture" => $user->getPicture()
          ]
        ]
      ];
      
      $pusher = new \Pusher\Pusher('55da4c74c2db8041edd6', 'd61dc5df277d1943a6fa', '1274340', [ 'cluster' => 'eu', 'useTLS' => true ]);
      $pusher->trigger($live->getChannel(), $live->getEvent(), $data);

			return $this->json($live, 200, [], [
	    	'groups' => 'live:read', 
	    	'circular_reference_limit' => 1, 
	    	'circular_reference_handler' => function ($object) {
	    		return $object->getId();
	    	} 
	    ]);
    }
  }


  /**
   * Mettre à jour les vues sur un live
   *
   * @Route("/user/api/live/{id}/update/viewers", name="user_api_live_update_viewers", methods={"PUT"})
   */
  public function updateViewers(Live $live, Request $request, ObjectManager $manager, SerializerInterface $serializer) {
    $pusher = new \Pusher\Pusher('55da4c74c2db8041edd6', 'd61dc5df277d1943a6fa', '1274340', [ 'cluster' => 'eu', 'useTLS' => true ]);
    $info = $pusher->getChannelInfo($live->getChannel(), ['info' => 'subscription_count']);
    $count = $info->subscription_count;
    $user = $this->getUser();
    $count = $count - 1;

    if ($user->getVendor()) {
      $vendor = [
        "businessName" => $user->getVendor()->getBusinessName(),
      ];
    } else {
      $vendor = null;
    }

    if ($live->getViewers() > $count) {
      $type = "remove";
    } else {
      $type = "add";
    }

    $live->setViewers($count);
    $live->setTotalViewers($live->getTotalViewers() + 1);
    $manager->flush();

    $data = [
      "viewers" => [
        "count" => $count, 
        "type" => $type, 
        "user" => [
          "id" => $user->getId(),
          "vendor" => $vendor,
          "firstname" => $user->getFirstname(),
          "lastname" => $user->getLastname(),
          "picture" => $user->getPicture()
        ]
      ]
    ];

    $pusher->trigger($live->getChannel(), $live->getEvent(), $data);

		return $this->json($live, 200, [], [
    	'groups' => 'live:read', 
    	'circular_reference_limit' => 1, 
    	'circular_reference_handler' => function ($object) {
    		return $object->getId();
    	} 
    ]);
  }


  /**
   * Mettre à jour les likes
   *
   * @Route("/user/api/live/{id}/update/likes", name="user_api_live_update_likes", methods={"PUT"})
   */
  public function updateLikes(Live $live, Request $request, ObjectManager $manager, SerializerInterface $serializer) {
    $pusher = new \Pusher\Pusher('55da4c74c2db8041edd6', 'd61dc5df277d1943a6fa', '1274340', [ 'cluster' => 'eu', 'useTLS' => true ]);
    $live->setTotalLikes($live->getTotalLikes() + 1);
    $manager->flush();

    if ($live->getChannel() && $live->getEvent()) {
      $pusher->trigger($live->getChannel(), $live->getEvent(), [
        "likes" => $this->getUser()->getId()
      ]);
    }

    return $this->json($live, 200, [], [
      'groups' => 'live:read', 
      'circular_reference_limit' => 1, 
      'circular_reference_handler' => function ($object) {
        return $object->getId();
      } 
    ]);
  }


  /**
   * Muter un viewer
   *
   * @Route("/user/api/live/{id}/update/banned/{userId}", name="user_api_live_update_banned", methods={"GET"})
   */
  public function bannedViewer(Live $live, $userId, Request $request, ObjectManager $manager, FollowRepository $followRepo, SerializerInterface $serializer) {
    $pusher = new \Pusher\Pusher('55da4c74c2db8041edd6', 'd61dc5df277d1943a6fa', '1274340', [ 'cluster' => 'eu', 'useTLS' => true ]);
    $follow = $followRepo->findOneBy(['following' => $live->getVendor()->getUser(), 'follower' => $userId ]);

    if ($follow) {
      $manager->remove($follow);
      $manager->flush();
    }

    if ($live->getChannel() && $live->getEvent()) {
      $pusher->trigger($live->getChannel(), $live->getEvent(), [
        "banned" => $userId
      ]);
    }

    return $this->json($live, 200, [], [
      'groups' => 'live:read', 
      'circular_reference_limit' => 1, 
      'circular_reference_handler' => function ($object) {
        return $object->getId();
      } 
    ]);
  }


  /**
   * Mettre à jour les commandes
   *
   * @Route("/user/api/live/{id}/update/orders/{orderId}", name="user_api_live_update_orders", methods={"GET"})
   */
  public function updateOrders(Live $live, $orderId, Request $request, ObjectManager $manager, OrderRepository $orderRepo, LiveProductsRepository $liveProductRepo, SerializerInterface $serializer) {
    $order = $orderRepo->findOneById($orderId);
    $upload = null; $vendor = null; $nbProducts = 0; $available = null;
    $display = $live->getDisplay();
    $liveProduct = $liveProductRepo->findOneBy([ "live" => $live, "priority" => $display ]);


    if ($liveProduct) {
      $product = $liveProduct->getProduct();

      if ($product && sizeof($product->getVariants()->toArray()) > 0) {
        foreach ($product->getVariants() as $variant) {
          $available = $available + $variant->getQuantity();
        }
      } else {
        $available = $product->getQuantity();
      }
    }

    if ($order) {
      $pusher = new \Pusher\Pusher('55da4c74c2db8041edd6', 'd61dc5df277d1943a6fa', '1274340', [ 'cluster' => 'eu', 'useTLS' => true ]);

      if ($order->getBuyer()->getVendor()) {
        $vendor = [ "businessName" => $order->getBuyer()->getVendor()->getBusinessName() ];
      }

      if (sizeof($order->getLineItems()->toArray()[0]->getProduct()->getUploads()) > 0) {
        $upload = $order->getLineItems()->toArray()[0]->getProduct()->getUploads()[0]->getFilename();
      }

      foreach ($order->getLineItems()->toArray() as $lineItem) {
        $nbProducts = $nbProducts + $lineItem->getQuantity();
      }

      if ($order->getPromotionAmount()) {
        $amount = $order->getSubtotal() - $order->getPromotionAmount();
      } else {
        $amount = $order->getSubtotal();
      }

      $data = [
        "order" => [
          "available" => $available,
          "number" => $order->getNumber(),
          "createdAt" => $order->getCreatedAt(),
          "nbProducts" => $nbProducts,
          "amount" => $amount,
          "upload" => $upload,
          "buyer" => [
            "vendor" => $vendor,
            "firstname" => $order->getBuyer()->getFirstname(),
            "lastname" => $order->getBuyer()->getLastname(),
            "picture" => $order->getBuyer()->getPicture()
          ]
        ]
      ];
      
      $pusher->trigger($live->getChannel(), $live->getEvent(), $data);

      return $this->json($live, 200, [], [
        'groups' => 'live:read', 
        'circular_reference_limit' => 1, 
        'circular_reference_handler' => function ($object) {
          return $object->getId();
        } 
      ]);
    }

    return $this->json("Impossible de trouver la commande", 404);
  }


  function dateIntervalToSeconds($dateInterval) {
    $reference = new \DateTimeImmutable;
    $endTime = $reference->add($dateInterval);

    return $reference->getTimestamp() - $endTime->getTimestamp();
  }
}
