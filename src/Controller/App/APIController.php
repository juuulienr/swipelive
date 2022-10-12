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


class APIController extends Controller {

  /**
   * Afficher le feed
   *
   * @Route("/api/feed", name="api_feed", methods={"GET"})
   */
  public function feed(Request $request, ObjectManager $manager, ClipRepository $clipRepo, LiveRepository $liveRepo, SerializerInterface $serializer) {
    $lives = $liveRepo->findByLive();
    $clips = $clipRepo->findByClip();
    $array = [];

    if ($lives) {
      foreach ($lives as $live) {
        $array[] = [ "type" => "live", "value" => $serializer->serialize($live, "json", ['groups' => 'live:read']) ];
      }
    }

    if ($clips) {
      foreach ($clips as $clip) {
        $array[] = [ "type" => "clip", "value" => $serializer->serialize($clip, "json", ['groups' => 'clip:read']) ];
      }
    }

    return $this->json($array);
  }


  /**
   * Afficher 10 clips tendances
   *
   * @Route("/api/clips/trending", name="api_clips_trending", methods={"GET"})
   */
  public function trending(Request $request, ObjectManager $manager, ClipRepository $clipRepo)
  {
    $clips = $clipRepo->findBy([ "status" => "available"], [ "createdAt" => "DESC" ]);

    return $this->json($clips, 200, [], ['groups' => 'clip:read']);
  }


  /**
   * Afficher le profil
   *
   * @Route("/api/profile/{id}", name="api_profile", methods={"GET"})
   */
  public function profile(User $user, Request $request, ObjectManager $manager)
  {
    return $this->json($user, 200, [], ['groups' => 'user:read', 'circular_reference_limit' => 1, 'circular_reference_handler' => function ($object) {
        return $object->getId();
    } ]);
  }


  /**
   * Récupérer les clips d'un profil
   *
   * @Route("/api/profile/{id}/clips", name="api_profile_clips", methods={"GET"})
   */
  public function profileClips(User $user, Request $request, ObjectManager $manager, ClipRepository $clipRepo) {
    $clips = $clipRepo->retrieveClips($user);

    return $this->json($clips, 200, [], ['groups' => 'clip:read']);
  }


  /**
   * Afficher les produits du vendeur
   *
   * @Route("/api/profile/{id}/products", name="api_profile_products", methods={"GET"})
   */
  public function products(User $user, Request $request, ObjectManager $manager, ProductRepository $productRepo) {
    $products = $productRepo->findBy([ "user" => $user, "archived" => false ]);

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
    $products = $productRepo->findBy([ "category" => $category, "archived" => false ]);

    return $this->json($products, 200, [], ['groups' => 'product:read']);
  }


  /**
   * Mettre à jour les vues sur un live
   *
   * @Route("/api/live/{id}/update/viewers", name="api_live_update_viewers", methods={"PUT"})
   */
  public function updateViewers(Live $live, Request $request, ObjectManager $manager, SerializerInterface $serializer) {
    $pusher = new \Pusher\Pusher('55da4c74c2db8041edd6', 'd61dc5df277d1943a6fa', '1274340', [ 'cluster' => 'eu', 'useTLS' => true ]);
    $info = $pusher->getChannelInfo($live->getChannel(), ['info' => 'subscription_count']);
    $count = $info->subscription_count;

    if ($count) {
      $count = $count - 1;
      $live->setViewers($count);
      $manager->flush();
    }

    $data = [ 
      "viewers" => $count,
    ];

    $pusher->trigger($live->getChannel(), $live->getEvent(), $data);

    return $this->json($live, 200, [], ['groups' => 'live:read'], 200);
  }


  /**
   * Modifier image du profil
   *
   * @Route("/api/registration/picture", name="api_registration_picture")
   */
  public function registrationPicture(Request $request, ObjectManager $manager, SerializerInterface $serializer) {
  	$this->get('bugsnag')->notifyException(new Exception($request));
    if ($request->files->get('picture')) {
      $file = $request->files->get('picture');

      if (!$file) {
        return $this->json("L'image est introuvable !", 404);
      }

      $filename = md5(time().uniqid()). "." . $file->guessExtension(); 
      $filepath = $this->getParameter('uploads_directory') . '/' . $filename;
      file_put_contents($filepath, file_get_contents($file));

      return $this->json($filename, 200);
    }

    return $this->json("L'image est introuvable !", 404);
  }
}
