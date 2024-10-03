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
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Api\Admin\AdminApi;
use Cloudinary\Cloudinary;


class FollowAPIController extends AbstractController {


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
    	'groups' => 'user:follow', 
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
    	'groups' => 'user:follow', 
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
}
