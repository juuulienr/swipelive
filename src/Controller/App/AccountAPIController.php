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


class AccountAPIController extends Controller {


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


    return $this->json([ "error" => "Une erreur est survenue"], 404);
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
   * Accès avec Facebook
   *
  * @Route("/api/authentication/facebook", name="api_facebook_authentification")
  */
  public function facebookAuthentication(Request $request, ObjectManager $manager, UserRepository $userRepo, UserPasswordEncoderInterface $encoder, SerializerInterface $serializer) {
    if ($json = $request->getContent()) {
      $param = json_decode($json, true);

      if ($param) {
        $facebookId = $param['facebookId'];
        $picture = $param['picture'];
        $email = $param['email'];
        $password = $param['password'];

        $filename = md5(uniqid());
        $fullname = $filename . ".jpg"; 
        $filepath = $this->getParameter('uploads_directory') . '/' . $fullname;
        file_put_contents($filepath, file_get_contents($picture));

        try {
          $result = (new UploadApi())->upload($filepath, [
            'public_id' => $filename,
            'use_filename' => TRUE,
            "height" => 256, 
            "width" => 256, 
            "crop" => "thumb"
          ]);

          unlink($filepath);
        } catch (\Exception $e) {
          return $this->json($e->getMessage(), 404);
        }

        $userExist = $userRepo->findOneByEmail($email);
        $user = $userRepo->findOneByFacebookId($facebookId);

        if ($userExist) {
          if ($userExist->getPicture()) {
            $oldFilename = explode(".", $userExist->getPicture());
            $result = (new AdminApi())->deleteAssets($oldFilename[0], []);
          }

          $hash = $encoder->encodePassword($userExist, $password);
          $userExist->setHash($hash);
          $userExist->setPicture($filename);
          $userExist->setFacebookId($facebookId);
          $manager->flush();

          return $this->json(false, 200);
        } else if (!$user) {
          $user = $serializer->deserialize($json, User::class, "json");
          $hash = $encoder->encodePassword($user, $password);
          $user->setHash($hash);
          $user->setPicture($filename);

          $manager->persist($user);
          $manager->flush();

          return $this->json(true, 200);
        } else {
          return $this->json(true, 200);
        }
      }
    }

    return $this->json([ "error" => "Une erreur est survenue"], 404);
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
      $serializer->deserialize($json, User::class, "json", [AbstractNormalizer::OBJECT_TO_POPULATE => $this->getUser()]);
      $manager->flush();

      $param = json_decode($json, true);

      if ($param['businessType']) {
        $vendor = $this->getUser()->getVendor();
        $vendor->setBusinessName($param['businessName']);
        $vendor->setSummary($param['summary']);
        $vendor->setAddress($param['address']);
        $vendor->setCity($param['city']);
        $vendor->setZip($param['zip']);
        $vendor->setCountry($param['country']);
        $vendor->setCountryCode($param['countryCode']);
        $vendor->setCompany($param['company']);
        $vendor->setSiren($param['siren']);
        $manager->flush();
      }

	    return $this->json($this->getUser(), 200, [], [
	    	'groups' => 'user:read', 
	    	'circular_reference_limit' => 1, 
	    	'circular_reference_handler' => function ($object) {
	    		return $object->getId();
	    	} 
	    ]);
    }

    return $this->json([ "error" => "Une erreur est survenue"], 404);
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

