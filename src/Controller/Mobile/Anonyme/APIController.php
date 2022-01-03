<?php

namespace App\Controller\Mobile\Anonyme;

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
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Config\Definition\Exception\Exception;


class APIController extends Controller {

  /**
   * Afficher le feed
   *
   * @Route("/api/feed", name="api_feed", methods={"GET"})
   */
  public function feed(Request $request, ObjectManager $manager, ClipRepository $clipRepo, LiveRepository $liveRepo, SerializerInterface $serializer) {
    $lives = $liveRepo->findByLive();
    $array = [];

    if ($lives) {
      foreach ($lives as $live) {
        $array[] = [ "type" => "live", "value" => $serializer->serialize($live, "json", ['groups' => 'live:read']) ];
      }
    }

    if (sizeof($lives) != 10) {
      $clips = $clipRepo->findByClip(10 - sizeof($lives));
      if ($clips) {
        foreach ($clips as $clip) {
          $array[] = [ "type" => "clip", "value" => $serializer->serialize($clip, "json", ['groups' => 'clip:read']) ];
        }
      }
    }

    return $this->json($array);
  }


  /**
   * Afficher les 10 derniers clips
   *
   * @Route("/api/clips/last", name="api_clips_last", methods={"GET"})
   */
  public function lastClips(Request $request, ObjectManager $manager, ClipRepository $clipRepo)
  {
    $clips = $clipRepo->findByClip(10);

    return $this->json($clips, 200, [], ['groups' => 'clip:read']);
  }


  /**
   * Afficher les messages
   *
   * @Route("/api/live/{id}/messages", name="api_live_messages", methods={"GET"})
   */
  public function messages(Live $live, Request $request, ObjectManager $manager)
  {
    $messages = $live->getMessages();

    return $this->json($messages, 200, [], ['groups' => 'message:read']);
  }


  /**
   * Afficher le profil
   *
   * @Route("/api/profile/{id}", name="api_profile", methods={"GET"})
   */
  public function profile(Vendor $vendor, Request $request, ObjectManager $manager)
  {
    return $this->json($vendor, 200, [], ['groups' => 'vendor:read', 'circular_reference_limit' => 1, 'circular_reference_handler' => function ($object) {
        return $object->getId();
    } ]);
  }


  /**
   * Afficher les produits du vendeur
   *
   * @Route("/api/vendor/{id}/products", name="api_vendor_products", methods={"GET"})
   */
  public function products(Vendor $vendor, Request $request, ObjectManager $manager, ProductRepository $productRepo) {
    $products = $productRepo->findByVendor($vendor);

    return $this->json($products, 200, [], ['groups' => 'product:read']);
  }


  /**
   * Récupérer un produit
   *
   * @Route("/api/products/{id}", name="api_product", methods={"GET"})
   */
  public function product(Product $product, Request $request, ObjectManager $manager) {
    return $this->json($product, 200, [], ['groups' => 'product:read']);
  }


  /**
   * Afficher les catégories
   *
   * @Route("/api/categories", name="api_categories", methods={"GET"})
   */
  public function categories(Request $request, ObjectManager $manager, CategoryRepository $categoryRepo) {
    $categories = $categoryRepo->findAll();

    return $this->json($categories, 200, [], ['groups' => 'category:read']);
  }


  /**
   * Récupérer une catégorie
   *
   * @Route("/api/categories/{id}", name="api_category", methods={"GET"})
   */
  public function category(Category $category, Request $request, ObjectManager $manager) {
    return $this->json($category, 200, [], ['groups' => 'category:read']);
  }


  /**
   * Récupérer les produits dans une catégorie
   *
   * @Route("/api/categories/{id}/products", name="api_category_products", methods={"GET"})
   */
  public function productsCategory(Category $category, Request $request, ObjectManager $manager, ProductRepository $productRepo) {
    $products = $productRepo->findByCategory($category);

    return $this->json($products, 200, [], ['groups' => 'product:read']);
  }


  /**
   * Mettre à jour les vues sur un live
   *
   * @Route("/api/live/{id}/update/viewers", name="api_live_update_viewers", methods={"PUT"})
   */
  public function updateViewers(Live $live, Request $request, ObjectManager $manager, SerializerInterface $serializer) {
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
        "vendor" => null
      ]
    ];

    $pusher->trigger($live->getChannel(), $live->getEvent(), $data);

    return $this->json($live, 200, [], ['groups' => 'live:read'], 200);
  }


  /**
   * Webhooks Bambuser
   *
   * @Route("/api/bambuser/webhooks", name="api_bambuser_webhooks", methods={"POST"})
   */
  public function webhooks(Request $request, ClipRepository $clipRepo, LiveRepository $liveRepo, ObjectManager $manager) {
    $result = json_decode($request->getContent(), true);
    $this->get('bugsnag')->notifyException(new Exception($result["payload"]["id"]));

    // broadcast
    if ($result["collection"] == "broadcast") {

      // broadcast add
      if ($result["action"] == "add") {
        $broadcastId = $result["payload"]["id"];
        $clip = $clipRepo->findOneByBroadcastId($broadcastId);

        if ($clip) {
          $clip->setResourceUri($result["payload"]["resourceUri"]);
          $clip->setStatus("available");

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

        if ($live && $result["payload"]["type"] == "archived") {
          $live->setStatus(2);

          // create last clip
          $liveProduct = $liveProductRepo->findOneBy([ "live" => $live, "priority" => $live->getDisplay() ]);

          if ($liveProduct) {
            $clip = new Clip();
            $clip->setVendor($live->getVendor());
            $clip->setLive($live);
            $clip->setProduct($liveProduct->getProduct());
            $clip->setPreview($live->getPreview());

            if ($display == 1) {
              $start = 0;
            } else {
              $start = $live->getDuration() + 1;
            }

            $end = $result["payload"]["length"];

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
        }

        $clip = $clipRepo->findOneByBroadcastId($broadcastId);

        if ($clip) {
          if ($result["payload"]["preview"]) {
            $clip->setPreview($result["payload"]["preview"]);
            $manager->flush();
          }
        }
      }

      // broadcast extract
      if ($result["action"] == "extract") {
        $broadcastId = $result["payload"]["id"];
        $clip = $clipRepo->findOneByBroadcastId($broadcastId);

        if ($clip) {
          if ($result["payload"]["status"]) {
            $clip->setStatus($result["payload"]["status"]);
            $manager->flush();
          }
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


  function dateIntervalToSeconds($dateInterval) {
    $reference = new \DateTimeImmutable;
    $endTime = $reference->add($dateInterval);

    return $reference->getTimestamp() - $endTime->getTimestamp();
  }
}
