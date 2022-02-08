<?php

namespace App\Controller\Mobile\Anonyme;

use App\Entity\Clip;
use App\Entity\Live;
use App\Entity\User;
use App\Entity\Vendor;
use App\Entity\Message;
use App\Entity\Product;
use App\Entity\Category;
use App\Repository\ClipRepository;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\LiveRepository;
use App\Repository\LiveProductsRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Config\Definition\Exception\Exception;


class PaymentAPIController extends Controller {

  /**
   * @Route("/api/payment/intent", name="api_payment_intent")
   */
  public function paymentIntent(){
  	// \Stripe\Stripe::setApiKey('sk_test_oS3SEk3VCEWusPy8btUhcCR3');
    \Stripe\Stripe::setApiKey('sk_live_dNOTznFTks1nDNJjfzd5yzYs');

  	$intent = \Stripe\PaymentIntent::create([
  		'amount' => 1000,
  		'currency' => 'eur',
  		'automatic_payment_methods' => [
  			'enabled' => 'true',
  		],
  		// 'payment_method_options' => [
  		// 	'card' => [
  		// 		'setup_future_usage' => 'off_session',
  		// 	],
  		// ],
  		// 'application_fee_amount' => 1000 * 0.1,
  		// 'transfer_data' => [
  			// 'destination' => 'acct_1KMvY32YfkHlUvQi',
  		// ],
  	]);

    // créer et enregistrer une commande

    return $this->json($intent->client_secret, 200);
  }

  /**
   * @Route("/payment/status", name="payment_status")
   */
  public function status(){

  	return $this->render('web/status.html.twig');
  }
}
