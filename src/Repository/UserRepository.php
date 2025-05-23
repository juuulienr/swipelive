<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[] findAll()
 * @method User[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method User|null findOneByEmail(string $email)
 * @method User|null findOneByPushToken(string $pushToken)
 * @method User|null findOneByAppleId(string $appleId)
 * @method User|null findOneByGoogleId(string $googleId)
 * @method User|null findOneByFacebookId(string $facebookId)
 */
class UserRepository extends ServiceEntityRepository
{
  public function __construct(ManagerRegistry $registry)
  {
    parent::__construct($registry, User::class);
  }

  public function findUserFollowing($user)
  {
    return $this->createQueryBuilder('u')
    ->join('u.followers', 'f')
    ->andWhere('f.follower = :user')
    ->setParameter('user', $user)
    ->getQuery()
    ->getResult();
  }

  public function findUserFollowers($user)
  {
    return $this->createQueryBuilder('u')
    ->join('u.following', 'f')
    ->andWhere('f.following = :user')
    ->setParameter('user', $user)
    ->getQuery()
    ->getResult();
  }

  public function findUserBySearch(?string $search, $vendor)
  {
    $query = $this->createQueryBuilder('u')
    ->join('u.vendor', 'v')
    ->andWhere('u.type = :type');

    if ($search) {
      $query->andWhere('u.firstname LIKE :search OR u.lastname LIKE :search OR v.pseudo LIKE :search')
      ->setParameter('search', '%' . $search . '%');
    }

    if ($vendor) {
      $query->andWhere('v.id != :vendor')
      ->setParameter('vendor', $vendor);
    }

    return $query->setParameter('type', 'vendor')
      ->getQuery()
      ->setMaxResults(21)
    ->getResult();
  }

  public function findTrendingClips($vendor)
  {
    $query = $this->createQueryBuilder('c')
    ->join('c.vendor', 'v')
    ->andWhere('c.status = :status')
    ->setParameter('status', 'available');

    if ($vendor) {
      $query->andWhere('v.id != :vendor')
      ->setParameter('vendor', $vendor);
    }

    return $query->orderBy('c.createdAt', 'DESC')
    ->getQuery()
    ->getResult();
  }


  // /**
  //  * @return User[] Returns an array of User objects
  //  */
  /*
  public function findByExampleField($value)
  {
      return $this->createQueryBuilder('u')
          ->andWhere('u.exampleField = :val')
          ->setParameter('val', $value)
          ->orderBy('u.id', 'ASC')
          ->setMaxResults(10)
          ->getQuery()
          ->getResult()
      ;
  }
  */

  /*
  public function findOneBySomeField($value): ?User
  {
      return $this->createQueryBuilder('u')
          ->andWhere('u.exampleField = :val')
          ->setParameter('val', $value)
          ->getQuery()
          ->getOneOrNullResult()
      ;
  }
  */
}