        if ($param['businessType'] == "company") {
          $vendor->setCompany($param['company']);
          $vendor->setSiren($param['siren']);
          $manager->flush();
        }
      }

      return $this->json($user, 200, [], [
        'groups' => 'user:read', 
        'circular_reference_limit' => 1, 
        'circular_reference_handler' => function ($object) {
          return $object->getId();
        } 
      ]);
    }

    return $this->json([ "error" => "Une erreur est survenue"], 404);
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


    $filename = md5(time().uniqid()); 
    $fullname = $filename . "." . $extension; 
    $filepath = $this->getParameter('uploads_directory') . '/' . $fullname;
    file_put_contents($filepath, $content);

    try {
      $result = (new UploadApi())->upload($filepath, [
        'public_id' => $filename,
        'use_filename' => TRUE,
        "height" => 256, 
        "width" => 256, 
        "crop" => "thumb"
      ]);

      unlink($filepath);
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


  /**
   * Follow/Unfollow un utilisateur
   *
   * @Route("/user/api/follow/{id}", name="user_api_follow", methods={"GET"})
   */
  public function follow(User $user, Request $request, ObjectManager $manager, FollowRepository $followRepo) {
    $follow = $followRepo->findOneBy(['following' => $user, 'follower' => $this->getUser() ]);

    if (!$follow) {
      $follow = new Follow();
      $follow->setFollower($this->getUser());
      $follow->setFollowing($user);
      $manager->persist($follow);
    } else {
      $manager->remove($follow);
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


  /**
   * Récupérer les abonnement
   *
   * @Route("/user/api/following", name="user_api_following", methods={"GET"})
   */
  public function following(Request $request, ObjectManager $manager, UserRepository $userRepo) {
    $following = $userRepo->findUserFollowing($this->getUser());

    return $this->json($following, 200, [], [
    	'groups' => 'user:read', 
    	'circular_reference_limit' => 1, 
    	'circular_reference_handler' => function ($object) {
    		return $object->getId();
    	} 
    ]);
  }


  /**
   * Récupérer les abonnés
   *
   * @Route("/user/api/followers", name="user_api_followers", methods={"GET"})
   */
  public function followers(Request $request, ObjectManager $manager, UserRepository $userRepo) {
    $followers = $userRepo->findUserFollowers($this->getUser());

    return $this->json($followers, 200, [], [
    	'groups' => 'user:read', 
    	'circular_reference_limit' => 1, 
    	'circular_reference_handler' => function ($object) {
    		return $object->getId();
    	} 
    ]);
  }


  /**
   * Supprimer un abonné
   *
   * @Route("/user/api/followers/remove/{id}", name="user_api_followers_remove", methods={"GET"})
   */
  public function removeFollower(User $user, Request $request, ObjectManager $manager, UserRepository $userRepo, FollowRepository $followRepo) {
    $follow = $followRepo->findOneBy(['following' => $this->getUser(), 'follower' => $user ]);

    if ($follow) {
      $manager->remove($follow);
      $manager->flush();
    }

    return $this->json($this->getUser(), 200, [], [
      'groups' => 'user:read', 
      'circular_reference_limit' => 1, 
      'circular_reference_handler' => function ($object) {
        return $object->getId();
      } 
    ]);
  }


  // /**
  //  * Récupérer les favoris
  //  *
  //  * @Route("/user/api/favoris", name="user_api_favoris", methods={"GET"})
  //  */
  // public function followers(Request $request, ObjectManager $manager, UserRepository $userRepo) {
  //   $followers = $userRepo->findUserFollowers($this->getUser());

  //   return $this->json($followers, 200, [], [
  //     'groups' => 'user:read', 
  //     'circular_reference_limit' => 1, 
  //     'circular_reference_handler' => function ($object) {
  //       return $object->getId();
  //     } 
  //   ]);
  // }


  /**
   * Ajouter/Enlever des favoris
   *
   * @Route("/user/api/favoris/{id}", name="user_api_favoris", methods={"GET"})
   */
  public function favoris(Product $product, Request $request, ObjectManager $manager, FavorisRepository $favorisRepo) {
    $favoris = $favorisRepo->findOneBy(['user' => $this->getUser(), 'product' => $product ]);

    if (!$favoris) {
      $favoris = new Favoris();
      $favoris->setUser($this->getUser());
      $favoris->setProduct($product);
      $manager->persist($favoris);
    } else {
      $manager->remove($favoris);
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
}
