<?php

namespace App\Controller\Admin;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;


class DashboardController extends Controller {

  /**
   * @Route("/admin/dashboard", name="dashboard")
   */
  public function dashboard(){
    return $this->render('admin/dashboard.html.twig');
  }

  // /**
  //  * @Route("/test", name="test")
  //  */
  // public function test(){
  //   $stripe = new \Stripe\StripeClient('sk_test_oS3SEk3VCEWusPy8btUhcCR3');

  //   $stripe->accounts->update(
  //     'acct_1KUTeq2VEI63cHkr',
  //     ['settings' => ['payouts' => ['schedule' => ['interval' => 'manual']]]]
  //   );

  //   return $this->json(true);
  // }
}

