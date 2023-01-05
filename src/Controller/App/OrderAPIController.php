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
    $orders = $orderRepo->findByVendorOrBuyer($this->getUser());

    return $this->json($orders, 200, [], [
      'groups' => 'order:read', 
      'datetime_format' => 'd/m/Y H:i' 
    ]);
  }

  
  /**
   * @Route("/user/api/orders/payment/success", name="user_api_orders_success")
   */
  public function success(Request $request, ObjectManager $manager, VariantRepository $variantRepo, ProductRepository $productRepo) {
    if ($json = $request->getContent()) {
	    $param = json_decode($json, true);

	    if ($param) {
	    	$customer = $this->getUser();
        $lineItems = $param["lineItems"];
	      $shippingPrice = $param["shippingPrice"];
	      $shippingName = $param["shippingName"];
	      $shippingMethodId = $param["shippingMethodId"];
	      $shippingCarrier = $param["shippingCarrier"];
	      $servicePointId = $param["servicePointId"];
        $totalWeight = 0;
        $subTotal = 0;

	      $order = new Order();
	      $order->setBuyer($customer);
	      $manager->persist($order);
	      
	      if (!$lineItems) {
	        return $this->json("Un produit est obligatoire !", 404); 
	      }

        foreach ($lineItems as $lineItem) {
          if ($lineItem["variant"]) {
            $variant = $variantRepo->findOneById($lineItem["variant"]);
            $product = $productRepo->findOneById($lineItem["product"]);

            if ($variant && $variant->getQuantity() > 0) {
              $weightUnit = $lineItem["variant"]["weightUnit"];
              $weight = $lineItem["variant"]["weight"];
              $quantity = $lineItem["quantity"];
              $title = $product->getTitle() . " - " . $variant->getTitle();
              $lineTotal = $variant->getPrice() * $quantity;
              $vendor = $product->getVendor();
              $subTotal += $lineTotal;

              if ($weightUnit == "g") {
                $totalWeight += round($weight / 1000, 2);
              } else {
                $totalWeight += $weight;
              }

              $lineItem = new LineItem();
              $lineItem->setQuantity($quantity);
              $lineItem->setProduct($product);
              $lineItem->setVariant($variant);
              $lineItem->setPrice($variant->getPrice());
              $lineItem->setTotal($lineTotal);
              $lineItem->setTitle($title);
              $lineItem->setOrderId($order);
              $manager->persist($lineItem);

              $variant->setQuantity($variant->getQuantity() - 1);
              $order->setVendor($vendor);
            } else {
              return $this->json("Stock épuisé", 404); 
            }

          } elseif ($lineItem["product"]) {
            $product = $productRepo->findOneById($lineItem["product"]);

            if ($product && $product->getQuantity() > 0) {
              $weightUnit = $lineItem["product"]["weightUnit"];
              $weight = $lineItem["product"]["weight"];
              $quantity = $lineItem["quantity"];
              $title = $product->getTitle();
              $lineTotal = $product->getPrice() * $quantity;
              $vendor = $product->getVendor();
              $subTotal += $lineTotal;

              if ($weightUnit == "g") {
                $totalWeight += round($weight / 1000, 2);
              } else {
                $totalWeight += $weight;
              }

              $lineItem = new LineItem();
              $lineItem->setQuantity($quantity);
              $lineItem->setProduct($product);
              $lineItem->setTitle($title);
              $lineItem->setPrice($product->getPrice());
              $lineItem->setTotal($lineTotal);
              $lineItem->setOrderId($order);
              $manager->persist($lineItem);

              $product->setQuantity($product->getQuantity() - 1);
              $order->setVendor($vendor);
            } else {
              return $this->json("Le produit est introuvable", 404); 
            }
          }
        }

	      $fees = $subTotal * 0.09; // commission
	      $profit = $subTotal * 0.06; // commission - frais paiement (2%)
	      $total = $subTotal + $shippingPrice;

	      if (sizeof($customer->getShippingAddresses()->toArray())) {
		      $order->setShippingAddress($customer->getShippingAddresses()->toArray()[0]);
	      }

	      $order->setWeight($totalWeight);
	      $order->setSubTotal($subTotal);
	      $order->setShippingPrice($shippingPrice);
	      $order->setShippingName($shippingName);
	      $order->setShippingMethodId($shippingMethodId);
	      $order->setShippingCarrier($shippingCarrier);
	      $order->setServicePointId($servicePointId);
	      $order->setTotal($total);
	      $order->setFees($fees);
	      $order->setProfit($profit);
	      $order->setShippingStatus("ready-to-send");
        $order->setPaymentStatus("paid");
        $order->setStatus("open");
	      $manager->flush();

	      $order->setNumber(1000 + sizeof($vendor->getSales()->toArray()));
	      $manager->flush();

        return $this->json($order, 200, [], [
          'groups' => 'order:read', 
          'datetime_format' => 'd/m/Y H:i' 
        ]);
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
    if ($order->getTrackingNumber()) {
      $url = "https://panel.sendcloud.sc/api/v2/tracking/" . $order->getTrackingNumber();
      $curl = curl_init();

      curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => [
          "Authorization: Basic MzgyNjY4NmYyZGJjNDE4MzgwODk4Y2MyNTRmYzBkMjg6MDk2ZTQ0Y2I5YjI2NDMxYjkwY2M1YjVkZWZjOWU5MTU=",
          "Content-Type: application/json"
        ],
      ]);

      $response = curl_exec($curl);
      $result = json_decode($response);
      curl_close($curl);

      if ($result && array_key_exists("expected_delivery_date", $result)) {
        $order->setExpectedDelivery(new \Datetime($result->expected_delivery_date));
        $manager->flush();

        foreach ($result->statuses as $status) {
          $orderStatus = $statusRepo->findOneByStatusId($status->parcel_status_history_id);

          if (!$orderStatus) {
            $orderStatus = new OrderStatus();
            $orderStatus->setUpdateAt(new \Datetime($status->carrier_update_timestamp));
            $orderStatus->setMessage($status->carrier_message);
            $orderStatus->setStatus($status->parent_status);
            $orderStatus->setCode($status->carrier_code);
            $orderStatus->setStatusId($status->parcel_status_history_id);
            $orderStatus->setShipping($order);
            $order->setShippingStatus($status->parent_status);
            $order->setUpdatedAt(new \Datetime($status->carrier_update_timestamp));
            
            $manager->persist($orderStatus);
            $manager->flush();
          }
        }
      }
    }

    return $this->json($order, 200, [], [
      'groups' => 'order:read', 
      'datetime_format' => 'd/m/Y H:i' 
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
      'datetime_format' => 'd/m/Y H:i' 
    ]);
  }
}
