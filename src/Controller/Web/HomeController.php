<?php

namespace App\Controller\Web;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;


class HomeController extends Controller {

  /**
   * @Route("/", name="landing")
   */
  public function landing(){
    try {
    // Some potentially crashy code
      $this->createError2();
    } catch (Exception $exception) {
      $this->bugsnag->notifyException($exception, function ($report) {
        $report->setSeverity('info');
        $report->setMetaData([
          'account' => array(
            'paying' => true,
            'name' => 'Acme Co'
          )
        ]);
      });
    }


    return $this->render('web/landing.html.twig');
  }

  /**
   * @Route("/mentions-legales", name="legal")
   */
  public function legal(){
    try {
    // Some potentially crashy code
      $this->createError();
    } catch (Exception $exception) {
      $this->bugsnag->notifyError('ErrorType', 'Something bad happened', function ($report) {
        $report->setSeverity('info');
        $report->setMetaData([
          'account' => array(
            'paying' => true,
            'name' => 'Acme Co'
          )
        ]);
      });
    }

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

