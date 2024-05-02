<?php

namespace App\Controller\Web;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use App\Service\NotifPushService;


class HomeController extends AbstractController {

  private $notifPushService;

  public function __construct(NotifPushService $notifPushService) {
      $this->notifPushService = $notifPushService;
  }


  /**
   * @Route("/", name="landing")
   */
  public function landing(){
    return $this->render('web/landing.html.twig');
  }

  /**
   * @Route("/mentions-legales", name="legal")
   */
  public function legal(){
    return $this->render('web/legal.html.twig');
  }

  /**
   * @Route("/politique-de-confidentialite", name="privacy")
   */
  public function privacy(){
    return $this->render('web/privacy.html.twig');
  }

  /**
   * @Route("/regles-communaute", name="rules")
   */
  public function rules(){
    return $this->render('web/rules.html.twig');
  }
}

