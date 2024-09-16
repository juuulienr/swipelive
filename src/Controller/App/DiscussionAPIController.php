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
use App\Service\NotifPushService;
use App\Repository\LiveProductsRepository;
use App\Repository\FollowRepository;
use App\Repository\VendorRepository;
use App\Repository\DiscussionRepository;
use App\Repository\ProductRepository;
use App\Repository\CommentRepository;
use App\Repository\LiveRepository;
use App\Repository\OrderRepository;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Api\Admin\AdminApi;
use Cloudinary\Cloudinary;



class DiscussionAPIController extends AbstractController {

  private $notifPushService;

  public function __construct(NotifPushService $notifPushService) {
      $this->notifPushService = $notifPushService;
  }



  /**
   * Afficher les discussions
   *
   * @Route("/user/api/discussions", name="user_api_discussions", methods={"GET"})
   */
  public function discussions(Request $request, ObjectManager $manager, DiscussionRepository $discussionRepo) {
    $discussions = $discussionRepo->findByVendorAndUser($this->getUser());

    return $this->json($discussions, 200, [], [
      'groups' => 'discussion:read',
      'circular_reference_limit' => 1,
      'circular_reference_handler' => function ($object) {
        return $object->getId();
      } 
    ]);
  }


  /**
   * Créer une discussion
   *
   * @Route("/user/api/discussions/add", name="user_api_discussions_add", methods={"POST"})
   */
  public function addDiscussion(Request $request, ObjectManager $manager, DiscussionRepository $discussionRepo, SerializerInterface $serializer) {
    if ($json = $request->getContent()) {
      $discussion = $serializer->deserialize($json, Discussion::class, "json");

      if ($discussion) {
        $exist = $discussionRepo->findOneBy([ 'user' => $discussion->getUser(), 'vendor' => $discussion->getVendor() ]);

        if (!$exist) {
          $exist = $discussionRepo->findOneBy([ 'user' => $discussion->getVendor(), 'vendor' => $discussion->getUser() ]);
        }

        if (!$exist) {
          $manager->persist($discussion);
          $manager->flush();
        } else {
          $message = $discussion->getMessages()[0];
          $message->setDiscussion($exist);

          $manager->persist($message);
          $manager->flush();


          // update discussion
          $exist->setPreview($message->getText());
          $exist->setUpdatedAt(new \DateTime('now', timezone_open('UTC')));

          if ($exist->getUser()->getId() == $this->getUser()->getId()) {
            $exist->setUnseenVendor(true);
          } else {
            $exist->setUnseen(true);
          }
        }
      }

      $discussions = $discussionRepo->findByVendorAndUser($this->getUser());

      return $this->json($discussions, 200, [], [
        'groups' => 'discussion:read',
        'circular_reference_limit' => 1,
        'circular_reference_handler' => function ($object) {
          return $object->getId();
        } 
      ]);
    }

    return $this->json("Une erreur est survenue", 404);
  }


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
    
    $discussions = $discussionRepo->findByVendorAndUser($this->getUser());

    return $this->json($discussions, 200, [], [
      'groups' => 'discussion:read',
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
      $discussion->setUpdatedAt(new \DateTime('now', timezone_open('UTC')));

      if ($discussion->getUser()->getId() == $this->getUser()->getId()) {
        $discussion->setUnseenVendor(true);
        $name = $discussion->getVendor()->getVendor()->getPseudo();
        $receiver = $discussion->getVendor();
      } else {
        $discussion->setUnseen(true);
        $name = $discussion->getUser()->getFullName();
        $receiver = $discussion->getUser();
      }

      $manager->persist($message);
      $manager->flush();
      
      $data = [
        "discussionId" => $discussion->getId(),
        "message" => [
          "fromUser" => $this->getUser()->getId(),
          "picture" => null,
          "loading" => false,
          "text" => $message->getText(),
        ],
      ];

      $pusher = new \Pusher\Pusher($this->getParameter('pusher_key'), $this->getParameter('pusher_secret'), $this->getParameter('pusher_app_id'), [ 'cluster' => 'eu', 'useTLS' => true ]);
      $pusher->trigger("discussion_channel", "new_message", $data);
      $discussions = $discussionRepo->findByVendorAndUser($this->getUser());

      if ($receiver->getPushToken()) {
        try {
          $this->notifPushService->send("SWIPE LIVE", "Tu as un nouveau message de " . $name, $receiver->getPushToken());
        } catch (\Exception $error) {
          $this->bugsnag->notifyException($error);
        }
      }

      return $this->json($discussions, 200, [], [
        'groups' => 'discussion:read',
        'circular_reference_limit' => 1,
        'circular_reference_handler' => function ($object) {
          return $object->getId();
        } 
      ]);
    }

    return $this->json("Une erreur est survenue", 404);
  }


