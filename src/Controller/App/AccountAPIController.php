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


class AccountAPIController extends AbstractController {


  /**
   * Inscription utilisateur
   *
  * @Route("/api/user/register", name="user_api_register")
  */
  public function register(Request $request, ObjectManager $manager, UserRepository $userRepo, UserPasswordEncoderInterface $encoder, SerializerInterface $serializer) {
    if ($json = $request->getContent()) {
      $param = json_decode($json, true);

      if ($param) {
        $user = $userRepo->findOneByEmail($param['email']);

        if (!$user) {
          $user = $serializer->deserialize($json, User::class, "json");
          $hash = $encoder->encodePassword($user, $param['password']);
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

          if ($param['device'] && $param['device'] != null) {
            $security->setModel($param['device']['model']);
            $security->setPlatform($param['device']['platform']);
            $security->setUuid($param['device']['uuid']);
            $security->setVersion($param['device']['version']);
            $security->setManufacturer($param['device']['manufacturer']);
            $security->setIsVirtual($param['device']['isVirtual']);
            $security->setSerial($param['device']['serial']);
          }

          $manager->persist($security);
          $manager->flush();

          return $this->json($user, 200, [], [
            'groups' => 'user:read', 
			    	'circular_reference_limit' => 1, 
			    	'circular_reference_handler' => function ($object) {
			    		return $object->getId();
			    	} 
			    ]);
        } else {
          return $this->json("Un compte existe avec cette adresse mail", 404);
        }
      }
    }

    return $this->json("Une erreur est survenue", 404);
  }


  /**
   * Ajouter le push token
   *
   * @Route("/user/api/push/add", name="user_push_add")
   */
  public function addPush(Request $request, ObjectManager $manager)
  {
    $user = $this->getUser();

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
    return $this->json($this->getUser(), 200, [], [
    	'groups' => 'user:read', 
    	'circular_reference_limit' => 1, 
    	'circular_reference_handler' => function ($object) {
    		return $object->getId();
    	} 
    ]);
  }


  /**
   * Check si utilisateur est en ligne
   *
   * @Route("/user/api/ping", name="user_api_ping", methods={"GET"})
   */
  public function ping(Request $request, ObjectManager $manager, SecurityUserRepository $securityRepo) {
    $user = $this->getUser(); 
    $security = $securityRepo->findOneByUser($user);

    if ($security) {
      $security->setConnectedAt(new \DateTime('now', timezone_open('UTC')));
      $manager->flush();
    }

    return $this->json(true, 200);
  }


  /**
   * Accès avec Apple
   *
  * @Route("/api/authentication/apple", name="api_authentification_apple")
  */
  public function appleAuthentication(Request $request, ObjectManager $manager, UserRepository $userRepo, UserPasswordEncoderInterface $encoder, SerializerInterface $serializer) {
    if ($json = $request->getContent()) {
      $param = json_decode($json, true);

      if ($param) {
        $appleId = $param['appleId'];
        $password = $param['password'];
        $email = $param['email'];

        $userExist = $userRepo->findOneByEmail($email);
        $user = $userRepo->findOneByAppleId($appleId);

        if ($userExist) {
          $hash = $encoder->encodePassword($userExist, $password);
          $userExist->setHash($hash);
          $userExist->setAppleId($appleId);
          $manager->flush();

          return $this->json(false, 200);
        } else if (!$user) {
          $user = $serializer->deserialize($json, User::class, "json");
          $hash = $encoder->encodePassword($user, $password);

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

          if ($param['device'] && $param['device'] != null) {
            $security->setModel($param['device']['model']);
            $security->setPlatform($param['device']['platform']);
            $security->setUuid($param['device']['uuid']);
            $security->setVersion($param['device']['version']);
            $security->setManufacturer($param['device']['manufacturer']);
            $security->setIsVirtual($param['device']['isVirtual']);
            $security->setSerial($param['device']['serial']);
          }

          $manager->persist($security);
          $manager->flush();

          return $this->json(true, 200);
        } else {
          return $this->json(false, 200);
        }
      }
    }

    return $this->json("Une erreur est survenue", 404);
  }


