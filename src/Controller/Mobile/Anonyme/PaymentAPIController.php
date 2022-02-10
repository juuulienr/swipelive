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
  public function paymentIntent(Request $request){
    if ($json = $request->getContent()) {
      $param = json_decode($json, true);

      if ($param) {

        $stripe = new \Stripe\StripeClient(
          'sk_live_dNOTznFTks1nDNJjfzd5yzYs'
        );

        $customer = $stripe->customers->create([
          'email' => $param['email'],
          'name' => $param['name'],
        ]);

     // \Stripe\Stripe::setApiKey('sk_test_oS3SEk3VCEWusPy8btUhcCR3');
        \Stripe\Stripe::setApiKey('sk_live_dNOTznFTks1nDNJjfzd5yzYs');

        $ephemeralKey = \Stripe\EphemeralKey::create([ 'customer' => $customer->id ], [ 'stripe_version' => '2020-08-27' ]);

          
        $intent = \Stripe\PaymentIntent::create([
          // 'amount' => $param['variant']['price'],
          'amount' => 500,
          'customer' => $customer->id,
          'currency' => 'eur',
          'automatic_payment_methods' => [
           'enabled' => 'true',
         ],
         'payment_method_options' => [
           'card' => [
              'setup_future_usage' => 'off_session',
            ],
          ],
          // 'application_fee_amount' => 500 * 0.1,
          // 'transfer_data' => [
          //  'destination' => 'acct_1KMvY32YfkHlUvQi',
          // ],
        ]);

      $array = [
          "publishableKey"=> "pk_live_KGjyLVjmMB3WnzLBitoNtsKC",
          "companyName"=> "Swipe Live",
          "paymentIntent"=> $intent->client_secret,
          "ephemeralKey" => $ephemeralKey->secret,
          "customerId"=> $customer->id,
          "appleMerchantId"=> "merchant.com.swipelive.app",
          "appleMerchantCountryCode"=> "FR",
          "mobilePayEnabled"=> true
        ];

        return $this->json($array, 200);
      }
    }
    return $this->json(false, 404);
  }

  /**
   * @Route("/payment/status", name="payment_status")
   */
  public function status(){

  	return $this->render('web/status.html.twig');
  }
}
