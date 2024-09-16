<?php

namespace App\Controller\App;

use App\Entity\Clip;
use App\Entity\Live;
use App\Entity\User;
use App\Entity\Promotion;
use App\Entity\Vendor;
use App\Entity\Message;
use App\Entity\Product;
use App\Entity\Category;
use App\Repository\ClipRepository;
use App\Repository\CategoryRepository;
use App\Repository\PromotionRepository;
use App\Repository\ProductRepository;
use App\Repository\LiveRepository;
use App\Repository\UserRepository;
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
use Symfony\Component\HttpFoundation\Response;
use BoogieFromZk\AgoraToken\RtcTokenBuilder2;
use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Api\Admin\AdminApi;
use Cloudinary\Cloudinary;


class APIController extends AbstractController {

  /**
   * @Route("/user/api/agora/token/{id}", name="generate_agora_token")
   */
  public function generateToken(Live $live, ObjectManager $manager) {
    $appID = $this->getParameter('agora_app_id');
    $appCertificate = $this->getParameter('agora_app_certificate');
    $expiresInSeconds = 86400;
    $cname = "Live" . $live->getId();
    $uid = (int) $this->getUser()->getId();
    $role = RtcTokenBuilder2::ROLE_PUBLISHER;

    $live->setCname($cname);
    $manager->flush();

    try {
      $token = RtcTokenBuilder2::buildTokenWithUid($appID, $appCertificate, $cname, $uid, $role, $expiresInSeconds);
      return $this->json([ "token" => $token ], 200);
    } catch (\Exception $e) {
      return $this->json('Failed to generate token', 500);
    }
  }


  /**
   * @Route("/user/api/agora/token/audience/{id}", name="generate_agora_token_audience")
   */
  public function generateAudienceToken(Live $live) {
    $appID = $this->getParameter('agora_app_id');
    $appCertificate = $this->getParameter('agora_app_certificate');
    $expiresInSeconds = 86400; 
    $cname = "Live" . $live->getId();
    $role = RtcTokenBuilder2::ROLE_SUBSCRIBER;
    $uid = (int) $this->getUser()->getId();

    try {
      $token = RtcTokenBuilder2::buildTokenWithUid($appID, $appCertificate, $cname, $uid, $role, $expiresInSeconds);
      return $this->json([ "token" => $token ], 200);
    } catch (\Exception $e) {
      return $this->json('Failed to generate token', 500);
    }
  }


  /**
   * @Route("/agora/token/record/{id}", name="generate_agora_token_record")
   */
  public function generateRecordToken(Live $live) {
    $appID = $this->getParameter('agora_app_id');
    $appCertificate = $this->getParameter('agora_app_certificate');
    $expiresInSeconds = 86400; // Expire dans 24 heures
    $role = RtcTokenBuilder2::ROLE_SUBSCRIBER;

    try {
      $token = RtcTokenBuilder2::buildTokenWithUid($appID, $appCertificate, $live->getCname(), 123456789, $role, $expiresInSeconds);
      return $this->json([ "token" => $token ], 200);
    } catch (\Exception $e) {
      return $this->json('Failed to generate token', 500);
    }
  }


  /**
   * Afficher le feed
   *
   * @Route("/user/api/feed", name="api_feed", methods={"GET"})
   */
  public function feed(Request $request, ObjectManager $manager, ClipRepository $clipRepo, LiveRepository $liveRepo, SerializerInterface $serializer) {
    $vendor = $this->getUser()->getVendor();
    $lives = $liveRepo->findByLive($vendor);
    $clips = $clipRepo->findByClip($vendor);
    $array = [];

    if ($lives) {
    	foreach ($lives as $live) {
    		$array[] = [ "type" => "live", "value" => $serializer->serialize($live, "json", [
    			'groups' => 'live:read', 
    			'circular_reference_limit' => 1, 
    			'circular_reference_handler' => function ($object) {
    				return $object->getId();
    			} 
    		])];
    	}
    }

    if ($clips) {
    	foreach ($clips as $clip) {
    		$array[] = [ "type" => "clip", "value" => $serializer->serialize($clip, "json", [
    			'groups' => 'clip:read', 
    			'circular_reference_limit' => 1, 
    			'circular_reference_handler' => function ($object) {
    				return $object->getId();
    			} 
    		])];
    	} 
    }

    return $this->json($array);
  }



  /**
   * Afficher clips tendances
   *
   * @Route("/user/api/clips/trending", name="api_clips_trending", methods={"GET"})
   */
  public function clipsTrending(Request $request, ObjectManager $manager, ClipRepository $clipRepo, ProductRepository $productRepo, CategoryRepository $categoryRepo, SerializerInterface $serializer)
  {
    $clips = $clipRepo->findTrendingClips($this->getUser()->getVendor());

    return $this->json($clips, 200, [], [
      'groups' => 'clip:read', 
      'circular_reference_limit' => 1, 
      'circular_reference_handler' => function ($object) {
        return $object->getId();
      } 
    ]);
  }



  /**
   * Afficher les nouveaux clips
   *
   * @Route("/user/api/clips/latest", name="api_clips_latest", methods={"GET"})
   */
  public function clipsLatest(Request $request, ObjectManager $manager, ClipRepository $clipRepo, SerializerInterface $serializer)
  {
    $clips = $clipRepo->findLatestClips($this->getUser()->getVendor());

    return $this->json($clips, 200, [], [
      'groups' => 'clip:read', 
      'circular_reference_limit' => 1, 
      'circular_reference_handler' => function ($object) {
        return $object->getId();
      } 
    ]);
  }



