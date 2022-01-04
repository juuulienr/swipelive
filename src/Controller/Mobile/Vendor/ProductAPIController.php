<?php

namespace App\Controller\Mobile\Vendor;

use App\Entity\Vendor;
use App\Entity\Clip;
use App\Entity\Live;
use App\Entity\Category;
use App\Entity\Message;
use App\Entity\Follow;
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


class ProductAPIController extends Controller {

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
  public function product(Product $product) {
    return $this->json($product, 200, [], ['groups' => 'product:read']);
  }


  /**
   * Ajouter un produit
   *
   * @Route("/vendor/api/products/add", name="vendor_api_product_add", methods={"POST"})
   */
  public function addProduct(Request $request, ObjectManager $manager, SerializerInterface $serializer) {
    if ($json = $request->getContent()) {
      $product = $serializer->deserialize($json, Product::class, "json");
      $product->setVendor($this->getUser());

      $manager->persist($product);
      $manager->flush();

      return $this->json($product, 200, [], ['groups' => 'product:read'], 200);
    }

    return $this->json([ "error" => "Une erreur est survenue"], 404);
  }


  /**
   * Editer un produit
   *
   * @Route("/vendor/api/products/edit/{id}", name="vendor_api_product_edit", methods={"POST"})
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
   * @Route("/vendor/api/products/delete/{id}", name="vendor_api_product_delete", methods={"GET"})
   */
  public function deleteProduct(Product $product, Request $request, ObjectManager $manager) {
    if ($product) {
      $product->setArchived(true);

      // if ($product->getUploads()->toArray()) {
      //   foreach ($product->getUploads()->toArray() as $upload) {
      //     $filePath = $this->getParameter('uploads_directory') . '/' . $upload->getFilename();

      //     if (file_exists($filePath)) {
      //       $filesystem = new Filesystem();
      //       $filesystem->remove($filePath);

      //       $manager->remove($upload);
      //       $manager->flush();
      //     }
      //   }
      // }

      // $manager->remove($product);
      // $manager->flush();
      
      return $this->json(true, 200);
    }

    return $this->json([ "error" => "Le produit est introuvable"], 404);
  }


  /**
   * Ajouter une image
   *
   * @Route("/vendor/api/products/upload/add", name="vendor_api_upload_add", methods={"POST"})
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

      return $this->json($upload, 200);
    }

    return $this->json("L'image est introuvable !", 404);
  }


  /**
   * Supprimer une image
   *
   * @Route("/vendor/api/products/upload/delete/{id}", name="vendor_api_upload_delete", methods={"GET"})
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