  /**
   * Utilisateur en train d'écrire
   *
   * @Route("/user/api/discussions/{id}/writing", name="user_api_discussions_writing", methods={"GET"})
   */
  public function writing(Discussion $discussion, Request $request, ObjectManager $manager, DiscussionRepository $discussionRepo) {
    $data = [
      "discussionId" => $discussion->getId(),
      "message" => [
        "fromUser" => $this->getUser()->getId(),
        "writing" => true,
      ],
    ];

    $pusher = new \Pusher\Pusher($this->getParameter('pusher_key'), $this->getParameter('pusher_secret'), $this->getParameter('pusher_app_id'), [ 'cluster' => 'eu', 'useTLS' => true ]);
    $pusher->trigger("discussion_channel", "new_message", $data);
    $discussions = $discussionRepo->findByVendorAndUser($this->getUser());

    return $this->json($discussions, 200, [], [
      'groups' => 'discussion:read',
      'circular_reference_limit' => 1,
      'circular_reference_handler' => function ($object) {
        return $object->getId();
      } 
    ]);
  }


  /**
   * Utilisateur n'écrit plus
   *
   * @Route("/user/api/discussions/{id}/writing/stop", name="user_api_discussions_writing_stop", methods={"GET"})
   */
  public function stopWriting(Discussion $discussion, Request $request, ObjectManager $manager, DiscussionRepository $discussionRepo) {
    $data = [
      "discussionId" => $discussion->getId(),
      "message" => [
        "fromUser" => $this->getUser()->getId(),
        "stopWriting" => false,
      ],
    ];

    $pusher = new \Pusher\Pusher($this->getParameter('pusher_key'), $this->getParameter('pusher_secret'), $this->getParameter('pusher_app_id'), [ 'cluster' => 'eu', 'useTLS' => true ]);
    $pusher->trigger("discussion_channel", "new_message", $data);
    $discussions = $discussionRepo->findByVendorAndUser($this->getUser());

    return $this->json($discussions, 200, [], [
      'groups' => 'discussion:read',
      'circular_reference_limit' => 1,
      'circular_reference_handler' => function ($object) {
        return $object->getId();
      } 
    ]);
  }


  /**
   * Ajouter une photo
   *
   * @Route("/user/api/discussions/{id}/picture", name="user_api_discussions_picture", methods={"POST"})
   */
  public function addPicture(Discussion $discussion, Request $request, ObjectManager $manager, SerializerInterface $serializer, DiscussionRepository $discussionRepo) {
    $file = json_decode($request->getContent(), true);
    $user = $this->getUser();

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
    $file->move($this->getParameter('uploads_directory'), $fullname);
    $file = $this->getParameter('uploads_directory') . '/' . $fullname;

    try {
      Configuration::instance($this->getParameter('cloudinary'));
      $result = (new UploadApi())->upload($file, [
        'public_id' => $filename,
        'use_filename' => TRUE,
        'height' => 720
      ]);

      $message = new Message();
      $message->setFromUser($user->getId());
      $message->setDiscussion($discussion);
      $message->setPicture($fullname);
      $message->setText(null);

      if ($result["width"] > $result["height"]) {
        $message->setPictureType("landscape");
      } else if ($result["width"] == $result["height"]) {
        $message->setPictureType("rounded");
      } else {
        $message->setPictureType("portrait");
      }

      $manager->persist($message);
      $manager->flush();


      // update discussion
      $discussion->setPreview("A envoyé une image");
      $discussion->setUpdatedAt(new \DateTime('now', timezone_open('UTC')));

      if ($discussion->getUser()->getId() == $user->getId()) {
        $discussion->setUnseenVendor(true);
        $name = $discussion->getVendor()->getVendor()->getPseudo();
        $receiver = $discussion->getVendor();
      } else {
        $discussion->setUnseen(true);
        $name = $user->getFullName();
        $receiver = $user;
      }

      $manager->flush();

      $data = [
        "discussionId" => $discussion->getId(),
        "message" => [
          "fromUser" => $user->getId(),
          "picture" => $message->getPicture(),
          "loading" => false,
          "pictureType" => $message->getPictureType(),
          "text" => null,
        ],
      ];

      $pusher = new \Pusher\Pusher($this->getParameter('pusher_key'), $this->getParameter('pusher_secret'), $this->getParameter('pusher_app_id'), [ 'cluster' => 'eu', 'useTLS' => true ]);
      $pusher->trigger("discussion_channel", "new_message", $data);
      $discussions = $discussionRepo->findByVendorAndUser($this->getUser());

      if ($receiver->getPushToken()) {
        try {
          $this->notifPushService->send("SWIPE LIVE", "Tu as un nouveau message de " . $name, $receiver->getPushToken());
        } catch (\Exception $error) {
          $this->bugsnag->notifyException($error);
        }
      }

      return $this->json($discussions, 200, [], [
        'groups' => 'discussion:read',
        'circular_reference_limit' => 1,
        'circular_reference_handler' => function ($object) {
          return $object->getId();
        } 
      ]);
    } catch (\Exception $e) {
      return $this->json($e->getMessage(), 404);
    }
  }

}
