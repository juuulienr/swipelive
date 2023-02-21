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
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Api\Admin\AdminApi;
use Cloudinary\Cloudinary;


class APIController extends Controller {

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

    \Sentry\configureScope(function (\Sentry\State\Scope $scope): void {
        $scope->setUser(['email' => $this->getUser()->getEmail() ]);
    });

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
  public function trending(Request $request, ObjectManager $manager, ClipRepository $clipRepo, SerializerInterface $serializer)
  {
    $vendor = $this->getUser()->getVendor();
    $clips = $clipRepo->findTrendingClips($vendor);

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
  public function latest(Request $request, ObjectManager $manager, ClipRepository $clipRepo, SerializerInterface $serializer)
  {
    $vendor = $this->getUser()->getVendor();
    $clips = $clipRepo->findLatestClips($vendor);

    return $this->json($clips, 200, [], [
      'groups' => 'clip:read', 
      'circular_reference_limit' => 1, 
      'circular_reference_handler' => function ($object) {
        return $object->getId();
      } 
    ]);
  }


  /**
   * Afficher les promotions
   *
   * @Route("/user/api/promotions", name="user_api_promotions", methods={"GET"})
   */
  public function promotions(Request $request, ObjectManager $manager, PromotionRepository $promotionRepo, SerializerInterface $serializer)
  {
    $promotions = $promotionRepo->findByVendor($this->getUser()->getVendor());
    
    return $this->json($promotions, 200, [], ['groups' => 'promotion:read'], 200);
  }


  /**
   * Supprimer une promotion
   *
   * @Route("/user/api/promotion/delete/{id}", name="user_api_promotions_delete", methods={"GET"})
   */
  public function deletePromotion(Promotion $promotion, ObjectManager $manager, PromotionRepository $promotionRepo, SerializerInterface $serializer)
  {
    if ($promotion) {
      $manager->remove($promotion);
      $manager->flush();
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
   * Ajouter une promotion
   *
   * @Route("/user/api/promotion/add", name="user_api_promotions_add", methods={"POST"})
   */
  public function addPromotion(Request $request, ObjectManager $manager, SerializerInterface $serializer, PromotionRepository $promotionRepo) {
    if ($json = $request->getContent()) {
      $vendor = $this->getUser()->getVendor();
      $promotions = $promotionRepo->findByVendor($vendor);
      $promotion = $serializer->deserialize($json, Promotion::class, "json");
      $exist = $promotionRepo->findOneBy([ "title" => $promotion->getTitle(), "vendor" => $vendor ]);

      if ($exist) {
        return $this->json($exist, 404);
      }

      $promotion->setVendor($vendor);
      $manager->persist($promotion);
      $manager->flush();

      foreach ($promotions as $promo) {
        $promo->setIsActive(false);
        $manager->flush();
      }

      return $this->json($this->getUser(), 200, [], [
        'groups' => 'user:read', 
        'circular_reference_limit' => 1, 
        'circular_reference_handler' => function ($object) {
          return $object->getId();
        } 
      ]);
    }

    return $this->json("Une erreur est survenue", 404);
  }


  /**
   * Activer/desactiver une promotion
   *
   * @Route("/user/api/promotion/activate/{id}", name="user_api_promotions_activate", methods={"GET"})
   */
  public function activate(Promotion $promotion, Request $request, ObjectManager $manager, PromotionRepository $promotionRepo) {
    $promotions = $promotionRepo->findByVendor($this->getUser()->getVendor());

    if ($promotion->getIsActive() == true) {
      $promotion->setIsActive(false);
      $manager->flush();
    } else {
      if ($promotions) {
        foreach ($promotions as $promo) {
          $promo->setIsActive(false);
          $manager->flush();
        }
      }
      $promotion->setIsActive(true);
      $manager->flush();
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
   * Afficher clips tendances feed
   *
   * @Route("/user/api/clips/trending/feed", name="api_clips_trending_feed", methods={"GET"})
   */
  public function trendingFeed(Request $request, ObjectManager $manager, ClipRepository $clipRepo, SerializerInterface $serializer)
  {
    $vendor = $this->getUser()->getVendor();
    $clips = $clipRepo->findTrendingClips($vendor);
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
   * Afficher les nouveaux clips dans le feed
   *
   * @Route("/user/api/clips/latest/feed", name="api_clips_latest_feed", methods={"GET"})
   */
  public function latestFeed(Request $request, ObjectManager $manager, ClipRepository $clipRepo, SerializerInterface $serializer)
  {
    $vendor = $this->getUser()->getVendor();
    $clips = $clipRepo->findLatestClips($vendor);
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
   * Afficher les produits 
   *
   * @Route("/api/products/all", name="api_products_all", methods={"GET"})
   */
  public function allProducts(Request $request, ObjectManager $manager, ProductRepository $productRepo)
  {
    $products = $productRepo->findBy([ "archived" => false ]);

    return $this->json($products, 200, [], [
      'groups' => 'product:read', 
      'circular_reference_limit' => 1, 
      'circular_reference_handler' => function ($object) {
        return $object->getId();
      } 
    ]);
  }


  /**
   * Afficher produits tendances
   *
   * @Route("/user/api/products/trending", name="api_products_trending", methods={"GET"})
   */
  public function productTrending(Request $request, ObjectManager $manager, ProductRepository $productRepo)
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
    $filepath = $this->getParameter('uploads_directory') . '/' . $fullname;
    file_put_contents($filepath, $content);

    try {
      $result = (new UploadApi())->upload($filepath, [
        'public_id' => $filename,
        'use_filename' => TRUE,
        "height" => 256, 
        "width" => 256, 
        "crop" => "thumb"
      ]);

      unlink($filepath);
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
    	'groups' => 'user:read', 
    	'circular_reference_limit' => 1, 
    	'circular_reference_handler' => function ($object) {
    		return $object->getId();
    	} 
    ]);
  }
}
