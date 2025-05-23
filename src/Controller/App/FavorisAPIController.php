<?php

declare(strict_types=1);

namespace App\Controller\App;

use App\Entity\Favoris;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\FavorisRepository;
use Doctrine\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class FavorisAPIController extends AbstractController
{
  public function getUser(): ?User
  {
    return parent::getUser();
  }

  /**
   * Récupérer les favoris
   *
   * @Route("/user/api/favoris", name="user_api_favoris", methods={"GET"})
   */
  public function favoris(Request $request, ObjectManager $manager, FavorisRepository $favorisRepo): JsonResponse
  {
    $favoris = $favorisRepo->findByUser($this->getUser());

    return $this->json($favoris, 200, [], [
      'groups'                     => 'favoris:read',
      'circular_reference_limit'   => 1,
      'circular_reference_handler' => fn ($object) => $object->getId(),
    ]);
  }

  /**
   * Ajouter/Enlever des favoris
   *
   * @Route("/user/api/favoris/{id}", name="user_api_favoris_update", methods={"GET"})
   */
  public function updateFavoris(Product $product, Request $request, ObjectManager $manager, FavorisRepository $favorisRepo): JsonResponse
  {
    $favoris = $favorisRepo->findOneBy(['user' => $this->getUser(), 'product' => $product]);

    if (!$favoris) {
      $favoris = new Favoris();
      $favoris->setUser($this->getUser());
      $favoris->setProduct($product);
      $manager->persist($favoris);
    } else {
      $manager->remove($favoris);
    }

    $manager->flush();

    return $this->json($this->getUser(), 200, [], [
      'groups'                     => 'user:read',
      'circular_reference_limit'   => 1,
      'circular_reference_handler' => fn ($object) => $object->getId(),
    ]);
  }
}
