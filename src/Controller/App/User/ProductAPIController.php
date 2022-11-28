<?php

namespace App\Controller\App\User;

use App\Entity\Vendor;
use App\Entity\Clip;
use App\Entity\Live;
use App\Entity\Category;
use App\Entity\Message;
use App\Entity\Follow;
use App\Entity\Variant;
use App\Entity\Product;
use App\Entity\LiveProducts;
use App\Entity\Upload;
use App\Repository\FollowRepository;
use App\Repository\VendorRepository;
use App\Repository\ClipRepository;
use App\Repository\ProductRepository;
use App\Repository\LiveRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Api\Admin\AdminApi;
use Cloudinary\Cloudinary;


class ProductAPIController extends Controller {

  /**
   * Récupérer les produits
   *
   * @Route("/user/api/products", name="user_api_products", methods={"GET"})
   */
  public function products(Request $request, ObjectManager $manager, ProductRepository $productRepo) {
    $products = $productRepo->findBy([ "vendor" => $this->getUser()->getVendor(), "archived" => false ], [ "title" => "ASC" ]);

    return $this->json($products, 200, [], ['groups' => 'product:read']);
  }


  /**
   * Récupérer un produit
   *
   * @Route("/user/api/products/{id}", name="user_api_product", methods={"GET"})
   */
  public function product(Product $product) {
    return $this->json($product, 200, [], ['groups' => 'product:read']);
  }


  /**
   * Ajouter un produit
   *
   * @Route("/user/api/products/add", name="user_api_product_add", methods={"POST"})
   */
  public function addProduct(Request $request, ObjectManager $manager, SerializerInterface $serializer) {
    if ($json = $request->getContent()) {
      $product = $serializer->deserialize($json, Product::class, "json");
      $product->setVendor($this->getUser()->getVendor());

      foreach ($product->getUploads() as $key => $upload) {
        $upload->setPosition($key + 1);
      }

      $manager->persist($product);
      $manager->flush();

      return $this->json($product, 200, [], ['groups' => 'product:read'], 200);
    }

    return $this->json([ "error" => "Une erreur est survenue"], 404);
  }


  /**
   * Editer un produit
   *
   * @Route("/user/api/products/edit/{id}", name="user_api_product_edit", methods={"POST"})
   */
  public function editProduct(Product $product, Request $request, ObjectManager $manager, SerializerInterface $serializer) {
    if ($json = $request->getContent()) {
      $serializer->deserialize($json, Product::class, "json", [AbstractNormalizer::OBJECT_TO_POPULATE => $product]);
      $manager->flush();

      return $this->json($product, 200, [], ['groups' => 'product:read'], 200);
    }

    return $this->json([ "error" => "Une erreur est survenue"], 404);
  }


  /**
   * Supprimer un produit
   *
   * @Route("/user/api/products/delete/{id}", name="user_api_product_delete", methods={"GET"})
   */
  public function deleteProduct(Product $product, Request $request, ObjectManager $manager) {
    if ($product) {
      $product->setArchived(true);
      $manager->flush();
      
      return $this->json(true, 200);
    }

    return $this->json([ "error" => "Le produit est introuvable"], 404);
  }


  /**
   * Editer un variant
   *
   * @Route("/user/api/variant/edit/{id}", name="user_api_variant_edit", methods={"POST"})
   */
  public function editVariant(Variant $variant, Request $request, ObjectManager $manager, SerializerInterface $serializer) {
    if ($json = $request->getContent()) {
      $serializer->deserialize($json, Variant::class, "json", [AbstractNormalizer::OBJECT_TO_POPULATE => $variant]);
      $manager->flush();

      return $this->json($variant, 200, [], ['groups' => 'variant:read'], 200);
    }

    return $this->json([ "error" => "Une erreur est survenue"], 404);
  }


  /**
   * Supprimer un variant
   *
   * @Route("/user/api/variant/delete/{id}", name="user_api_variant_delete", methods={"GET"})
   */
  public function deleteVariant(Variant $variant, Request $request, ObjectManager $manager) {
    if ($variant) {
      $variant->setProduct(null);
      $manager->flush();
      
      return $this->json(true, 200);
    }

    return $this->json([ "error" => "Le variant est introuvable"], 404);
  }


  /**
   * Ajouter une image
   *
   * @Route("/user/api/products/upload/add", name="user_api_upload_add", methods={"POST"})
   */
  public function addUpload(Request $request, ObjectManager $manager, SerializerInterface $serializer) {
    if ($request->files->get('picture')) {
      $file = $request->files->get('picture');

      if (!$file) {
        return $this->json("L'image est introuvable !", 404);
      }

      $filename = md5(time().uniqid()). "." . $file->guessExtension(); 
      $filepath = $this->getParameter('uploads_directory') . '/' . $filename;
      file_put_contents($filepath, file_get_contents($file));

      $upload = new Upload();
      $upload->setFilename($filename);

      $manager->persist($upload);
      $manager->flush();

      // \Cloudinary::config([ 
      // 	"cloud_name" => "dxlsenc2r", 
      // 	"api_key" => "461186889242285", 
      // 	"api_secret" => "ZUiL6ovY92-do6u1Rr0-pcQqCMg", 
      // 	"secure" => true]);

      return $this->json($upload, 200);
    }

    return $this->json("L'image est introuvable !", 404);
  }


  /**
   * Ajouter une image sur un produit
   *
   * @Route("/user/api/products/edit/upload/add/{id}", name="user_api_edit_upload_add", methods={"POST"})
   */
  public function addUploadProduct(Product $product, Request $request, ObjectManager $manager, SerializerInterface $serializer) {
    if ($request->files->get('picture')) {
      $file = $request->files->get('picture');

      if (!$file) {
        return $this->json("L'image est introuvable !", 404);
      }

      $filename = md5(time().uniqid()). "." . $file->guessExtension(); 
      $filepath = $this->getParameter('uploads_directory') . '/' . $filename;
      file_put_contents($filepath, file_get_contents($file));

      $upload = new Upload();
      $upload->setFilename($filename);
      $product->setUpload($upload);

      $manager->persist($upload);
      $manager->flush();

      return $this->json($upload, 200);
    }

    return $this->json("L'image est introuvable !", 404);
  }


  /**
   * Supprimer une image
   *
   * @Route("/user/api/products/upload/delete/{id}", name="user_api_upload_delete", methods={"GET"})
   */
  public function deleteUpload(Upload $upload, Request $request, ObjectManager $manager) {
    $filePath = $this->getParameter('uploads_directory') . '/' . $upload->getFilename();

    if (file_exists($filePath)) {
      $filesystem = new Filesystem();
      $filesystem->remove($filePath);

      $manager->remove($upload);
      $manager->flush();

      return $this->json(true, 200);
    }

    return $this->json("L'image n'existe pas !", 404);
  }

}
