<?php

namespace App\Controller\Web;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Persistence\ObjectManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Cookie;


class HomeController extends Controller {

  /**
   * @Route("/", name="home")
   */
  public function home(){
    return $this->render('web/home.html.twig');
  }

  /**
   * @Route("/vendeur", name="vendor")
   */
  public function vendor(){
    return $this->render('web/vendor.html.twig');
  }

  /**
   * @Route("/influenceur", name="influencer")
   */
  public function influencer(){
    return $this->render('web/influencer.html.twig');
  }

  /**
   * @Route("/termes-et-conditions", name="terms")
   */
  public function terms(){
    return $this->render('web/terms.html.twig');
  }

  /**
   * @Route("/politique-de-confidentialite", name="privacy")
   */
  public function privacy(){
    return $this->render('web/privacy.html.twig');
  }

  /**
   * @Route("/politique-de-cookies", name="cookies")
   */
  public function cookies(){
    return $this->render('web/cookies.html.twig');
  }

  /**
   * @Route("/test", name="test")
   */
  public function test(){
    $stripe = new \Stripe\StripeClient('sk_test_oS3SEk3VCEWusPy8btUhcCR3');

    $stripe->accounts->update(
      'acct_1KUTeq2VEI63cHkr',
      ['settings' => ['payouts' => ['schedule' => ['interval' => 'manual']]]]
    );

    return $this->json(true);
  }
}

