<?php

namespace App\Controller\App;

use App\Entity\User;
use App\Entity\Vendor;
use App\Entity\Clip;
use App\Entity\Live;
use App\Entity\Favoris;
use App\Entity\Category;
use App\Entity\Message;
use App\Entity\SecurityUser;
use App\Entity\Follow;
use App\Entity\Product;
use App\Entity\LiveProducts;
use App\Entity\Upload;
use App\Repository\FavorisRepository;
use App\Repository\FollowRepository;
use App\Repository\SecurityUserRepository;
use App\Repository\VendorRepository;
use App\Repository\UserRepository;
use App\Repository\ClipRepository;
use App\Repository\ProductRepository;
use App\Repository\LiveRepository;
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
use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Api\Admin\AdminApi;
use Cloudinary\Cloudinary;


class FavorisAPIController extends AbstractController {


  /**
   * Récupérer les favoris
   *
   * @Route("/user/api/favoris", name="user_api_favoris", methods={"GET"})
   */
  public function favoris(Request $request, ObjectManager $manager, FavorisRepository $favorisRepo) {
    $favoris = $favorisRepo->findByUser($this->getUser());

    return $this->json($favoris, 200, [], [
      'groups' => 'favoris:read', 
      'circular_reference_limit' => 1, 
      'circular_reference_handler' => function ($object) {
        return $object->getId();
      } 
    ]);
  }


  /**
   * Ajouter/Enlever des favoris
   *
   * @Route("/user/api/favoris/{id}", name="user_api_favoris_update", methods={"GET"})
   */
  public function updateFavoris(Product $product, Request $request, ObjectManager $manager, FavorisRepository $favorisRepo) {
    $favoris = $favorisRepo->findOneBy(['user' => $this->getUser(), 'product' => $product ]);

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
      'groups' => 'user:read', 
      'circular_reference_limit' => 1, 
      'circular_reference_handler' => function ($object) {
        return $object->getId();
      } 
    ]);
  }
}
