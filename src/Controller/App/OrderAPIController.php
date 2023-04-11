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
   * Récupérer les commandes
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
   * @Route("/user/api/orders/payment/success", name="user_api_orders_success", methods={"POST"})
   */
  public function success(Request $request, ObjectManager $manager, VariantRepository $variantRepo, ProductRepository $productRepo, OrderRepository $orderRepo) {
    if ($json = $request->getContent()) {
	    $param = json_decode($json, true);

	    if ($param) {
	    	$customer = $this->getUser();
        $nbOrders = sizeof($orderRepo->findAll());
        $lineItems = $param["lineItems"];
	      $identifier = $param["identifier"];
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
	      $order->setBuyer($customer);
	      $manager->persist($order);
	      
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

                    $variant->setQuantity($variant->getQuantity() - $quantity);
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
               
                  $product->setQuantity($product->getQuantity() - $quantity);
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
                  
                  $order->setVendor($product->getVendor());
                }
              }
            } else {
              return $this->json("Le produit est obligatoire", 404); 
            }
          }

          if ($soldOut && sizeof($lineItems) == 1) {
            return $this->json("Le produit est en rupture de stock", 404); 
          }
          
  	      $fees = $subTotal * 0.09; // commission
  	      $profit = $subTotal * 0.06; // commission - frais paiement (3%)
  	      $total = $subTotal + $shippingPrice;

  	      if (sizeof($customer->getShippingAddresses()->toArray())) {
  		      $order->setShippingAddress($customer->getShippingAddresses()->toArray()[0]);
  	      }

  	      $order->setWeight($totalWeight);
  	      $order->setSubTotal($subTotal);
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
          $order->setTotal($total);
  	      $order->setFees($fees);
  	      $order->setProfit($profit);
  	      $order->setShippingStatus("ready-to-send");
          $order->setPaymentStatus("paid");
          $order->setStatus("open");
  	      $manager->flush();

  	      $order->setNumber(1000 + $nbOrders);
  	      $manager->flush();

          return $this->json($order, 200, [], [
            'groups' => 'order:read', 
          ]);
  		  } else {
          return $this->json("Le panier est obligatoire", 404); 
        }
      }
    }

    return $this->json(false, 404);
  }

  
  /**
   * @Route("/user/api/payment/intent", name="user_api_payment_intent", methods={"GET"})
   */
  public function intent(Request $request, ObjectManager $manager, OrderRepository $orderRepo) {
    \Stripe\Stripe::setApiKey($this->getParameter('stripe_sk'));

    // $customer = \Stripe\Customer::create();

    // $stripe = new \Stripe\StripeClient($this->getParameter('stripe_sk'));

    // $customer = $stripe->customers->create([
    //   'email' => $buyer->getEmail(),
    //   'name' => ucwords($buyer->getFullName()),
    // ]);

    $intent = \Stripe\PaymentIntent::create([
      'amount' => 10000,
      'customer' => "cus_LdHzF3Snr0mzf1",
      'description' => "Test",
      'currency' => 'eur',
      'automatic_payment_methods' => [
         'enabled' => 'true',
       ],
       'payment_method_options' => [
         'card' => [
          'setup_future_usage' => 'off_session',
          ],
        ],
        'application_fee_amount' => 1000,
        'transfer_data' => [
         'destination' => "acct_1LttLoFZcx4zHjJa",
       ],
     ]);

    if ($intent) {
      return $this->json([ "clientSecret" => $intent->client_secret ], 200);
    }
    return $this->json(false, 404);
  }


  
  /**
   * @Route("/user/api/payment/intent/update", name="user_api_payment_intent_update", methods={"GET"})
   */
  public function update(Request $request, ObjectManager $manager, OrderRepository $orderRepo) {
    \Stripe\Stripe::setApiKey($this->getParameter('stripe_sk'));

    $intent = $stripe->paymentIntents->update(
      '{{PAYMENT_INTENT_ID}}',
      ['amount' => 1499]
    );

    if ($intent) {
      return $this->json([ 'status' => $intent->status ], 200);
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

      if ($result->success) {
        $order->setStatus('cancel');
        $manager->flush();

        return $this->json($order, 200, [], [
          'groups' => 'order:read', 
        ]);
      }
    } catch (\Exception $e) {
      return $this->json($e->getMessage(), 404);
    }

    return $this->json(true, 200);
  }
}
