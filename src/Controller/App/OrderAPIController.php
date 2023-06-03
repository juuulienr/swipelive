<?php

namespace App\Controller\App;

use App\Entity\Clip;
use App\Entity\Live;
use App\Entity\User;
use App\Entity\Order;
use App\Entity\Vendor;
use App\Entity\Message;
use App\Entity\Product;
use App\Entity\Category;
use App\Entity\LineItem;
use App\Entity\OrderStatus;
use App\Repository\OrderStatusRepository;
use App\Repository\OrderRepository;
use App\Repository\ClipRepository;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\PromotionRepository;
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
   * Récupérer les ventes
   *
   * @Route("/user/api/orders", name="user_api_orders", methods={"GET"})
   */
  public function orders(Request $request, ObjectManager $manager, OrderRepository $orderRepo) {
    $orders = $orderRepo->findBy([ "vendor" => $this->getUser()->getVendor() ], [ "createdAt" => "DESC" ]);

    return $this->json($orders, 200, [], [
      'groups' => 'order:read'
    ]);
  }

  /**
   * Récupérer les achats
   *
   * @Route("/user/api/purchases", name="user_api_purchases", methods={"GET"})
   */
  public function purchases(Request $request, ObjectManager $manager, OrderRepository $orderRepo) {
    $orders = $orderRepo->findBy([ "buyer" => $this->getUser() ], [ "createdAt" => "DESC" ]);

    return $this->json($orders, 200, [], [
      'groups' => 'order:read'
    ]);
  }

  
  /**
   * @Route("/user/api/orders/payment", name="user_api_orders_payment", methods={"POST"})
   */
  public function payment(Request $request, ObjectManager $manager, VariantRepository $variantRepo, ProductRepository $productRepo, OrderRepository $orderRepo, PromotionRepository $promotionRepo, SerializerInterface $serializer) {
    if ($json = $request->getContent()) {
	    $param = json_decode($json, true);

	    if ($param) {
	    	$buyer = $this->getUser();
        $nbOrders = sizeof($orderRepo->findAll());
        $lineItems = $param["lineItems"];
	      $identifier = $param["identifier"];
        $promotionId = $param["promotionId"];
        $promotionAmount = $param["promotionAmount"];
        $shippingPrice = $param["shippingPrice"];
        $shippingCarrierId = $param["shippingCarrierId"];
        $shippingCarrierName = $param["shippingCarrierName"];
	      $shippingServiceId = $param["shippingServiceId"];
	      $shippingServiceName = $param["shippingServiceName"];
	      $shippingServiceCode = $param["shippingServiceCode"];
        $expectedDelivery = $param["expectedDelivery"];
        $dropoffLocationId = $param["dropoffLocationId"];
        $dropoffCountryCode = $param["dropoffCountryCode"];
        $dropoffPostcode = $param["dropoffPostcode"];
        $dropoffName = $param["dropoffName"];
        $soldOut = false;
        $totalWeight = 0;
        $subTotal = 0;

	      $order = new Order();
	      $order->setBuyer($buyer);
	      
        if ($lineItems) {
          foreach ($lineItems as $item) {
            $quantity = $item["quantity"];
            $productItem = $item["product"];
            $variantItem = $item["variant"];
            $lineItem = new LineItem();
            $soldOut = false;

            if ($productItem) {
              $product = $productRepo->findOneById($productItem["id"]);

              if ($product) {
                if ($variantItem) {
                  $variant = $variantRepo->findOneById($variantItem["id"]);

                  if ($variant && $variant->getQuantity() > 0) {
                    $weightUnit = $variant->getWeightUnit();
                    $weight = $variant->getWeight();
                    $title = $product->getTitle() . " - " . $variant->getTitle();
                    $price = $variant->getPrice();
                    $lineTotal = $variant->getPrice() * $quantity;
                    $subTotal += $lineTotal;

                    $lineItem->setVariant($variant);
                  } else {
                    $soldOut = true; 
                  }
                } elseif ($product && $product->getQuantity() > 0) {
                  $weightUnit = $product->getWeightUnit();
                  $weight = $product->getWeight();
                  $title = $product->getTitle();
                  $price = $product->getPrice();
                  $lineTotal = $product->getPrice() * $quantity;
                  $subTotal += $lineTotal;
                } else {
                  $soldOut = true; 
                }

                if (!$soldOut) {
                  if ($weightUnit == "g") {
                    $totalWeight += round($weight / 1000, 2);
                  } else {
                    $totalWeight += $weight;
                  }

                  $lineItem->setQuantity($quantity);
                  $lineItem->setProduct($product);
                  $lineItem->setPrice($price);
                  $lineItem->setTotal($lineTotal);
                  $lineItem->setTitle($title);
                  $lineItem->setOrderId($order);
                  $manager->persist($lineItem);
                  
                  $vendor = $product->getVendor();
                  $order->setVendor($vendor);
                }
              }
            } else {
              return $this->json("Le produit est obligatoire", 404); 
            }
          }

          if ($soldOut && sizeof($lineItems) == 1) {
            return $this->json("Le produit est en rupture de stock", 404); 
          }

          if ($promotionId && $promotionAmount) {
            $promotion = $promotionRepo->findOneById($promotionId);

            if ($promotion) {
              $order->setPromotionAmount($promotionAmount);
              $order->setPromotion($promotion);
            }
          } else {
            $promotionAmount = 0;
          }

  	      if (sizeof($buyer->getShippingAddresses()->toArray())) {
  		      $order->setShippingAddress($buyer->getShippingAddresses()->toArray()[0]);
  	      }

          $fees = ($subTotal - $promotionAmount) * 0.08; // commission
          $total = $subTotal - $promotionAmount + $shippingPrice;

  	      $order->setWeight($totalWeight);
  	      $order->setIdentifier($identifier);
          $order->setShippingPrice($shippingPrice);
          $order->setShippingCarrierId($shippingCarrierId);
          $order->setShippingCarrierName($shippingCarrierName);
          $order->setShippingServiceId($shippingServiceId);
          $order->setShippingServiceName($shippingServiceName);
          $order->setShippingServiceCode($shippingServiceCode);
          $order->setExpectedDelivery(new \Datetime($expectedDelivery));
          $order->setDropoffLocationId($dropoffLocationId);
          $order->setDropoffCountryCode($dropoffCountryCode);
          $order->setDropoffPostcode($dropoffPostcode);
  	      $order->setDropoffName($dropoffName);
          $order->setSubTotal($subTotal);
          $order->setTotal($total);
  	      $order->setFees($fees);
  	      $order->setShippingStatus("ready-to-send");
          $order->setStatus("created");
          $manager->flush();


          if (!$buyer->getStripeCustomer()) {
            try {
              $stripe = new \Stripe\StripeClient($this->getParameter('stripe_sk'));
              $stripeCustomer = $stripe->customers->create([
                'email' => $buyer->getEmail(),
                'name' => ucwords($buyer->getFullName()),
              ]);

              $buyer->setStripeCustomer($stripeCustomer->id);
            } catch (Exception $e) {
              return $this->json($e, 500);
            }
          }

          \Stripe\Stripe::setApiKey($this->getParameter('stripe_sk'));
          $ephemeralKey = \Stripe\EphemeralKey::create([ 'customer' => $buyer->getStripeCustomer() ], [ 'stripe_version' => '2020-08-27' ]);
          $applicationAmount = round($fees * 100) + round($shippingPrice * 100);
          $order->setNumber(1000 + $nbOrders);

          try {
            $intent = \Stripe\PaymentIntent::create([
              'amount' => round($total * 100),
              'customer' => $buyer->getStripeCustomer(),
              'currency' => 'eur',
              'automatic_payment_methods' => [
                'enabled' => 'true',
              ],
              'application_fee_amount' => $applicationAmount,
              'transfer_data' => [
                'destination' => $vendor->getStripeAcc(),
              ],
            ]);

            $array = [
              "order" => $serializer->serialize($order, "json", [ 'groups' => 'order:read']),
              "paymentConfig" => [
                "publishableKey"=> $this->getParameter('stripe_pk'),
                "companyName"=> "Swipe Live",
                "paymentIntent"=> $intent->client_secret,
                "ephemeralKey" => $ephemeralKey->secret,
                "customerId"=> $buyer->getStripeCustomer(),
                "appleMerchantId"=> "merchant.com.swipelive.app",
                "appleMerchantCountryCode"=> "FR",
                "mobilePayEnabled"=> true
              ]
            ];

            $order->setPaymentId($intent->id);
            $manager->persist($order);
            $manager->flush();

            return $this->json($array, 200);
          } catch (Exception $e) {
            return $this->json($e, 500);
          }
  		  } else {
          return $this->json("Le panier est obligatoire", 404); 
        }
      }
    }

    return $this->json(false, 404);
  }



  /**
   * Récupérer une commande
   *
   * @Route("/user/api/orders/{id}", name="user_api_order", methods={"GET"})
   */
  public function order(Order $order, Request $request, ObjectManager $manager, OrderStatusRepository $statusRepo) {
    return $this->json($order, 200, [], [
      'groups' => 'order:read', 
    ]);
  }


  /**
   * Cloturer une commande
   *
   * @Route("/user/api/orders/{id}/closed", name="user_api_order_closed", methods={"GET"})
   */
  public function closed(Order $order, Request $request, ObjectManager $manager, OrderStatusRepository $statusRepo) {
    $order->setStatus('closed');
    $manager->flush();

    // payer le vendeur
    // pending -> available

    return $this->json($order, 200, [], [
      'groups' => 'order:read', 
    ]);
  }


  /**
   * Annuler une commande
   *
   * @Route("/user/api/orders/{id}/cancel", name="user_api_order_cancel", methods={"GET"})
   */
  public function cancel(Order $order, Request $request, ObjectManager $manager, OrderStatusRepository $statusRepo) {
    try {
      $data = [
        "order_id" => $order->getIdentifier(), 
        "shipment" => [
          "service_code" => $order->getShippingServiceCode(), 
          "pickup_code" => "", 
        ]
      ]; 

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "Accept: application/json", "Authorization: Bearer JDJ5JDEzJGdLZWxFYS5TNjh3R2V4UmU3TE9nak9nWE43U3RZR0pGS0pnODRiYWowTXlnTXAuY3hScmgu"]);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
      curl_setopt($ch, CURLOPT_URL, "https://www.upelgo.com/api/carrier/cancel");

      $result = curl_exec($ch);
      $result = json_decode($result);
      curl_close($ch);

      $order->setStatus('cancelled');
      $order->setShippingStatus('cancelled');
      $manager->flush();


      // rembourser client
      $stripe = new \Stripe\StripeClient($this->getParameter('stripe_sk'));
      $stripe->refunds->create([
        'payment_intent' => $order->getPaymentId(),
      ]);

      return $this->json($order, 200, [], [
        'groups' => 'order:read', 
      ]);
      
    } catch (\Exception $e) {
      return $this->json($e->getMessage(), 404);
    }

    return $this->json(true, 200);
  }
}
