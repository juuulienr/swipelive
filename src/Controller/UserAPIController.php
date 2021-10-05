<?php

namespace App\Controller\User;

use App\Entity\User;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;

class UserAPIController extends Controller {


  /**
   * @Route("/api/push/add", name="user_push_add")
   */
  public function addPush(Request $request, ObjectManager $manager)
  {
    $user = $this->getUser(); $token = [];

    // récupérer le push token
    if ($content = $request->getContent()) {
      $token = json_decode($content, true);
      if ($token) {
        $user->setPushToken($token['token']);
        $manager->flush();

        return $this->json(true, 200);
      }
    }

    return $this->json("Le token est introuvable", 404);
  }

  
  /**
   * @Route("/api/dashboard", name="user_api_dashboard")
   */
  public function apiDashboard(ObjectManager $manager, UserRepository $usersRepo, InfoRepository $infoRepo, ResellerRepository $resellerRepo)
  {
    $info = $infoRepo->findOneById(1);
    $resellers = $resellerRepo->findAll();

    $array = [
      "info" => $info,
      "resellers" => $resellers
    ];

    return $this->json($array, 200);
  }


  public function dateFilter($date) {
    $now = new \DateTime('now', timezone_open('Europe/Paris'));

    $diff = $now->diff($date);

    if ($diff->format('%y') > 0) {
        if ($diff->format('%y') > 1) {
            return 'il y a ' . $diff->format('%y') . ' ans';
        } 
        return 'il y a ' . $diff->format('%y') . ' an';

    } else if ($diff->format('%m') > 0) {
        return 'il y a ' . $diff->format('%m') . ' mois';

    } else if ($diff->format('%a') > 0) {
        if ($diff->format('%a') > 1) {
            return 'il y a ' . $diff->format('%a') . ' jours';
        } 
        return 'il y a ' . $diff->format('%a') . ' jour';

    } else if ($diff->format('%h') > 0) {
        if ($diff->format('%h') > 1) {
            return 'il y a ' . $diff->format('%h') . ' heures';
        } 
        return 'il y a ' . $diff->format('%h') . ' heure';

    } else if ($diff->format('%i') > 0) {
        if ($diff->format('%i') > 1) {
            return 'il y a ' . $diff->format('%i') . ' minutes';
        } 
        return 'il y a ' . $diff->format('%i') . ' minute';

    }else if ($diff->format('%s') > 0) {
        if ($diff->format('%s') > 1) {
            return 'il y a ' . $diff->format('%s') . ' secondes';
        } 
        return 'il y a ' . $diff->format('%s') . ' seconde';

    }
  }
}
