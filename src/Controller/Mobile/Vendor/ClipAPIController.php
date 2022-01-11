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

class ClipAPIController extends Controller {


  /**
   * Récupérer les clips
   *
   * @Route("/vendor/api/clips", name="vendor_api_clips", methods={"GET"})
   */
  public function clips(Request $request, ObjectManager $manager, ClipRepository $clipRepo) {
    $clips = $clipRepo->retrieveClips($this->getUser());

    return $this->json($clips, 200, [], ['groups' => 'clip:read']);
  }



  /**
   * Récupérer tous les clips
   *
   * @Route("/vendor/api/clips/all", name="vendor_api_clips_all", methods={"GET"})
   */
  public function allClips(Request $request, ObjectManager $manager, ClipRepository $clipRepo) {
    $clips = $clipRepo->findByVendor($this->getUser());

    return $this->json($clips, 200, [], ['groups' => 'clip:read']);
  }


  /**
   * Récupérer les clips des abonnés
   *
   * @Route("/vendor/api/clips/following", name="vendor_api_clips_following", methods={"GET"})
   */
  public function clipsFollowing(Request $request, ObjectManager $manager, ClipRepository $clipRepo) {
    $clips = $clipRepo->findClipByFollowing($this->getUser());

    return $this->json($clips, 200, [], ['groups' => 'clip:read']);
  }


  /**
   * Ajouter un message sur un clip
   *
   * @Route("/vendor/api/clip/{id}/message/add", name="vendor_api_clip_message_add", methods={"POST"})
   */
  public function addMessage(Clip $clip, Request $request, ObjectManager $manager, SerializerInterface $serializer) {
    if ($json = $request->getContent()) {
      $param = json_decode($json, true);
      $content = $param["content"];
      $vendor = $this->getUser();

      $message = new Message();
      $message->setContent($content);
      $message->setVendor($vendor);
      $message->setClip($clip);
      
      $manager->persist($message);
      $manager->flush();

      return $this->json($clip, 200, [], ['groups' => 'clip:read'], 200);
    }
  }
}
