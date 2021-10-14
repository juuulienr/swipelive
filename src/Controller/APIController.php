<?php

namespace App\Controller;

use App\Entity\Live;
use App\Entity\User;
use App\Entity\Vendor;
use App\Entity\Message;
use App\Repository\ClipRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;

class APIController extends Controller {


  /**
   * @Route("/api/feed", name="api_feed", methods={"GET"})
   */
  public function feed(Request $request, ObjectManager $manager, ClipRepository $clipRepo)
  {
    $clips = $clipRepo->findAll();

    return $this->json($clips, 200, [], ['groups' => 'clip:read']);
  }


  /**
   * @Route("/api/live/{id}/messages", name="api_live_messages", methods={"GET"})
   */
  public function messages(Live $live, Request $request, ObjectManager $manager)
  {
    $messages = $live->getMessages();

    return $this->json($messages, 200, [], ['groups' => 'message:read']);
  }

  /**
   * @Route("/api/profile/{id}", name="api_profile", methods={"GET"})
   */
  public function profile(Vendor $vendor, Request $request, ObjectManager $manager)
  {
    return $this->json($vendor, 200, [], ['groups' => 'vendor:read']);
  }
}
