<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;

class UserAPIController extends Controller {

  // /**
  //  * @Route("/user/api/push/add", name="user_api_push_add")
  //  */
  // public function addPush(Request $request, ObjectManager $manager)
  // {
  //   $user = $this->getUser(); $token = [];

  //   // récupérer le push token
  //   if ($content = $request->getContent()) {
  //     $token = json_decode($content, true);
  //     if ($token) {
  //       $user->setPushToken($token['token']);
  //       $manager->flush();

  //       return $this->json(true, 200);
  //     }
  //   }

  //   return $this->json("Le token est introuvable", 404);
  // }


  // /**
  //  * @Route("/user/api/profile", name="user_api_profile", methods={"GET"})
  //  */
  // public function profile(Request $request, ObjectManager $manager)
  // {
  //   return $this->json($this->getUser(), 200, [], ['groups' => 'user:read']);
  // }
}
