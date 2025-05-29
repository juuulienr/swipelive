<?php

declare(strict_types=1);

namespace App\Controller\App;

use App\Entity\Follow;
use App\Entity\User;
use App\Repository\FollowRepository;
use App\Repository\UserRepository;
use Doctrine\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class FollowAPIController extends AbstractController
{
  public function getUser(): ?User
  {
    $user = parent::getUser();
    return $user instanceof User ? $user : null;
  }

  /**
   * Follow/Unfollow un utilisateur
   *
   * @Route("/user/api/follow/{id}", name="user_api_follow", methods={"GET"})
   */
  public function follow(User $user, Request $request, ObjectManager $manager, FollowRepository $followRepo): JsonResponse
  {
    $follow = $followRepo->findOneBy(['following' => $user, 'follower' => $this->getUser()]);

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
      'groups'                     => 'user:read',
      'circular_reference_limit'   => 1,
      'circular_reference_handler' => fn ($object) => $object->getId(),
    ]);
  }

  /**
   * Récupérer les abonnement
   *
   * @Route("/user/api/following", name="user_api_following", methods={"GET"})
   */
  public function following(Request $request, ObjectManager $manager, UserRepository $userRepo): JsonResponse
  {
    $following = $userRepo->findUserFollowing($this->getUser());

    return $this->json($following, 200, [], [
      'groups'                     => 'user:follow',
      'circular_reference_limit'   => 1,
      'circular_reference_handler' => fn ($object) => $object->getId(),
    ]);
  }

  /**
   * Récupérer les abonnés
   *
   * @Route("/user/api/followers", name="user_api_followers", methods={"GET"})
   */
  public function followers(Request $request, ObjectManager $manager, UserRepository $userRepo): JsonResponse
  {
    $followers = $userRepo->findUserFollowers($this->getUser());

    return $this->json($followers, 200, [], [
      'groups'                     => 'user:follow',
      'circular_reference_limit'   => 1,
      'circular_reference_handler' => fn ($object) => $object->getId(),
    ]);
  }

  /**
   * Supprimer un abonné
   *
   * @Route("/user/api/followers/remove/{id}", name="user_api_followers_remove", methods={"GET"})
   */
  public function removeFollower(User $user, Request $request, ObjectManager $manager, UserRepository $userRepo, FollowRepository $followRepo): JsonResponse
  {
    $follow = $followRepo->findOneBy(['following' => $this->getUser(), 'follower' => $user]);

    if ($follow) {
      $manager->remove($follow);
      $manager->flush();
    }

    return $this->json($this->getUser(), 200, [], [
      'groups'                     => 'user:read',
      'circular_reference_limit'   => 1,
      'circular_reference_handler' => fn ($object) => $object->getId(),
    ]);
  }
}
