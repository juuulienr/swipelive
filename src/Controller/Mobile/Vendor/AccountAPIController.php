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


class AccountAPIController extends Controller {


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

          if ($param['businessType'] == "company") {
            try {
              $stripe = new \Stripe\StripeClient('sk_test_oS3SEk3VCEWusPy8btUhcCR3');
              $response = $stripe->accounts->create([
                'country' => 'FR',
                'type' => 'custom',
                'capabilities' => [
                  'transfers' => ['requested' => true],
                ],
                'business_profile' => [
                  'product_description' => $param['summary'],
                ],
                'account_token' => $param['tokenAccount']
              ]);

              \Stripe\Stripe::setApiKey('sk_test_oS3SEk3VCEWusPy8btUhcCR3');

              $person = \Stripe\Account::createPerson($response->id, [
                'person_token' => $param['tokenPerson'],
              ]);
            } catch (Exception $e) {
              dump($e->getMessage());
              dd($request);
            }

          } else if ($param['businessType'] == "individual") {
            try {
              $stripe = new \Stripe\StripeClient('sk_test_oS3SEk3VCEWusPy8btUhcCR3');
              $response = $stripe->accounts->create([
                'country' => 'FR',
                'type' => 'custom',
                'capabilities' => [
                  'transfers' => ['requested' => true],
                ],
                'business_profile' => [
                  'product_description' => $param['summary'],
                ],
                'account_token' => $param['tokenAccount']
              ]);
            } catch (Exception $e) {
              dump($e->getMessage());
              dd($request);
            }
          }

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
    return $this->json($this->getUser(), 200, [], ['groups' => 'vendor:edit', 'circular_reference_limit' => 1, 'circular_reference_handler' => function ($object) {
        return $object->getId();
    } ]);
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

      return $this->json($this->getUser(), 200, [], ['groups' => 'vendor:edit', 'circular_reference_limit' => 1, 'circular_reference_handler' => function ($object) {
        return $object->getId();
      } ]);
    }

    return $this->json([ "error" => "Une erreur est survenue"], 404);
  }


  /**
   * Modifier image du profil
   *
   * @Route("/vendor/api/profile/picture", name="vendor_api_profile_picture", methods={"POST"})
   */
  public function picture(Request $request, ObjectManager $manager, SerializerInterface $serializer) {
    if ($request->files->get('picture')) {
      $file = $request->files->get('picture');

      if (!$file) {
        return $this->json("L'image est introuvable !", 404);
      }

      $filename = md5(time().uniqid()). "." . $file->guessExtension(); 
      $filepath = $this->getParameter('uploads_directory') . '/' . $filename;
      file_put_contents($filepath, file_get_contents($file));

      $vendor = $this->getUser();
      $vendor->setPicture($filename);
      $manager->flush();

      return $this->json($filename, 200);
    }

    return $this->json("L'image est introuvable !", 404);
  }


  /**
   * Follow/Unfollow un vendeur
   *
   * @Route("/vendor/api/follow/vendor/{id}", name="vendor_api_follow", methods={"GET"})
   */
  public function follow(Vendor $id, Request $request, ObjectManager $manager, FollowRepository $followRepo) {
    $follow = $followRepo->findOneBy(['following' => $id, 'vendor' => $this->getUser() ]);

    if (!$follow) {
      $follow = new Follow();
      $follow->setVendor($this->getUser());
      $follow->setFollowing($id);
      $manager->persist($follow);
    } else {
      $manager->remove($follow);
    }

    $manager->flush();

    return $this->json($id, 200, [], ['groups' => 'vendor:read', 'circular_reference_limit' => 1, 'circular_reference_handler' => function ($object) {
      return $object->getId();
    } ]);
  }
}