  /**
   * Afficher les produits tendance
   *
   * @Route("/user/api/products/trending", name="api_products_trending", methods={"GET"})
   */
  public function productsTrending(Request $request, ObjectManager $manager, ProductRepository $productRepo, SerializerInterface $serializer)
  {
    $products = $productRepo->findTrendingProducts($this->getUser()->getVendor());

    return $this->json($products, 200, [], [
      'groups' => 'product:read', 
      'circular_reference_limit' => 1, 
      'circular_reference_handler' => function ($object) {
        return $object->getId();
      } 
    ]);
  }



  /**
   * Afficher les produits 
   *
   * @Route("/user/api/products/all", name="api_products_all", methods={"GET"})
   */
  public function allProducts(Request $request, ObjectManager $manager, ProductRepository $productRepo)
  {
    $products = $productRepo->findProductsNotCreatedByVendor($this->getUser()->getVendor());

    return $this->json($products, 200, [], [
      'groups' => 'product:read', 
      'circular_reference_limit' => 1, 
      'circular_reference_handler' => function ($object) {
        return $object->getId();
      } 
    ]);
  }



  /**
   * Afficher les produits d'un vendeur
   *
   * @Route("/user/api/shop/{id}", name="api_shop_vendor", methods={"GET"})
   */
  public function shopVendor(Vendor $vendor, Request $request, ObjectManager $manager, ProductRepository $productRepo)
  {
    $products = $productRepo->findByVendor($vendor);

    return $this->json($products, 200, [], [
      'groups' => 'product:read', 
      'circular_reference_limit' => 1, 
      'circular_reference_handler' => function ($object) {
        return $object->getId();
      } 
    ]);
  }



  /**
   * Afficher le profil
   *
   * @Route("/api/profile/{id}", name="api_profile", methods={"GET"})
   */
  public function profile(User $user, Request $request, ObjectManager $manager)
  {
    return $this->json($user, 200, [], [
    	'groups' => 'user:read', 
    	'circular_reference_limit' => 1, 
    	'circular_reference_handler' => function ($object) {
    		return $object->getId();
    	} 
    ]);
  }


  /**
   * Récupérer les clips d'un profil
   *
   * @Route("/api/profile/{id}/clips", name="api_profile_clips", methods={"GET"})
   */
  public function profileClips(User $user, Request $request, ObjectManager $manager, ClipRepository $clipRepo, SerializerInterface $serializer) {
    $clips = $clipRepo->retrieveClips($user->getVendor());
    $array = [];

    if ($clips) {
      foreach ($clips as $clip) {
        $array[] = [ "type" => "clip", "value" => $serializer->serialize($clip, "json", [
          'groups' => 'clip:read', 
          'circular_reference_limit' => 1, 
          'circular_reference_handler' => function ($object) {
            return $object->getId();
          } 
        ])];
      } 
    }

    return $this->json($array);
  }



  /**
   * Récupérer les produits d'un profil
   *
   * @Route("/api/profile/{id}/products", name="api_profile_shop", methods={"GET"})
   */
  public function profileProducts(User $user, Request $request, ObjectManager $manager, ProductRepository $productRepo)
  {
    $products = $productRepo->findByVendor($user->getVendor());

    return $this->json($products, 200, [], [
      'groups' => 'product:read', 
      'circular_reference_limit' => 1, 
      'circular_reference_handler' => function ($object) {
        return $object->getId();
      } 
    ]);
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
   * Modifier image du profil
   *
   * @Route("/api/registration/picture", name="api_registration_picture")
   */
  public function registrationPicture(Request $request, ObjectManager $manager, SerializerInterface $serializer) {
    $file = json_decode($request->getContent(), true);

    if ($file && array_key_exists("picture", $file)) {
      $file = $file["picture"];
      $content = $file;
      $extension = 'jpg';
    } else if ($request->files->get('picture')) {
      $file = $request->files->get('picture');
      $content = file_get_contents($file);
      $extension = $file->guessExtension();
    } else {
      return $this->json("L'image est introuvable !", 404);
    }

    $filename = md5(time().uniqid()); 
    $fullname = $filename . "." . $extension; 
    $file->move($this->getParameter('uploads_directory'), $fullname);
    $file = $this->getParameter('uploads_directory') . '/' . $fullname;

    try {
      Configuration::instance($this->getParameter('cloudinary'));
      $result = (new UploadApi())->upload($file, [
        'public_id' => $filename,
        'use_filename' => TRUE,
        "height" => 256, 
        "width" => 256, 
        "crop" => "thumb"
      ]);

    } catch (\Exception $e) {
      return $this->json($e->getMessage(), 404);
    }

    return $this->json($fullname, 200);
  }


  /**
   * Rechercher un vendeur
   *
   * @Route("/user/api/user/search", name="api_user_search")
   */
  public function search(Request $request, UserRepository $repo, ObjectManager $manager)
  {
    $search = $request->query->get('search');
    $users = $repo->findUserBySearch($search, $this->getUser()->getVendor());

    return $this->json($users, 200, [], [
    	'groups' => 'user:follow', 
    	'circular_reference_limit' => 1, 
    	'circular_reference_handler' => function ($object) {
    		return $object->getId();
    	} 
    ]);
  }
}
