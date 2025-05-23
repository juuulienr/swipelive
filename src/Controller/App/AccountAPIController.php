<?php

declare(strict_types=1);

namespace App\Controller\App;

use App\Entity\SecurityUser;
use App\Entity\User;
use App\Entity\Vendor;
use App\Repository\SecurityUserRepository;
use App\Repository\UserRepository;
use App\Service\FirebaseMessagingService;
use Bugsnag\Client;
use Cloudinary\Api\Admin\AdminApi;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Configuration\Configuration;
use DateTime;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Stripe\Account;
use Stripe\Stripe;
use Stripe\StripeClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class AccountAPIController extends AbstractController
{
  public function __construct(private readonly FirebaseMessagingService $firebaseMessagingService, private readonly Client $bugsnag)
  {
  }

  /**
   * @return User
   */
  public function getUser(): ?User
  {
    return parent::getUser();
  }

  /**
   * Inscription utilisateur
   *
   * @Route("/api/user/register", name="user_api_register")
   */
  public function register(Request $request, ObjectManager $manager, UserRepository $userRepo, UserPasswordHasherInterface $passwordHasher, SerializerInterface $serializer): JsonResponse
  {
    if ($json = $request->getContent()) {
      $param = \json_decode($json, true);

      if ($param) {
        $user = $userRepo->findOneByEmail($param['email']);

        if (!$user) {
          $user = $serializer->deserialize($json, User::class, 'json');
          $hash = $passwordHasher->hashPassword($user, $param['password']);
          $user->setHash($hash);

          $manager->persist($user);
          $manager->flush();

          $security = new SecurityUser();
          $security->setUser($user);
          $security->setWifiIPAddress($param['wifiIPAddress']);
          $security->setCarrierIPAddress($param['carrierIPAddress']);
          $security->setConnection($param['connection']);
          $security->setTimezone($param['timezone']);
          $security->setLocale($param['locale']);

          if ($param['device'] && null !== $param['device']) {
            $security->setModel($param['device']['model']);
            $security->setPlatform($param['device']['platform']);
            $security->setUuid($param['device']['uuid']);
            $security->setVersion($param['device']['version']);
            $security->setManufacturer($param['device']['manufacturer']);
            $security->setIsVirtual($param['device']['isVirtual']);
          }

          $manager->persist($security);
          $manager->flush();

          return $this->json($user, 200, [], [
            'groups'                     => 'user:read',
            'circular_reference_limit'   => 1,
            'circular_reference_handler' => fn ($object) => $object->getId(),
          ]);
        }

        return $this->json('Un compte existe avec cette adresse mail', 404);
      }
    }

    return $this->json('Une erreur est survenue', 404);
  }

  /**
   * Ajouter le push token
   *
   * @Route("/user/api/push/add", name="user_push_add")
   */
  public function addPush(Request $request, ObjectManager $manager, UserRepository $userRepo): JsonResponse
  {
    if ($content = $request->getContent()) {
      $result = \json_decode($content, true);
      $user   = $this->getUser();

      if ($result) {
        $exist = $userRepo->findOneByPushToken($result['pushToken']);

        if ($exist) {
          $exist->setPushToken(null);
        }

        $user->setPushToken($result['pushToken']);
        $manager->flush();

        return $this->json(true, 200);
      }
    }

    return $this->json('Le token est introuvable', 404);
  }

  /**
   * Test Notif Push
   *
   * @Route("/api/test/{id}", name="api_notif_push_test", methods={"GET"})
   */
  public function testNotifPush(User $user, Request $request, ObjectManager $manager): ?JsonResponse
  {
    $data = [
      'route'   => 'ListOrders',
      'type'    => 'vente',
      'isOrder' => true,
      'orderId' => 446,
    ];

    try {
      $response = $this->firebaseMessagingService->sendNotification('Swipe Live', 'Test notif push', $user->getPushToken(), $data);

      return $this->json($response, 200);
    } catch (Exception $error) {
      $this->bugsnag->notifyException($error);
    }

    return null;
  }

  /**
   * Récupérer le profil
   *
   * @Route("/user/api/profile", name="user_api_profile", methods={"GET"})
   */
  public function profile(Request $request, ObjectManager $manager): JsonResponse
  {
    return $this->json($this->getUser(), 200, [], [
      'groups'                     => 'user:read',
      'circular_reference_limit'   => 1,
      'circular_reference_handler' => fn ($object) => $object->getId(),
    ]);
  }

  /**
   * Check si utilisateur est en ligne
   *
   * @Route("/user/api/ping", name="user_api_ping", methods={"GET"})
   */
  public function ping(Request $request, ObjectManager $manager, SecurityUserRepository $securityRepo): JsonResponse
  {
    $user     = $this->getUser();
    $security = $securityRepo->findOneByUser($user);

    if ($security) {
      $security->setConnectedAt(new DateTime('now', \timezone_open('UTC')));
      $manager->flush();
    }

    return $this->json(true, 200);
  }

  /**
   * Accès avec Apple
   *
   * @Route("/api/authentication/apple", name="api_authentification_apple")
   */
  public function appleAuthentication(Request $request, ObjectManager $manager, UserRepository $userRepo, UserPasswordHasherInterface $passwordHasher, SerializerInterface $serializer): JsonResponse
  {
    if ($json = $request->getContent()) {
      $param = \json_decode($json, true);

      if ($param) {
        try {
          $appleId  = $param['appleId'];
          $password = $param['password'];
          $email    = $param['email'];

          $userExist = $userRepo->findOneByEmail($email);
          $user      = $userRepo->findOneByAppleId($appleId);

          if ($userExist) {
            $hash = $passwordHasher->hashPassword($userExist, $password);
            $userExist->setHash($hash);
            $userExist->setAppleId($appleId);
            $manager->flush();

            return $this->json(false, 200);
          }

          if (!$user) {
            $user = $serializer->deserialize($json, User::class, 'json');
            $hash = $passwordHasher->hashPassword($user, $password);
            $user->setAppleId($appleId);
            $user->setHash($hash);
            $manager->persist($user);
            $manager->flush();
            $security = new SecurityUser();
            $security->setUser($user);
            $security->setWifiIPAddress($param['wifiIPAddress']);
            $security->setCarrierIPAddress($param['carrierIPAddress']);
            $security->setConnection($param['connection']);
            $security->setTimezone($param['timezone']);
            $security->setLocale($param['locale']);

            if ($param['device'] && null !== $param['device']) {
              $security->setModel($param['device']['model']);
              $security->setPlatform($param['device']['platform']);
              $security->setUuid($param['device']['uuid']);
              $security->setVersion($param['device']['version']);
              $security->setManufacturer($param['device']['manufacturer']);
              $security->setIsVirtual($param['device']['isVirtual']);
            }
            $manager->persist($security);
            $manager->flush();

            return $this->json(true, 200);
          } else {
            return $this->json(false, 200);
          }
        } catch (Exception $e) {
          return $this->json($e, 404);
        }
      }
    }

    return $this->json('Une erreur est survenue', 404);
  }

  /**
   * Accès avec Google
   *
   * @Route("/api/authentication/google", name="api_authentification_google")
   */
  public function googleAuthentication(Request $request, ObjectManager $manager, UserRepository $userRepo, UserPasswordHasherInterface $passwordHasher, SerializerInterface $serializer): JsonResponse
  {
    if ($json = $request->getContent()) {
      $param = \json_decode($json, true);

      if ($param) {
        $googleId = $param['googleId'];
        $picture  = $param['picture'];
        $email    = $param['email'];
        $password = $param['password'];

        $userExist = $userRepo->findOneByEmail($email);
        $user      = $userRepo->findOneByGoogleId($googleId);

        if ($userExist) {
          if (!$userExist->getPicture()) {
            $filename     = \md5(\uniqid());
            $fullname     = $filename . '.jpg';
            $uploadsDir   = $this->getParameter('uploads_directory');
            $tempFilePath = $uploadsDir . '/' . $fullname;

            $imageData = \file_get_contents($picture);
            \file_put_contents($tempFilePath, $imageData);

            try {
              Configuration::instance($this->getParameter('cloudinary'));
              $result = (new UploadApi())->upload($tempFilePath, [
                'public_id'    => $filename,
                'use_filename' => true,
                'height'       => 256,
                'width'        => 256,
                'crop'         => 'thumb',
              ]);

              \unlink($tempFilePath);
              $userExist->setPicture($fullname);
            } catch (Exception $e) {
              return $this->json($e->getMessage(), 404);
            }
          }
          $hash = $passwordHasher->hashPassword($userExist, $password);
          $userExist->setHash($hash);
          $userExist->setGoogleId($googleId);
          $manager->flush();

          return $this->json(false, 200);
        }

        if (!$user) {
          $user = $serializer->deserialize($json, User::class, 'json');
          $hash = $passwordHasher->hashPassword($user, $password);
          $user->setHash($hash);
          $user->setGoogleId($googleId);
          $filename     = \md5(\uniqid());
          $fullname     = $filename . '.jpg';
          $uploadsDir   = $this->getParameter('uploads_directory');
          $tempFilePath = $uploadsDir . '/' . $fullname;
          $imageData    = \file_get_contents($picture);
          \file_put_contents($tempFilePath, $imageData);
          try {
            Configuration::instance($this->getParameter('cloudinary'));
            $result = (new UploadApi())->upload($tempFilePath, [
              'public_id'    => $filename,
              'use_filename' => true,
              'height'       => 256,
              'width'        => 256,
              'crop'         => 'thumb',
            ]);

            \unlink($tempFilePath);
            $user->setPicture($fullname);
          } catch (Exception $e) {
            return $this->json($e->getMessage(), 404);
          }
          $manager->persist($user);
          $manager->flush();
          $security = new SecurityUser();
          $security->setUser($user);
          $security->setWifiIPAddress($param['wifiIPAddress']);
          $security->setCarrierIPAddress($param['carrierIPAddress']);
          $security->setConnection($param['connection']);
          $security->setTimezone($param['timezone']);
          $security->setLocale($param['locale']);

          if ($param['device'] && null !== $param['device']) {
            $security->setModel($param['device']['model']);
            $security->setPlatform($param['device']['platform']);
            $security->setUuid($param['device']['uuid']);
            $security->setVersion($param['device']['version']);
            $security->setManufacturer($param['device']['manufacturer']);
            $security->setIsVirtual($param['device']['isVirtual']);
          }
          $manager->persist($security);
          $manager->flush();

          return $this->json(true, 200);
        } else {
          return $this->json(false, 200);
        }
      }
    }

    return $this->json('Une erreur est survenue', 404);
  }

  /**
   * Accès avec Facebook
   *
   * @Route("/api/authentication/facebook", name="api_authentification_facebook")
   */
  public function facebookAuthentication(Request $request, ObjectManager $manager, UserRepository $userRepo, UserPasswordHasherInterface $passwordHasher, SerializerInterface $serializer): JsonResponse
  {
    if ($json = $request->getContent()) {
      $param = \json_decode($json, true);

      if ($param) {
        $facebookId = $param['facebookId'];
        $picture    = $param['picture'];
        $email      = $param['email'];
        $password   = $param['password'];

        $userExist = $userRepo->findOneByEmail($email);
        $user      = $userRepo->findOneByFacebookId($facebookId);

        if ($userExist) {
          if (!$userExist->getPicture()) {
            $filename     = \md5(\uniqid());
            $fullname     = $filename . '.jpg';
            $uploadsDir   = $this->getParameter('uploads_directory');
            $tempFilePath = $uploadsDir . '/' . $fullname;

            $imageData = \file_get_contents($picture);
            \file_put_contents($tempFilePath, $imageData);

            try {
              Configuration::instance($this->getParameter('cloudinary'));
              $result = (new UploadApi())->upload($tempFilePath, [
                'public_id'    => $filename,
                'use_filename' => true,
                'height'       => 256,
                'width'        => 256,
                'crop'         => 'thumb',
              ]);

              \unlink($tempFilePath);
              $userExist->setPicture($fullname);
            } catch (Exception $e) {
              $this->bugsnag->notifyException($e);
            }
          }
          $hash = $passwordHasher->hashPassword($userExist, $password);
          $userExist->setHash($hash);
          $userExist->setFacebookId($facebookId);
          $manager->flush();

          return $this->json(false, 200);
        }

        if (!$user) {
          $user         = $serializer->deserialize($json, User::class, 'json');
          $hash         = $passwordHasher->hashPassword($user, $password);
          $filename     = \md5(\uniqid());
          $fullname     = $filename . '.jpg';
          $uploadsDir   = $this->getParameter('uploads_directory');
          $tempFilePath = $uploadsDir . '/' . $fullname;
          $imageData    = \file_get_contents($picture);
          \file_put_contents($tempFilePath, $imageData);
          try {
            Configuration::instance($this->getParameter('cloudinary'));
            $result = (new UploadApi())->upload($tempFilePath, [
              'public_id'    => $filename,
              'use_filename' => true,
              'height'       => 256,
              'width'        => 256,
              'crop'         => 'thumb',
            ]);

            \unlink($tempFilePath);
            $user->setPicture($fullname);
          } catch (Exception $e) {
            $this->bugsnag->notifyException($e);
          }
          $user->setHash($hash);
          $manager->persist($user);
          $manager->flush();
          $security = new SecurityUser();
          $security->setUser($user);
          $security->setWifiIPAddress($param['wifiIPAddress']);
          $security->setCarrierIPAddress($param['carrierIPAddress']);
          $security->setConnection($param['connection']);
          $security->setTimezone($param['timezone']);
          $security->setLocale($param['locale']);

          if ($param['device'] && null !== $param['device']) {
            $security->setModel($param['device']['model']);
            $security->setPlatform($param['device']['platform']);
            $security->setUuid($param['device']['uuid']);
            $security->setVersion($param['device']['version']);
            $security->setManufacturer($param['device']['manufacturer']);
            $security->setIsVirtual($param['device']['isVirtual']);
          }
          $manager->persist($security);
          $manager->flush();

          return $this->json(true, 200);
        } else {
          return $this->json(false, 200);
        }
      }
    }

    return $this->json('Une erreur est survenue', 404);
  }

  /**
   * Redirection authentification avec facebook
   *
   * @Route("/api/facebook/oauth", name="api_facebook_oauth")
   */
  public function facebookOauth(Request $request, ObjectManager $manager): JsonResponse
  {
    return $this->json(true, 200);
  }

  /**
   * Edition du profil
   *
   * @Route("/user/api/profile/edit", name="user_api_profile_edit", methods={"POST"})
   */
  public function editProfile(Request $request, ObjectManager $manager, UserRepository $userRepo, SerializerInterface $serializer): JsonResponse
  {
    if ($json = $request->getContent()) {
      $param  = \json_decode($json, true);
      $user   = $this->getUser();
      $vendor = $user->getVendor();

      $serializer->deserialize($json, User::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $user]);
      $manager->flush();

      if ($user->getVendor() && $vendor->getStripeAcc()) {
        try {
          $stripe = new StripeClient($this->getParameter('stripe_sk'));
          $stripe->accounts->update($vendor->getStripeAcc(), [
            'business_profile' => [
              'name'                => $param['pseudo'],
              'product_description' => $param['summary'],
            ],
            'email' => $user->getEmail(),
          ]);

          $vendor->setPseudo($param['pseudo']);
          $vendor->setSummary($param['summary']);
          $vendor->setAddress($param['address']);
          $vendor->setCity($param['city']);
          $vendor->setZip($param['zip']);
          $vendor->setCountry($param['country']);
          $vendor->setCountryCode($param['countryCode']);
          $manager->flush();
        } catch (Exception $e) {
          return $this->json($e->getMessage(), 404);
        }
      }

      return $this->json($this->getUser(), 200, [], [
        'groups'                     => 'user:read',
        'circular_reference_limit'   => 1,
        'circular_reference_handler' => fn ($object) => $object->getId(),
      ]);
    }

    return $this->json('Une erreur est survenue', 404);
  }

  /**
   * Devenir vendeur
   *
   * @Route("/user/api/vendor", name="user_api_vendor", methods={"POST"})
   */
  public function vendor(Request $request, ObjectManager $manager, UserRepository $userRepo, SerializerInterface $serializer): JsonResponse
  {
    if ($json = $request->getContent()) {
      $user  = $this->getUser();
      $param = \json_decode($json, true);
      $serializer->deserialize($json, User::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $user]);
      $manager->flush();

      if ($param && !$user->getVendor()) {
        $vendor = new Vendor();
        $vendor->setPseudo($param['pseudo']);
        $vendor->setBusinessType($param['businessType']);
        $vendor->setSummary($param['summary']);
        $vendor->setAddress($param['address']);
        $vendor->setCity($param['city']);
        $vendor->setZip($param['zip']);
        $vendor->setCountry($param['country']);
        $vendor->setCountryCode($param['countryShort']);

        $user->setType('vendor');
        $user->setVendor($vendor);

        $manager->persist($vendor);
        $manager->flush();


        try {
          $stripe   = new StripeClient($this->getParameter('stripe_sk'));
          $response = $stripe->accounts->create([
            'country'      => 'FR',
            'type'         => 'custom',
            'capabilities' => [
              'transfers' => ['requested' => true],
            ],
            'business_profile' => [
              'product_description' => $param['summary'],
            ],
            'account_token' => $param['tokenAccount'],
            'settings'      => [
              'payouts' => [
                'schedule' => [
                  'interval' => 'manual',
                ],
              ],
            ],
          ]);

          $vendor->setStripeAcc($response->id);
          $manager->flush();

          if ('company' === $param['businessType']) {
            try {
              Stripe::setApiKey($this->getParameter('stripe_sk'));
              $person = Account::createPerson($response->id, [
                'person_token' => $param['tokenPerson'],
              ]);

              $vendor->setCompany($param['company']);
              $vendor->setSiren($param['siren']);
              $vendor->setPersonId($person->id);
              $manager->flush();
            } catch (Exception $e) {
              return $this->json($e->getMessage(), 404);
            }
          }
        } catch (Exception $e) {
          return $this->json($e->getMessage(), 404);
        }

        return $this->json($user, 200, [], [
          'groups'                     => 'user:read',
          'circular_reference_limit'   => 1,
          'circular_reference_handler' => fn ($object) => $object->getId(),
        ]);
      }
    }

    return $this->json('Une erreur est survenue', 404);
  }

  /**
   * Modifier image du profil
   *
   * @Route("/user/api/profile/picture", name="user_api_profile_picture", methods={"POST"})
   */
  public function picture(Request $request, ObjectManager $manager, SerializerInterface $serializer): JsonResponse
  {
    $file        = \json_decode($request->getContent(), true);
    $user        = $this->getUser();
    $oldfilename = $user->getPicture();

    if ($file && \array_key_exists('picture', $file)) {
      $file      = $file['picture'];
      $extension = 'jpg';
    } elseif ($request->files->get('picture')) {
      $file      = $request->files->get('picture');
      $extension = $file->guessExtension();
    } else {
      return $this->json("L'image est introuvable !", 404);
    }

    $filename = \md5(\uniqid());
    $fullname = $filename . '.' . $extension;
    $file->move($this->getParameter('uploads_directory'), $fullname);
    $file = $this->getParameter('uploads_directory') . '/' . $fullname;

    try {
      Configuration::instance($this->getParameter('cloudinary'));
      $result = (new UploadApi())->upload($file, [
        'public_id'    => $filename,
        'use_filename' => true,
        'height'       => 256,
        'width'        => 256,
        'crop'         => 'thumb',
      ]);
    } catch (Exception $e) {
      return $this->json($e->getMessage(), 404);
    }

    if ($oldfilename) {
      $oldfilename = \explode('.', $oldfilename);
      $result      = (new AdminApi())->deleteAssets($oldfilename[0], []);
    }

    $user->setPicture($fullname);
    $manager->flush();

    return $this->json($user, 200, [], [
      'groups'                     => 'user:read',
      'circular_reference_limit'   => 1,
      'circular_reference_handler' => fn ($object) => $object->getId(),
    ]);
  }
}
