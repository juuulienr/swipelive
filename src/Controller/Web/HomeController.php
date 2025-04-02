<?php

namespace App\Controller\Web;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class HomeController extends AbstractController {

  /**
   * @Route("/", name="landing")
   */
  public function landing(){
    return $this->render('web/landing.html.twig');
  }

  /**
   * @Route("/regles-communaute", name="rules")
   */
  public function rules(){
    return $this->render('web/rules.html.twig');
  }
}

