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
use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Api\Admin\AdminApi;
use Cloudinary\Cloudinary;


class PromotionAPIController extends AbstractController {

  /**
   * @return User|null
   */
  public function getUser(): ?User
  {
      return parent::getUser();
  }

  
  /**
   * Afficher les promotions
   *
   * @Route("/user/api/promotions", name="user_api_promotions", methods={"GET"})
   */
  public function promotions(Request $request, ObjectManager $manager, PromotionRepository $promotionRepo, SerializerInterface $serializer)
  {
    $promotions = $promotionRepo->findByVendor($this->getUser()->getVendor());
    
    return $this->json($promotions, 200, [], ['groups' => 'promotion:read']);
  }


  /**
   * Récupérer la promotion active
   *
   * @Route("/user/api/promotions/active/{id}", name="user_api_promotions_active", methods={"GET"})
   */
  public function active(Product $product, Request $request, ObjectManager $manager, PromotionRepository $promotionRepo, SerializerInterface $serializer)
  {
    $promotion = $promotionRepo->findOneBy([ "vendor" => $product->getVendor(), "isActive" => true ]);

    if ($promotion) {
      return $this->json($promotion, 200, [], ['groups' => 'promotion:read']);
    } else {
      return $this->json("Aucune promotion disponible", 404);
    }
  }


  /**
   * Supprimer une promotion
   *
   * @Route("/user/api/promotion/delete/{id}", name="user_api_promotions_delete", methods={"GET"})
   */
  public function deletePromotion(Promotion $promotion, ObjectManager $manager, PromotionRepository $promotionRepo, SerializerInterface $serializer)
  {
    if ($promotion) {
      if ($promotion->getOrders()) {
        foreach ($promotion->getOrders() as $order) {
          $promotion->removeOrder($order);
        }
      }

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
}
