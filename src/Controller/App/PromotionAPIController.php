<?php

declare(strict_types=1);

namespace App\Controller\App;

use App\Entity\Product;
use App\Entity\Promotion;
use App\Entity\User;
use App\Repository\PromotionRepository;
use Doctrine\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class PromotionAPIController extends AbstractController
{
  public function getUser(): ?User
  {
    $user = parent::getUser();
    return $user instanceof User ? $user : null;
  }

  /**
   * Afficher les promotions
   *
   * @Route("/user/api/promotions", name="user_api_promotions", methods={"GET"})
   */
  public function promotions(Request $request, ObjectManager $manager, PromotionRepository $promotionRepo, SerializerInterface $serializer): JsonResponse
  {
    $promotions = $promotionRepo->findByVendor($this->getUser()->getVendor());

    return $this->json($promotions, 200, [], ['groups' => 'promotion:read']);
  }

  /**
   * Récupérer la promotion active
   *
   * @Route("/user/api/promotions/active/{id}", name="user_api_promotions_active", methods={"GET"})
   */
  public function active(Product $product, Request $request, ObjectManager $manager, PromotionRepository $promotionRepo, SerializerInterface $serializer): JsonResponse
  {
    $promotion = $promotionRepo->findOneBy(['vendor' => $product->getVendor(), 'isActive' => true]);

    if ($promotion) {
      return $this->json($promotion, 200, [], ['groups' => 'promotion:read']);
    }

    return $this->json('Aucune promotion disponible', 404);
  }

  /**
   * Supprimer une promotion
   *
   * @Route("/user/api/promotion/delete/{id}", name="user_api_promotions_delete", methods={"GET"})
   */
  public function deletePromotion(Promotion $promotion, ObjectManager $manager, PromotionRepository $promotionRepo, SerializerInterface $serializer): JsonResponse
  {
    if ($promotion->getOrders()->count() > 0) {
      foreach ($promotion->getOrders() as $order) {
        $promotion->removeOrder($order);
      }
    }

    $manager->remove($promotion);
    $manager->flush();

    return $this->json($this->getUser(), 200, [], [
      'groups'                     => 'user:read',
      'circular_reference_limit'   => 1,
      'circular_reference_handler' => fn ($object) => $object->getId(),
    ]);
  }

  /**
   * Ajouter une promotion
   *
   * @Route("/user/api/promotion/add", name="user_api_promotions_add", methods={"POST"})
   */
  public function addPromotion(Request $request, ObjectManager $manager, SerializerInterface $serializer, PromotionRepository $promotionRepo): JsonResponse
  {
    if ($json = $request->getContent()) {
      $vendor     = $this->getUser()->getVendor();
      $promotions = $promotionRepo->findByVendor($vendor);
      $promotion  = $serializer->deserialize($json, Promotion::class, 'json');
      $exist      = $promotionRepo->findOneBy(['title' => $promotion->getTitle(), 'vendor' => $vendor]);

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
        'groups'                     => 'user:read',
        'circular_reference_limit'   => 1,
        'circular_reference_handler' => fn ($object) => $object->getId(),
      ]);
    }

    return $this->json('Une erreur est survenue', 404);
  }

  /**
   * Activer/desactiver une promotion
   *
   * @Route("/user/api/promotion/activate/{id}", name="user_api_promotions_activate", methods={"GET"})
   */
  public function activate(Promotion $promotion, Request $request, ObjectManager $manager, PromotionRepository $promotionRepo): JsonResponse
  {
    $promotions = $promotionRepo->findByVendor($this->getUser()->getVendor());

    if (true === $promotion->getIsActive()) {
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
      'groups'                     => 'user:read',
      'circular_reference_limit'   => 1,
      'circular_reference_handler' => fn ($object) => $object->getId(),
    ]);
  }
}
