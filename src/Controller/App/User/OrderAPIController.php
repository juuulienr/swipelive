<?php

namespace App\Controller\App\User;

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
      'datetime_format' => 'd F Y à H:i' 
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
	      $param["quantity"] ? $quantity = $param["quantity"] : $quantity = 1;
	      $shippingPrice = $param["shippingPrice"];
	      $shippingName = $param["shippingName"];
	      $shippingMethodId = $param["shippingMethodId"];
	      $shippingCarrier = $param["shippingCarrier"];
	      $servicePointId = $param["servicePointId"];
	    	$weight = $param["weight"];
	    	$weightUnit = $param["weightUnit"];

	      $order = new Order();
	      $order->setBuyer($customer);
	      $manager->persist($order);
	      
	      if (!$weight || !$weightUnit) {
	        return $this->json("Le poids est obligatoire", 404); 
	      }

	      if ($weightUnit == "g") {
	      	$weight = round($weight / 1000, 2);
	      }

	      if ($param["variant"]) {
	        $variant = $variantRepo->findOneById($param["variant"]);

	        if ($variant && $variant->getQuantity() > 0) {
	          $title = $variant->getProduct()->getTitle() . " - " . $variant->getTitle();
	          $subTotal = $variant->getPrice() * $quantity;
	      		$vendor = $variant->getProduct()->getVendor();

	          $lineItem = new LineItem();
	          $lineItem->setQuantity($quantity);
	          $lineItem->setProduct($variant->getProduct());
	          $lineItem->setVariant($variant);
	          $lineItem->setPrice($variant->getPrice());
	          $lineItem->setTotal($subTotal);
	          $lineItem->setTitle($title);
	          $lineItem->setOrderId($order);
	          $manager->persist($lineItem);

	          $variant->setQuantity($variant->getQuantity() - 1);
	          $order->setVendor($vendor);
	        } else {
	          return $this->json("Le variant est introuvable", 404); 
	        }
	      } elseif ($param["product"]) {
	        $product = $productRepo->findOneById($param["product"]);

	        if ($product && $product->getQuantity() > 0) {
	          $title = $product->getTitle();
	          $subTotal = $product->getPrice() * $quantity;
	      		$vendor = $product->getVendor();

	          $lineItem = new LineItem();
	          $lineItem->setQuantity($quantity);
	          $lineItem->setProduct($product);
	          $lineItem->setTitle($title);
	          $lineItem->setPrice($product->getPrice());
	          $lineItem->setTotal($subTotal);
	          $lineItem->setOrderId($order);
	          $manager->persist($lineItem);

	          $product->setQuantity($product->getQuantity() - 1);
	          $order->setVendor($vendor);
	        } else {
	          return $this->json("Le produit est introuvable", 404); 
	        }
	      } else {
	        return $this->json("Un produit ou un variant est obligatoire", 404); 
	      }

	      $fees = $subTotal * 0.08; // commission
	      $profit = $subTotal * 0.06; // commission - frais paiement (2%)
	      $total = $subTotal + $shippingPrice;

	      if (sizeof($customer->getShippingAddresses()->toArray())) {
		      $order->setShippingAddress($customer->getShippingAddresses()->toArray()[0]);
	      }

	      $order->setWeight($weight);
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

	      $order->setNumber(100000 + sizeof($vendor->getSales()->toArray()));
	      $manager->flush();

				return $this->json(true, 200);
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
      'datetime_format' => 'd F Y à H:i' 
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
      'datetime_format' => 'd F Y à H:i' 
    ]);
  }
}
