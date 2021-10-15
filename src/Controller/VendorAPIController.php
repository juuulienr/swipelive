<?php

namespace App\Controller;

use App\Entity\Vendor;
use App\Entity\Clip;
use App\Entity\Live;
use App\Entity\Category;
use App\Entity\Product;
use App\Repository\VendorRepository;
use App\Repository\ClipRepository;
use App\Repository\ProductRepository;
use App\Repository\LiveRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;


class VendorAPIController extends Controller {


  /**
   * Inscription vendeur
   *
  * @Route("/api/vendor/register", name="vendor_api_register")
  */
  public function register(Request $request, ObjectManager $manager, VendorRepository $vendorRepo , UserPasswordEncoderInterface $encoder, SerializerInterface $serializer) {

    if ($json = $request->getContent()) {
      $param = json_decode($json, true);

      if ($param) {
        $vendor = $vendorRepo->findOneByEmail($param['email']);

        if (!$vendor) {
          $vendor = $serializer->deserialize($json, Vendor::class, "json");
          $hash = $encoder->encodePassword($vendor, $param['password']);
          $vendor->setHash($hash);

          $manager->persist($vendor);
          $manager->flush();

          return $this->json($vendor, 200);

        } else {
          return $this->json("Un compte est associé à cette adresse mail", 404);
        }
      }
    }

    return $this->json([ "error" => "Une erreur est survenue"], 404);
  }


  /**
   * Ajouter le push token
   *
   * @Route("/vendor/api/push/add", name="vendor_push_add")
   */
  public function addPush(Request $request, ObjectManager $manager)
  {
    $vendor = $this->getUser(); $token = [];

    // récupérer le push token
    if ($content = $request->getContent()) {
      $result = json_decode($content, true);
      if ($result) {
        $vendor->setPushToken($result['pushToken']);
        $manager->flush();

        return $this->json(true, 200);
      }
    }

    return $this->json("Le token est introuvable", 404);
  }


  /**
   * Récupérer le profil
   *
   * @Route("/vendor/api/profile", name="vendor_api_profile", methods={"GET"})
   */
  public function profile(Request $request, ObjectManager $manager) {
    return $this->json($this->getUser(), 200, [], ['groups' => 'vendor:edit']);
  }


  /**
   * Edition du profil
   *
  * @Route("/vendor/api/profile/edit", name="vendor_api_profile_edit")
  */
  public function editProfile(Request $request, ObjectManager $manager, VendorRepository $vendorRepo , UserPasswordEncoderInterface $encoder, SerializerInterface $serializer) {

    if ($json = $request->getContent()) {
      $serializer->deserialize($json, Vendor::class, "json", [AbstractNormalizer::OBJECT_TO_POPULATE => $this->getUser()]);
      $manager->flush();

      return $this->json($this->getUser(), 200, [], ['groups' => 'vendor:edit'], 200);
    }

    return $this->json([ "error" => "Une erreur est survenue"], 404);
  }


  /**
   * Récupérer les clips
   *
   * @Route("/vendor/api/clips", name="vendor_api_clips", methods={"GET"})
   */
  public function clips(Request $request, ObjectManager $manager, ClipRepository $clipRepo) {
    $clips = $clipRepo->findByVendor($this->getUser());

    return $this->json($clips, 200, [], ['groups' => 'clip:read']);
  }


  /**
   * Récupérer les produits
   *
   * @Route("/vendor/api/products", name="vendor_api_products", methods={"GET"})
   */
  public function products(Request $request, ObjectManager $manager, ProductRepository $productRepo) {
    $products = $productRepo->findByVendor($this->getUser());

    return $this->json($products, 200, [], ['groups' => 'product:read']);
  }


  /**
   * Récupérer un produit
   *
   * @Route("/vendor/api/products/{id}", name="vendor_api_product", methods={"GET"})
   */
  public function product(Product $product, Request $request, ObjectManager $manager, ProductRepository $productRepo) {
    return $this->json($product, 200, [], ['groups' => 'product:read']);
  }

}
