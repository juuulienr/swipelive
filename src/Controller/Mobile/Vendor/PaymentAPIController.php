<?php

namespace App\Controller\Mobile\Vendor;

use App\Entity\Clip;
use App\Entity\Live;
use App\Entity\User;
use App\Entity\Vendor;
use App\Entity\Message;
use App\Entity\Product;
use App\Entity\Category;
use App\Entity\Order;
use App\Entity\LineItem;
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


class PaymentAPIController extends Controller {

  /**
   * @Route("/vendor/api/payment", name="vendor_api_payment")
   */
  public function payment(Request $request, ObjectManager $manager, VariantRepository $variantRepo, ProductRepository $productRepo){
    if ($json = $request->getContent()) {
      $param = json_decode($json, true);

      if ($param) {
        $buyer = $this->getUser();
        $customer = $buyer->getStripeCus();
        $param["quantity"] ? $quantity = $param["quantity"] : $quantity = 1;

        // buyer/customer
        if (!$customer) {
          $stripe = new \Stripe\StripeClient($this->getParameter('stripe_sk'));

          $customer = $stripe->customers->create([
            'email' => $buyer->getEmail(),
            'name' => ucwords($buyer->getFullname()),
          ]);

          $customer = $customer->id;
          $buyer->setStripeCus($customer);
          $manager->flush();
        }

        $order = new Order();
        $order->setBuyer($buyer);
        $manager->persist($order);

        if ($param["variant"]) {
          $variant = $variantRepo->findOneById($param["variant"]);

          if ($variant) {
            $title = $variant->getProduct()->getTitle() . " - " . $variant->getTitle();
            $price = $variant->getPrice() * $quantity;
            $stripeAcc = $variant->getProduct()->getVendor()->getStripeAcc();

            $lineItem = new LineItem();
            $lineItem->setQuantity($quantity);
            $lineItem->setPrice($price);
            $lineItem->setProduct($variant->getProduct());
            $lineItem->setVariant($variant);
            $lineItem->setTitle($title);
            $lineItem->setOrderId($order);
            $manager->persist($lineItem);

            $order->setVendor($variant->getProduct()->getVendor());
          } else {
            return $this->json("Le variant est introuvable", 404); 
          }
        } elseif ($param["product"]) {
          $product = $productRepo->findOneById($param["product"]);

          if ($product) {
            $title = $product->getTitle();
            $price = $product->getPrice() * $quantity;
            $stripeAcc = $product->getVendor()->getStripeAcc();

            $lineItem = new LineItem();
            $lineItem->setQuantity($quantity);
            $lineItem->setPrice($price);
            $lineItem->setProduct($product);
            $lineItem->setTitle($title);
            $lineItem->setOrderId($order);
            $manager->persist($lineItem);

            $order->setVendor($product->getVendor());
          } else {
            return $this->json("Le produit est introuvable", 404); 
          }
        } else {
          return $this->json("Un produit ou un variant est obligatoire", 404); 
        }


        \Stripe\Stripe::setApiKey($this->getParameter('stripe_sk'));
        $ephemeralKey = \Stripe\EphemeralKey::create([ 'customer' => $customer ], [ 'stripe_version' => '2020-08-27' ]);

        $intent = \Stripe\PaymentIntent::create([
          'amount' => str_replace(',', '', $price) * 100,
          'customer' => $customer,
          'description' => $title,
          'currency' => 'eur',
          'automatic_payment_methods' => [
           'enabled' => 'true',
          ],
          'payment_method_options' => [
           'card' => [
              'setup_future_usage' => 'off_session',
            ],
          ],
          'application_fee_amount' => str_replace(',', '', $price) * 10,
          'transfer_data' => [
           'destination' => $stripeAcc,
          ],
        ]);

        $order->setPaymentId($intent->id);
        $order->setSubTotal($price);
        $order->setTotal($price);
        $order->setFees($price / 10);
        $order->setStatus("created");
        $manager->flush();

        $array = [
          "publishableKey"=> $this->getParameter('stripe_pk_test'),
          "companyName"=> "Swipe Live",
          "paymentIntent"=> $intent->client_secret,
          "ephemeralKey" => $ephemeralKey->secret,
          "customerId"=> $customer,
          "appleMerchantId"=> "merchant.com.swipelive.app",
          "appleMerchantCountryCode"=> "FR",
          "mobilePayEnabled"=> true
        ];

        return $this->json($array, 200);
      }
    }
    return $this->json(false, 404);
  }
}
