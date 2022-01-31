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
   * @Route("/stripe", name="stripe")
   */
  public function stripe(){
    return $this->render('web/stripe.html.twig');
  }

  /**
   * @Route("/test", name="test")
   */
  public function test(Request $request){

    $stripe = new \Stripe\StripeClient('sk_test_oS3SEk3VCEWusPy8btUhcCR3');

    $response = $stripe->accounts->create(
      [
        'country' => 'FR',
        'type' => 'custom',
        'capabilities' => [
          'transfers' => ['requested' => true],
        ],
        'business_profile' => [
          'product_description' => 'Vente de produits beauté'
        ],
        'account_token' => $request->request->get('token-account'),
      ]
    );

    $token = $_POST['token-person'];
    \Stripe\Stripe::setApiKey('sk_test_oS3SEk3VCEWusPy8btUhcCR3');

    $person = \Stripe\Account::createPerson(
      $response->id, // id of the account created earlier
      [
        'person_token' => $request->request->get('token-person'),
      ]
    );

    return $this->json(true);
  }
}

