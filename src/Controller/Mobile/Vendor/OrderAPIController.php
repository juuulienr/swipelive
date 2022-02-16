<?php

namespace App\Controller\Mobile\Vendor;

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
use App\Repository\VariantRepository;
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


class OrderAPIController extends Controller {

  /**
   * @Route("/vendor/api/orders", name="vendor_api_orders")
   */
  public function orders(Request $request, ObjectManager $manager, VariantRepository $variantRepo, ProductRepository $productRepo){
    if ($json = $request->getContent()) {
      $param = json_decode($json, true);

      if ($param) {
        $vendor = $this->getUser();
        $customer = $vendor->getStripeCus();

        if (!$customer) {
          $stripe = new \Stripe\StripeClient($this->getParameter('stripe_sk'));

          $customer = $stripe->customers->create([
            'email' => $vendor->getEmail(),
            'name' => ucwords($vendor->getFullname()),
          ]);

          $customer = $customer->id;
          $vendor->setStripeCus($customer);
          $manager->flush();
        }

        if ($param["variant"]) {
          $variant = $variantRepo->findOneById($param["variant"]);

          if ($variant) {
            $price = $variant->getPrice();
            $stripeAcc = $variant->getVendor()->getStripeAcc();
          }
        } elseif ($param["product"]) {
          $product = $productRepo->findOneById($param["product"]);

          if ($product) {
            $price = $product->getPrice();
            $stripeAcc = $product->getVendor()->getStripeAcc();
          }
        } else {
          return $this->json("Le produit est introuvable", 404); 
        }

        $array = $this->generatePaymentIntent($customer, $price, $stripeAcc);

        return $this->json($array, 200);
      }
    }
    return $this->json(false, 404);
  }


  function generatePaymentIntent($customer, $price, $stripeAcc){
    \Stripe\Stripe::setApiKey($this->getParameter('stripe_sk'));
    $ephemeralKey = \Stripe\EphemeralKey::create([ 'customer' => $customer ], [ 'stripe_version' => '2020-08-27' ]);
    $intent = \Stripe\PaymentIntent::create([
      'amount' => $price * 100,
      'customer' => $customer,
      'currency' => 'eur',
      'automatic_payment_methods' => [
       'enabled' => 'true',
      ],
      'payment_method_options' => [
       'card' => [
          'setup_future_usage' => 'off_session',
        ],
      ],
      'application_fee_amount' => $price * 10,
      'transfer_data' => [
       'destination' => $stripeAcc,
      ],
    ]);

    $array = [
      "publishableKey"=> $this->getParameter('stripe_pk'),
      "companyName"=> "Swipe Live",
      "paymentIntent"=> $intent->client_secret,
      "ephemeralKey" => $ephemeralKey->secret,
      "customerId"=> $customer,
      "appleMerchantId"=> "merchant.com.swipelive.app",
      "appleMerchantCountryCode"=> "FR",
      "mobilePayEnabled"=> true
    ];

    return $array;
  }
}
