<?php

namespace App\Controller\App;

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
use App\Repository\LiveProductsRepository;
use App\Repository\LineItemRepository;
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
    $products = $productRepo->findBy([ "vendor" => $this->getUser()->getVendor() ], [ "title" => "ASC" ]);

    return $this->json($products, 200, [], ['groups' => 'product:read']);
  }


  /**
   * Récupérer un produit
   *
   * @Route("/user/api/product/{id}", name="user_api_product", methods={"GET"})
   */
  public function product(Product $product) {
    return $this->json($product, 200, [], ['groups' => 'product:read']);
  }


  /**
   * Ajouter un produit
   *
   * @Route("/user/api/product/add", name="user_api_product_add", methods={"POST"})
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
   * Editer un produit
   *
   * @Route("/user/api/product/edit/{id}", name="user_api_product_edit", methods={"PUT"})
   */
  public function editProduct(Product $product, Request $request, ObjectManager $manager, SerializerInterface $serializer) {
    if ($json = $request->getContent()) {
      $serializer->deserialize($json, Product::class, "json", [AbstractNormalizer::OBJECT_TO_POPULATE => $product]);

      foreach ($product->getUploads() as $key => $upload) {
        $upload->setPosition($key + 1);
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

    return $this->json("Une erreur est survenue", 404);
  }


  /**
   * Supprimer un produit
   *
   * @Route("/user/api/product/delete/{id}", name="user_api_product_delete", methods={"GET"})
   */
  public function deleteProduct(Product $product, Request $request, LiveProductsRepository $liveProductRepo, ClipRepository $clipRepo, LineItemRepository $lineItemRepo, ObjectManager $manager) {
    if ($product) {
      $clips = $clipRepo->findByProduct($product);
      $env = $this->getParameter('environment');

      if ($clips) {
        foreach ($clips as $clip) {
          $live = $clip->getLive();
          $comments = $clip->getComments();

          if ($comments) {
            foreach ($comments as $comment) {
              $manager->remove($comment);
            }
            $manager->flush();
          }


          if ($env == "prod") {
            $url = "https://api.bambuser.com/broadcasts/" . $clip->getBroadcastId();
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "Accept: application/vnd.bambuser.v1+json", "Authorization: Bearer RkbHZdUPzA8Rcu2w4b1jn9"]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($ch, CURLOPT_URL, $url);

            $result = curl_exec($ch);
            $result = json_decode($result);
            curl_close($ch);
          }

          $manager->remove($clip);
          $manager->flush();

          if (!sizeof($live->getClips())) {
            $liveProducts = $live->getLiveProducts();
            $comments = $live->getComments();

            if ($liveProducts) {
              foreach ($liveProducts as $liveProduct) {
                $manager->remove($liveProduct);
              }
              $manager->flush();
            }

            if ($comments) {
              foreach ($comments as $comment) {
                $manager->remove($comment);
              }
              $manager->flush();
            }

            if ($env == "prod") {
              $url = "https://api.bambuser.com/broadcasts/" . $live->getBroadcastId();
              $ch = curl_init();

              curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "Accept: application/vnd.bambuser.v1+json", "Authorization: Bearer RkbHZdUPzA8Rcu2w4b1jn9"]);
              curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
              curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
              curl_setopt($ch, CURLOPT_URL, $url);

              $result = curl_exec($ch);
              $result = json_decode($result);
              curl_close($ch);
            }

            $manager->remove($live);
            $manager->flush();
          }
        }
      }

      $liveProducts = $liveProductRepo->findByProduct($product);
      if ($liveProducts) {
        foreach ($liveProducts as $liveProduct) {
          $manager->remove($liveProduct);
        }
        $manager->flush();
      }

      foreach ($product->getOptions()->toArray() as $option) {
        $manager->remove($option);
      }


      $lineItems = $lineItemRepo->findByProduct($product);
      if ($lineItems) {
        foreach ($lineItems as $lineItem) {
          $lineItem->setProduct(null);

          foreach ($product->getVariants()->toArray() as $variant) {
            $lineItems2 = $lineItemRepo->findByVariant($variant);

            foreach ($lineItems2 as $lineItem2) {
              $lineItem2->setVariant(null);
            }
            $manager->flush();
          }
        }
        $manager->flush();
      }

      foreach ($product->getVariants()->toArray() as $variant) {
        $manager->remove($variant);
      }

      foreach ($product->getUploads()->toArray() as $upload) {
        try {
          $fileName = explode(".", $upload->getFilename());
          $result = (new AdminApi())->deleteAssets($fileName[0], []);
        } catch (\Exception $e) {
          return $this->json($e->getMessage(), 404);
        }

        $manager->remove($upload);
      }

      $manager->flush();
      $manager->remove($product);
      $manager->flush();

      return $this->json($this->getUser(), 200, [], [
        'groups' => 'user:read', 
        'circular_reference_limit' => 1, 
        'circular_reference_handler' => function ($object) {
          return $object->getId();
        } 
      ]);
    }

    return $this->json("Le produit est introuvable", 404);
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

    return $this->json("Une erreur est survenue", 404);
  }


  /**
   * Supprimer un variant
   *
   * @Route("/user/api/variant/delete/{id}", name="user_api_variant_delete", methods={"GET"})
   */
  public function deleteVariant(Variant $variant, Request $request, ObjectManager $manager) {
    if ($variant) {
      $manager->remove($variant);
      $manager->flush();
      
      return $this->json(true, 200);
    }

    return $this->json("Le variant est introuvable", 404);
  }


  /**
   * Ajouter une image
   *
   * @Route("/user/api/product/upload", name="user_api__product_upload", methods={"POST"})
   */
  public function addPicture(Request $request, ObjectManager $manager, SerializerInterface $serializer) {
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
        "height" => 750, 
        "width" => 750, 
        "crop" => "thumb"
      ]);

      unlink($filepath);
    } catch (\Exception $e) {
      return $this->json($e->getMessage(), 404);
    }

    $upload = new Upload();
    $upload->setFilename($fullname);

    $manager->persist($upload);
    $manager->flush();

    return $this->json($upload, 200, [], [
      'groups' => 'upload:read',
      'circular_reference_limit' => 1, 
      'circular_reference_handler' => function ($object) {
        return $object->getId();
      } 
    ], 200);
  }


  /**
   * Supprimer une image
   *
   * @Route("/user/api/product/upload/delete/{id}", name="user_api_upload_delete", methods={"GET"})
   */
  public function deleteUpload(Upload $upload, Request $request, ObjectManager $manager) {
    if ($upload->getFilename()) {
      $oldFilename = explode(".", $upload->getFilename());

      try {
        $result = (new AdminApi())->deleteAssets($oldFilename[0], []);
      } catch (\Exception $e) {
        return $this->json($e->getMessage(), 404);
      }

      $manager->remove($upload);
      $manager->flush();

      return $this->json(true, 200);
    }

    return $this->json("L'image n'existe pas !", 404);
  }

}
