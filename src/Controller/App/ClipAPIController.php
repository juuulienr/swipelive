<?php

namespace App\Controller\App;

use App\Entity\Vendor;
use App\Entity\Clip;
use App\Entity\Live;
use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\Follow;
use App\Entity\Product;
use App\Entity\LiveProducts;
use App\Entity\Upload;
use App\Repository\FollowRepository;
use App\Repository\VendorRepository;
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

class ClipAPIController extends AbstractController {


  /**
   * Récupérer tous les clips
   *
   * @Route("/user/api/clips", name="user_api_clips", methods={"GET"})
   */
  public function clips(Request $request, ObjectManager $manager, ClipRepository $clipRepo) {
    $clips = $clipRepo->findByVendor($this->getUser()->getVendor());

    return $this->json($clips, 200, [], [
    	'groups' => 'clip:read', 
    	'circular_reference_limit' => 1, 
    	'circular_reference_handler' => function ($object) {
    		return $object->getId();
    	} 
    ]);
  }

  /**
   * Ajouter un comment sur un clip
   *
   * @Route("/user/api/clip/{id}/comment/add", name="user_api_clip_comment_add", methods={"POST"})
   */
  public function addComment(Clip $clip, Request $request, ObjectManager $manager, SerializerInterface $serializer) {
    if ($json = $request->getContent()) {
      $param = json_decode($json, true);
      $content = $param["content"];
      $user = $this->getUser();

      $comment = new Comment();
      $comment->setContent($content);
      $comment->setUser($user);
      $comment->setClip($clip);

      if ($user->getVendor() && $user->getVendor()->getPseudo() == $clip->getVendor()->getPseudo()) {
        $comment->setIsVendor(true);
      }
      
      $manager->persist($comment);
      $manager->flush();

	    return $this->json($clip, 200, [], [
	    	'groups' => 'clip:read', 
	    	'circular_reference_limit' => 1, 
	    	'circular_reference_handler' => function ($object) {
	    		return $object->getId();
	    	} 
	    ]);
    }
  }

  /**
   * Mettre à jour les likes
   *
   * @Route("/user/api/clips/{id}/update/likes", name="user_api_clips_update_likes", methods={"PUT"})
   */
  public function updateLikes(Clip $clip, Request $request, ObjectManager $manager, SerializerInterface $serializer) {
    $clip->setTotalLikes($clip->getTotalLikes() + 1);
    $manager->flush();

    return $this->json(true, 200);
  }


  /**
   * Supprimer un clip
   *
   * @Route("/user/api/clips/{id}/delete", name="user_api_clips_delete", methods={"GET"})
   */
  public function delete(Clip $clip, Request $request, ObjectManager $manager, ClipRepository $clipRepo) {
  	$live = $clip->getLive();
  	$comments = $clip->getComments();

  	if ($comments) {
  		foreach ($comments as $comment) {
  			$manager->remove($comment);
  		}
  		$manager->flush();
  	}

    $manager->remove($clip);
    $manager->flush();

  	if (!sizeof($live->getClips())) {
      $liveProducts = $live->getLiveProducts();
  		$comments = $live->getComments();

	  	if ($liveProducts) {
	  		foreach ($liveProducts as $liveProduct) {
	  			$manager->remove($liveProduct);
	  		}
	  		$manager->flush();
	  	}

	  	if ($comments) {
	  		foreach ($comments as $comment) {
	  			$manager->remove($comment);
	  		}
	  		$manager->flush();
	  	}

      $manager->remove($live);
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