  /**
   * Accès avec Google
   *
  * @Route("/api/authentication/google", name="api_authentification_google")
  */
  public function googleAuthentication(Request $request, ObjectManager $manager, UserRepository $userRepo, UserPasswordEncoderInterface $encoder, SerializerInterface $serializer) {
    if ($json = $request->getContent()) {
      $param = json_decode($json, true);

      if ($param) {
        $googleId = $param['googleId'];
        $picture = $param['picture'];
        $email = $param['email'];
        $password = $param['password'];

        $userExist = $userRepo->findOneByEmail($email);
        $user = $userRepo->findOneByGoogleId($googleId);

        if ($userExist) {
          if (!$userExist->getPicture()) {
            $filename = md5(uniqid());
            $fullname = $filename . ".jpg"; 
            $file->move($this->getParameter('uploads_directory'), $fullname);
            $file = $this->getParameter('uploads_directory') . '/' . $fullname;

            try {
              Configuration::instance($this->getParameter('cloudinary'));
              $result = (new UploadApi())->upload($file, [
                'public_id' => $filename,
                'use_filename' => TRUE,
                "height" => 256, 
                "width" => 256, 
                "crop" => "thumb"
              ]);

              $userExist->setPicture($fullname);
            } catch (\Exception $e) {
              return $this->json($e->getMessage(), 404);
            }
          }

          $hash = $encoder->encodePassword($userExist, $password);
          $userExist->setHash($hash);
          $userExist->setGoogleId($googleId);
          $manager->flush();

          return $this->json(false, 200);
        } else if (!$user) {
          $user = $serializer->deserialize($json, User::class, "json");
          $hash = $encoder->encodePassword($user, $password);
          $user->setHash($hash);
          $user->setGoogleId($googleId);

          $filename = md5(uniqid());
          $fullname = $filename . ".jpg"; 
          $file->move($this->getParameter('uploads_directory'), $fullname);
          $file = $this->getParameter('uploads_directory') . '/' . $fullname;

          try {
            Configuration::instance($this->getParameter('cloudinary'));
            $result = (new UploadApi())->upload($file, [
              'public_id' => $filename,
              'use_filename' => TRUE,
              "height" => 256, 
              "width" => 256, 
              "crop" => "thumb"
            ]);

            $user->setPicture($fullname);
          } catch (\Exception $e) {
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

          if ($param['device'] && $param['device'] != null) {
            $security->setModel($param['device']['model']);
            $security->setPlatform($param['device']['platform']);
            $security->setUuid($param['device']['uuid']);
            $security->setVersion($param['device']['version']);
            $security->setManufacturer($param['device']['manufacturer']);
            $security->setIsVirtual($param['device']['isVirtual']);
            $security->setSerial($param['device']['serial']);
          }

          $manager->persist($security);
          $manager->flush();

          return $this->json(true, 200);
        } else {
          return $this->json(false, 200);
        }
      }
    }

    return $this->json("Une erreur est survenue", 404);
  }


  /**
   * Accès avec Facebook
   *
  * @Route("/api/authentication/facebook", name="api_authentification_facebook")
  */
  public function facebookAuthentication(Request $request, ObjectManager $manager, UserRepository $userRepo, UserPasswordEncoderInterface $encoder, SerializerInterface $serializer) {
    if ($json = $request->getContent()) {
      $param = json_decode($json, true);

      if ($param) {
        $facebookId = $param['facebookId'];
        $picture = $param['picture'];
        $email = $param['email'];
        $password = $param['password'];

        $userExist = $userRepo->findOneByEmail($email);
        $user = $userRepo->findOneByFacebookId($facebookId);

        if ($userExist) {
          if (!$userExist->getPicture()) {
            $filename = md5(uniqid());
            $fullname = $filename . ".jpg"; 
            $file->move($this->getParameter('uploads_directory'), $fullname);
            $file = $this->getParameter('uploads_directory') . '/' . $fullname;

            try {
              Configuration::instance($this->getParameter('cloudinary'));
              $result = (new UploadApi())->upload($file, [
                'public_id' => $filename,
                'use_filename' => TRUE,
                "height" => 256, 
                "width" => 256, 
                "crop" => "thumb"
              ]);

              $userExist->setPicture($fullname);
            } catch (\Exception $e) {
              return $this->json($e->getMessage(), 404);
            }
          }

          $hash = $encoder->encodePassword($userExist, $password);
          $userExist->setHash($hash);
          $userExist->setFacebookId($facebookId);
          $manager->flush();

          return $this->json(false, 200);
        } else if (!$user) {
          $user = $serializer->deserialize($json, User::class, "json");
          $hash = $encoder->encodePassword($user, $password);
          $filename = md5(uniqid());
          $fullname = $filename . ".jpg"; 
          $file->move($this->getParameter('uploads_directory'), $fullname);
          $file = $this->getParameter('uploads_directory') . '/' . $fullname;

          try {
            Configuration::instance($this->getParameter('cloudinary'));
            $result = (new UploadApi())->upload($file, [
              'public_id' => $filename,
              'use_filename' => TRUE,
              "height" => 256, 
              "width" => 256, 
              "crop" => "thumb"
            ]);

            $user->setPicture($fullname);
          } catch (\Exception $e) {
            return $this->json($e->getMessage(), 404);
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

          if ($param['device'] && $param['device'] != null) {
            $security->setModel($param['device']['model']);
            $security->setPlatform($param['device']['platform']);
            $security->setUuid($param['device']['uuid']);
            $security->setVersion($param['device']['version']);
            $security->setManufacturer($param['device']['manufacturer']);
            $security->setIsVirtual($param['device']['isVirtual']);
            $security->setSerial($param['device']['serial']);
          }

          $manager->persist($security);
          $manager->flush();

          return $this->json(true, 200);
        } else {
          return $this->json(false, 200);
        }
      }
    }

    return $this->json("Une erreur est survenue", 404);
  }



  /**
   * Redirection authentification avec facebook
   *
   * @Route("/api/facebook/oauth", name="api_facebook_oauth")
   */
  public function facebookOauth(Request $request, ObjectManager $manager) {
    return true;
  }


  /**
   * Edition du profil
   *
  * @Route("/user/api/profile/edit", name="user_api_profile_edit", methods={"POST"})
  */
  public function editProfile(Request $request, ObjectManager $manager, UserRepository $userRepo, SerializerInterface $serializer) {
    if ($json = $request->getContent()) {
      $param = json_decode($json, true);
      $user = $this->getUser();
      $vendor = $user->getVendor();

      $serializer->deserialize($json, User::class, "json", [ AbstractNormalizer::OBJECT_TO_POPULATE => $user ]);
      $manager->flush();

      try {
        $stripe = new \Stripe\StripeClient($this->getParameter('stripe_sk'));
        $stripe->accounts->update($vendor->getStripeAcc(), [
          'business_profile' => [
            'name' => $param['businessName'],
            'product_description' => $param['summary'],
          ],
          'email' => $user->getEmail()
        ]);

        $vendor->setBusinessName($param['businessName']);
        $vendor->setSummary($param['summary']);
        $vendor->setAddress($param['address']);
        $vendor->setCity($param['city']);
        $vendor->setZip($param['zip']);
        $vendor->setCountry($param['country']);
        $vendor->setCountryCode($param['countryCode']);
        $manager->flush();

        return $this->json($this->getUser(), 200, [], [
          'groups' => 'user:read', 
          'circular_reference_limit' => 1, 
          'circular_reference_handler' => function ($object) {
            return $object->getId();
          } 
        ]);
      } catch (Exception $e) {
        return $this->json($e->getMessage(), 404);
      }
    }

    return $this->json("Une erreur est survenue", 404);
  }


  /**
   * Devenir vendeur
   *
  * @Route("/user/api/vendor", name="user_api_vendor", methods={"POST"})
  */
  public function vendor(Request $request, ObjectManager $manager, UserRepository $userRepo, SerializerInterface $serializer) {
    if ($json = $request->getContent()) {
      $user = $this->getUser();
      $param = json_decode($json, true);
      $serializer->deserialize($json, User::class, "json", [AbstractNormalizer::OBJECT_TO_POPULATE => $user]);
      $manager->flush();

      if ($param && !$user->getVendor()) {
        $vendor = new Vendor();
        $vendor->setBusinessName($param['businessName']);
        $vendor->setBusinessType($param['businessType']);
        $vendor->setSummary($param['summary']);
        $vendor->setAddress($param['address']);
        $vendor->setCity($param['city']);
        $vendor->setZip($param['zip']);
        $vendor->setCountry($param['country']);
        $vendor->setCountryCode($param['countryShort']);

        $user->setType("vendor");
        $user->setVendor($vendor);

        $manager->persist($vendor);
        $manager->flush();


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

          $vendor->setStripeAcc($response->id);
          $manager->flush();

          if ($param['businessType'] == "company") {
            try {
              \Stripe\Stripe::setApiKey($this->getParameter('stripe_sk'));
              $person = \Stripe\Account::createPerson($response->id, [
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
          'groups' => 'user:read', 
          'circular_reference_limit' => 1, 
          'circular_reference_handler' => function ($object) {
            return $object->getId();
          } 
        ]);
      }
    }

    return $this->json("Une erreur est survenue", 404);
  }


  /**
   * Modifier image du profil
   *
   * @Route("/user/api/profile/picture", name="user_api_profile_picture", methods={"POST"})
   */
  public function picture(Request $request, ObjectManager $manager, SerializerInterface $serializer) {
    $file = json_decode($request->getContent(), true);
    $user = $this->getUser();
    $oldFilename = $user->getPicture();

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

    $filename = md5(uniqid());
    $fullname = $filename.'.'.$file->guessExtension();
    $file->move($this->getParameter('uploads_directory'), $fullname);
    $file = $this->getParameter('uploads_directory') . '/' . $fullname;

    try {
      Configuration::instance($this->getParameter('cloudinary'));
      $result = (new UploadApi())->upload($file, [
        'public_id' => $filename,
        'use_filename' => TRUE,
        "height" => 256, 
        "width" => 256, 
        "crop" => "thumb"
      ]);
    } catch (\Exception $e) {
      return $this->json($e->getMessage(), 404);
    }

    if ($oldFilename) {
      $oldFilename = explode(".", $oldFilename);
      $result = (new AdminApi())->deleteAssets($oldFilename[0], []);
    }

    $user->setPicture($fullname);
    $manager->flush();
    
    return $this->json($user, 200, [], [
    	'groups' => 'user:read', 
    	'circular_reference_limit' => 1, 
    	'circular_reference_handler' => function ($object) {
    		return $object->getId();
    	} 
    ]);
  }

}
