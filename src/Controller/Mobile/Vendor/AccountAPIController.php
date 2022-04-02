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
  * @Route("/api/user/register", name="user_api_register")
  */
  public function register(Request $request, ObjectManager $manager, VendorRepository $userRepo , UserPasswordEncoderInterface $encoder, SerializerInterface $serializer) {
    if ($json = $request->getContent()) {
      $param = json_decode($json, true);

      if ($param) {
        $user = $userRepo->findOneByEmail($param['email']);

        if (!$user) {
          $user = $serializer->deserialize($json, Vendor::class, "json");
          $hash = $encoder->encodePassword($user, $param['password']);
          $user->setHash($hash);

          $manager->persist($user);
          $manager->flush();

          if ($param['businessType'] == "company") {
            try {
              $stripe = new \Stripe\StripeClient($this->getParameter('stripe_sk'));
              $response = $stripe->accounts->create([
                'country' => 'FR',
                'type' => 'custom',
                'capabilities' => [
                  'transfers' => ['requested' => true],
                ],
                'business_profile' => [
                  'product_description' => $param['summary'],
                ],
                'account_token' => $param['tokenAccount'],
                'settings' => [
                  'payouts' => [
                    'schedule' => [
                      'interval' => 'manual'
                    ]
                  ]
                ]
              ]);

              \Stripe\Stripe::setApiKey($this->getParameter('stripe_sk'));

              $person = \Stripe\Account::createPerson($response->id, [
                'person_token' => $param['tokenPerson'],
              ]);

              $user->setStripeAcc($response->id);
              $user->setPersonId($person->id);
              $manager->flush();

            } catch (Exception $e) {
              return $this->json($e->getMessage(), 404);
            }

          } else if ($param['businessType'] == "individual") {
            try {
              $stripe = new \Stripe\StripeClient($this->getParameter('stripe_sk'));
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

              $user->setStripeAcc($response->id);
              $manager->flush();
              
            } catch (Exception $e) {
              return $this->json($e->getMessage(), 404);
            }
          }

          return $this->json($user, 200);

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
   * @Route("/user/api/push/add", name="user_push_add")
   */
  public function addPush(Request $request, ObjectManager $manager)
  {
    $user = $this->getUser(); $token = [];

    // récupérer le push token
    if ($content = $request->getContent()) {
      $result = json_decode($content, true);
      if ($result) {
        $user->setPushToken($result['pushToken']);
        $manager->flush();

        return $this->json(true, 200);
      }
    }

    return $this->json("Le token est introuvable", 404);
  }


  /**
   * Récupérer le profil
   *
   * @Route("/user/api/profile", name="user_api_profile", methods={"GET"})
   */
  public function profile(Request $request, ObjectManager $manager) {
    return $this->json($this->getUser(), 200, [], ['groups' => 'user:read', "datetime_format" => "Y-m-d", 'circular_reference_limit' => 1, 'circular_reference_handler' => function ($object) {
        return $object->getId();
    } ]);
  }


  /**
   * Edition du profil
   *
  * @Route("/user/api/profile/edit", name="user_api_profile_edit", methods={"POST"})
  */
  public function editProfile(Request $request, ObjectManager $manager, VendorRepository $userRepo, SerializerInterface $serializer) {
    if ($json = $request->getContent()) {
      $serializer->deserialize($json, Vendor::class, "json", [AbstractNormalizer::OBJECT_TO_POPULATE => $this->getUser()]);
      $manager->flush();

      return $this->json($this->getUser(), 200, [], ['groups' => 'user:read', "datetime_format" => "Y-m-d", 'circular_reference_limit' => 1, 'circular_reference_handler' => function ($object) {
        return $object->getId();
      } ]);
    }

    return $this->json([ "error" => "Une erreur est survenue"], 404);
  }


  /**
   * Modifier image du profil
   *
   * @Route("/user/api/profile/picture", name="user_api_profile_picture", methods={"POST"})
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

      $user = $this->getUser();
      $user->setPicture($filename);
      $manager->flush();

      return $this->json($filename, 200);
    }

    return $this->json("L'image est introuvable !", 404);
  }


  /**
   * Follow/Unfollow un vendeur
   *
   * @Route("/user/api/follow/user/{id}", name="user_api_follow", methods={"GET"})
   */
  public function follow(Vendor $id, Request $request, ObjectManager $manager, FollowRepository $followRepo) {
    $follow = $followRepo->findOneBy(['following' => $id, 'user' => $this->getUser() ]);

    if (!$follow) {
      $follow = new Follow();
      $follow->setVendor($this->getUser());
      $follow->setFollowing($id);
      $manager->persist($follow);
    } else {
      $manager->remove($follow);
    }

    $manager->flush();

    return $this->json($id, 200, [], ['groups' => 'user:read', 'circular_reference_limit' => 1, 'circular_reference_handler' => function ($object) {
      return $object->getId();
    } ]);
  }
}
