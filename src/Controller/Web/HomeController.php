<?php

namespace App\Controller\Web;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class HomeController extends AbstractController {

  /**
   * @Route("/", name="landing")
   */
  public function landing(): Response{
    return $this->render('web/landing.html.twig');
  }

  /**
   * @Route("/regles-communaute", name="rules")
   */
  public function rules(): Response{
    return $this->render('web/rules.html.twig');
  }
}

