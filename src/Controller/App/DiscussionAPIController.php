<?php

namespace App\Controller\App;

use App\Entity\Vendor;
use App\Entity\Clip;
use App\Entity\Live;
use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\Follow;
use App\Entity\Product;
use App\Entity\Discussion;
use App\Entity\Message;
use App\Entity\Order;
use App\Entity\LiveProducts;
use App\Entity\Upload;
use App\Repository\LiveProductsRepository;
use App\Repository\FollowRepository;
use App\Repository\VendorRepository;
use App\Repository\DiscussionRepository;
use App\Repository\ProductRepository;
use App\Repository\CommentRepository;
use App\Repository\LiveRepository;
use App\Repository\OrderRepository;
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


class DiscussionAPIController extends Controller {


  /**
   * Afficher les discussions
   *
   * @Route("/user/api/discussions", name="user_api_discussions", methods={"GET"})
   */
  public function discussions(Request $request, ObjectManager $manager, DiscussionRepository $discussionRepo) {
    $array = $discussionRepo->findBy([ 'user' => $this->getUser() ]);
    $array2 = $discussionRepo->findBy([ 'vendor' => $this->getUser() ]);
    $discussions = $array + $array2;

    return $this->json($discussions, 200, [], [
      'groups' => 'discussion:read',
      'datetime_format' => 'H:i',
      'circular_reference_limit' => 1,
      'circular_reference_handler' => function ($object) {
        return $object->getId();
      } 
    ]);
  }


  // /**
  //  * Créer une discussion
  //  *
  //  * @Route("/user/api/discussions/add/{id}", name="user_api_discussions_add", methods={"GET"})
  //  */
  // public function addDiscussion(Vendor $vendor, Request $request, ObjectManager $manager, DiscussionRepository $discussionRepo) {

  //   $discussion = new Discussion();
    
  //   $manager->flush();

  //   return $this->json($discussionRepo->findAll(), 200, [], [
  //     'groups' => 'discussion:read',
  //     'datetime_format' => 'H:i',
  //     'circular_reference_limit' => 1,
  //     'circular_reference_handler' => function ($object) {
  //       return $object->getId();
  //     } 
  //   ]);
  // }


  /**
   * Afficher la discussion comme lu
   *
   * @Route("/user/api/discussions/{id}/seen", name="user_api_discussions_seen", methods={"GET"})
   */
  public function seenDiscussion(Discussion $discussion, Request $request, ObjectManager $manager, DiscussionRepository $discussionRepo) {
    if ($discussion->getUser()->getId() == $this->getUser()->getId()) {
      $discussion->setUnseen(false);
    } else {
      $discussion->setUnseenVendor(false);
    }

    $manager->flush();

    return $this->json($discussionRepo->findAll(), 200, [], [
      'groups' => 'discussion:read',
      'datetime_format' => 'H:i',
      'circular_reference_limit' => 1,
      'circular_reference_handler' => function ($object) {
        return $object->getId();
      } 
    ]);
  }


  /**
   * Ajouter un message
   *
   * @Route("/user/api/discussions/{id}/message", name="user_api_discussions_message", methods={"POST"})
   */
  public function addMessage(Discussion $discussion, Request $request, ObjectManager $manager, SerializerInterface $serializer, DiscussionRepository $discussionRepo) {
    if ($json = $request->getContent()) {
      $message = $serializer->deserialize($json, Message::class, "json");
      $message->setDiscussion($discussion);

      $discussion->setPreview($message->getText());
      $discussion->setUpdatedAt(new \DateTime('now', timezone_open('Europe/Paris')));

      if ($discussion->getUser()->getId() == $this->getUser()->getId()) {
        $discussion->setUnseenVendor(true);
      } else {
        $discussion->setUnseen(true);
      }

      $manager->persist($message);
      $manager->flush();

      return $this->json($discussionRepo->findAll(), 200, [], [
        'groups' => 'discussion:read',
        'datetime_format' => 'H:i',
        'circular_reference_limit' => 1,
        'circular_reference_handler' => function ($object) {
          return $object->getId();
        } 
      ]);
    }

    return $this->json([ "error" => "Une erreur est survenue"], 404);
  }
}
