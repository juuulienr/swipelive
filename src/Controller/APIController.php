<?php

namespace App\Controller;

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

class APIController extends Controller {


  /**
   * @Route("/api/feed", name="api_feed", methods={"GET"})
   */
  public function feed(Request $request, ObjectManager $manager, ClipRepository $clipRepo, LiveRepository $liveRepo, SerializerInterface $serializer)
  {
    $lives = $liveRepo->findByLive();
    $clips = $clipRepo->findByClip();

    $array = [
      'clips' => $serializer->serialize($clips, "json", ['groups' => 'clip:read']),
      'lives' => $serializer->serialize($lives, "json", ['groups' => 'live:read'])
    ];
// return new JsonResponse($array, 200, [], true);

    return $this->json($array, 200);
  }


  /**
   * @Route("/api/live/{id}/messages", name="api_live_messages", methods={"GET"})
   */
  public function messages(Live $live, Request $request, ObjectManager $manager)
  {
    $messages = $live->getMessages();

    return $this->json($messages, 200, [], ['groups' => 'message:read']);
  }

  /**
   * @Route("/api/profile/{id}", name="api_profile", methods={"GET"})
   */
  public function profile(Vendor $vendor, Request $request, ObjectManager $manager)
  {
    return $this->json($vendor, 200, [], ['groups' => 'vendor:read']);
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
}
