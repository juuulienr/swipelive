<?php

namespace App\Controller;

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
  * @Route("/vendor/api/profile/edit", name="vendor_api_profile_edit", methods={"POST"})
  */
  public function editProfile(Request $request, ObjectManager $manager, VendorRepository $vendorRepo, SerializerInterface $serializer) {
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
   * Editer un produit
   *
   * @Route("/vendor/api/products/delete/{id}", name="vendor_api_product_delete", methods={"GET"})
   */
  public function deleteProduct(Product $product, Request $request, ObjectManager $manager) {
    if ($product) {
      if ($product->getUploads()->toArray()) {
        foreach ($product->getUploads()->toArray() as $upload) {
          $filePath = $this->getParameter('uploads_directory') . '/' . $upload->getFilename();

          if (file_exists($filePath)) {
            $filesystem = new Filesystem();
            $filesystem->remove($filePath);

            $manager->remove($upload);
            $manager->flush();
          }
        }
      }

      $manager->remove($product);
      $manager->flush();

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


  /**
   * Préparer un live
   *
   * @Route("/vendor/api/prelive", name="vendor_api_prelive_step1", methods={"POST"})
   */
  public function prelive(Request $request, ObjectManager $manager, SerializerInterface $serializer) {
    if ($json = $request->getContent()) {
      $live = $serializer->deserialize($json, Live::class, "json");
      $live->setVendor($this->getUser());

      $manager->persist($live);
      $manager->flush();

      return $this->json($live, 200, [], ['groups' => 'live:read'], 200);
    }

    return $this->json([ "error" => "Une erreur est survenue"], 404);
  }


  /**
   * Editer un liveproduct   
   * 
   * @Route("/vendor/api/liveproducts/edit/{id}", name="vendor_api_liveproducts_edit", methods={"PUT"})
   */
  public function prelive2(LiveProducts $liveProduct, Request $request, ObjectManager $manager, SerializerInterface $serializer) {
    if ($json = $request->getContent()) {
      $serializer->deserialize($json, LiveProducts::class, "json", [AbstractNormalizer::OBJECT_TO_POPULATE => $liveProduct]);
      $manager->flush();

      return $this->json(true, 200);
    }

    return $this->json(false, 404);
  }


  /**
   * Récupérer un live
   *
   * @Route("/vendor/api/live/{id}", name="vendor_api_live", methods={"GET"})
   */
  public function live(Live $live, Request $request, ObjectManager $manager) {
    return $this->json($live, 200, [], ['groups' => 'live:read'], 200);
  }


  /**
   * Mettre à jour un live
   *
   * @Route("/vendor/api/live/update/{id}", name="vendor_api_live_update", methods={"PUT"})
   */
  public function updateLive(Live $live, Request $request, ObjectManager $manager, SerializerInterface $serializer) {
    $channel = "channel" . $live->getId();
    $event = "event" . $live->getId();
    $vendor = $this->getUser();

    $options = [
      'cluster' => 'eu',
      'useTLS' => true
    ];

    $pusher = new \Pusher\Pusher('55da4c74c2db8041edd6', 'd61dc5df277d1943a6fa', '1274340', $options);
    $data = [
      "message" => [
        "content" => "Début du live", 
        "user" => "", 
        "vendor" => $vendor->getCompany() ? $vendor->getCompany() : $vendor->getFirstname(), 
        "picture" => $vendor->getPicture()
      ], 
    ];
    $pusher->trigger($channel, $event, $data);

    $live->setChannel($channel);
    $live->setEvent($event);
    $manager->flush();

    return $this->json($live, 200, [], ['groups' => 'live:read'], 200);
  }


  /**
   * Mettre à jour le produit
   *
   * @Route("/vendor/api/live/{id}/update/display", name="vendor_api_live_update_display", methods={"PUT"})
   */
  public function updateDisplay(Live $live, Request $request, ObjectManager $manager, SerializerInterface $serializer) {
    if ($json = $request->getContent()) {
      $param = json_decode($json, true);
      $display = $param["display"];
      $vendor = $this->getUser();

      $live->setDisplay($display);
      $manager->flush();

      // enregistrer la durée pour créer et récupérer le clip

      $options = [
        'cluster' => 'eu',
        'useTLS' => true
      ];

      $pusher = new \Pusher\Pusher('55da4c74c2db8041edd6', 'd61dc5df277d1943a6fa', '1274340', $options);
      $data = [
        "display" => $display
      ];
      $pusher->trigger($live->getChannel(), $live->getEvent(), $data);

      return $this->json($live, 200, [], ['groups' => 'live:read'], 200);
    }
  }


  /**
   * Mettre à jour le live avec bambuser
   *
   * @Route("/vendor/api/live/bambuser/{id}", name="vendor_api_live_bambuser", methods={"PUT"})
   */
  public function updateBambuser(Live $live, Request $request, ObjectManager $manager, SerializerInterface $serializer) {
    $url = "https://api.bambuser.com/broadcasts?limit=1&titleContains=Live" . $live->getId();
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "Accept: application/vnd.bambuser.v1+json", "Authorization: Bearer 2NJko17PqQdCDQ1DRkyMYr"]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_URL, $url);

    $result = curl_exec($ch);
    $result = json_decode($result);
    curl_close($ch);

    if (sizeof($result->results) > 0) {
      $broadcastId = $result->results[0]->id;
      $resourceUri = $result->results[0]->resourceUri;
      $thumbnail = $result->results[0]->preview;
      $vendor = $this->getUser();

      $live->setBroadcastId($broadcastId);
      $live->setResourceUri($resourceUri);
      $live->setThumbnail($thumbnail);
      $live->setStatus(1);
      $manager->flush();

      return $this->json(true, 200);
    } else {
      return $this->json(false, 404);
    }
  }


  /**
   * Arreter un live
   *
   * @Route("/vendor/api/live/stop/{id}", name="vendor_api_live_stop", methods={"PUT"})
   */
  public function stopLive(Live $live, Request $request, ObjectManager $manager, SerializerInterface $serializer) {
    if ($json = $request->getContent()) {
      $live->setStatus(2);
      $manager->flush();

      return $this->json($live, 200, [], ['groups' => 'live:read'], 200);
    }

    return $this->json(false, 404);
  }



  /**
   * Ajouter un message pendant le live
   *
   * @Route("/vendor/api/live/{id}/message/add", name="vendor_api_live_message_add", methods={"POST"})
   */
  public function addMessage(Live $live, Request $request, ObjectManager $manager, SerializerInterface $serializer) {
    if ($json = $request->getContent()) {
      $param = json_decode($json, true);
      $content = $param["content"];
      $vendor = $this->getUser();

      $message = new Message();
      $message->setContent($content);
      $message->setVendor($vendor);
      $message->setLive($live);
      $manager->persist($message);
      $manager->flush();

      $options = [
        'cluster' => 'eu',
        'useTLS' => true
      ];

      $pusher = new \Pusher\Pusher('55da4c74c2db8041edd6', 'd61dc5df277d1943a6fa', '1274340', $options);
      $data = [
        "message" => [
          "content" => $content, 
          "user" => "", 
          "vendor" => $vendor->getCompany() ? $vendor->getCompany() : $vendor->getFirstname(), 
          "picture" => $vendor->getPicture()
        ]
      ];
      $pusher->trigger($live->getChannel(), $live->getEvent(), $data);

      return $this->json($live, 200, [], ['groups' => 'live:read'], 200);
    }
  }



  /**
   * Follow/Unfollow un vendeur
   *
   * @Route("/vendor/api/follow/vendor/{id}", name="vendor_api_follow", methods={"GET"})
   */
  public function follow(Vendor $vendor, Request $request, ObjectManager $manager, FollowRepository $followRepo) {
    $follow = $followRepo->findOneBy(['following' => $vendor, 'vendor' => $this->getUser() ]);

    if (!$follow) {
      $follow = new Follow();
      $follow->setVendor($this->getUser());
      $follow->setFollowing($vendor);

      $manager->persist($follow);
      $manager->flush();

      return $this->json(true, 200);
    }

    return $this->json(false, 404);
  }

}
