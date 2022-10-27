<?php

namespace App\Controller\App\User;

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
   * @Route("/user/api/payment", name="user_api_payment")
   */
  public function payment(Request $request, ObjectManager $manager, VariantRepository $variantRepo, ProductRepository $productRepo) {
    if ($json = $request->getContent()) {
	    $param = json_decode($json, true);

	    if ($param) {
	      $buyer = $this->getUser();
	      $customer = $buyer->getStripeCustomer();
	      $param["quantity"] ? $quantity = $param["quantity"] : $quantity = 1;

	      $order = new Order();
	      $order->setBuyer($buyer);
	      $manager->persist($order);

	      if ($param["variant"]) {
	        $variant = $variantRepo->findOneById($param["variant"]);

	        if ($variant) {
	          $title = $variant->getProduct()->getTitle() . " - " . $variant->getTitle();
	          $total = $variant->getPrice() * $quantity;
	          // $stripeAcc = $variant->getProduct()->getVendor()->getStripeAcc();

	          $lineItem = new LineItem();
	          $lineItem->setQuantity($quantity);
	          $lineItem->setProduct($variant->getProduct());
	          $lineItem->setVariant($variant);
	          $lineItem->setPrice($variant->getPrice());
	          $lineItem->setTotal($total);
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
	          $total = $product->getPrice() * $quantity;
	          // $stripeAcc = $product->getVendor()->getStripeAcc();

	          $lineItem = new LineItem();
	          $lineItem->setQuantity($quantity);
	          $lineItem->setProduct($product);
	          $lineItem->setTitle($title);
	          $lineItem->setPrice($product->getPrice());
	          $lineItem->setTotal($total);
	          $lineItem->setOrderId($order);
	          $manager->persist($lineItem);

	          $order->setVendor($product->getVendor());
	        } else {
	          return $this->json("Le produit est introuvable", 404); 
	        }
	      } else {
	        return $this->json("Un produit ou un variant est obligatoire", 404); 
	      }


	      $fees = 70 + str_replace(',', '', $total) * 8;

	      $intent = \Stripe\PaymentIntent::create([
	        'amount' => str_replace(',', '', $total) * 100,
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
	        'application_fee_amount' => $fees,
	        'transfer_data' => [
	         'destination' => $stripeAcc,
	        ],
	      ]);

	      $profit = $fees - (25 + str_replace(',', '', $total) * 1.4);

	      $order->setPaymentId($intent->id);
	      $order->setSubTotal($total);
	      $order->setTotal($total);
	      $order->setFees($fees / 100);
	      $order->setProfit($profit / 100);
	      $order->setStatus("created");
	      $manager->flush();


		  	$ch = curl_init();
		  	$data = [
		  		'type' => 'checkout',
		  		'currency' => 'eur',
		  		'from' => [ 
		  			'email'=> 'julienreignierr@gmail.com',
		  			'name'=> 'Julien REIGNIER',
		  			'type'=> 'individual',
		  		],
		  		'settlements' => [
		  			[
		  				'type' => 'escrow',
		  				'to' => [ 'email'=> 'seller@lumber.com', 'name' => 'Vendeur 1' ],
		  				'description' => 'Gamme Coco Youarda.C',
		  				'amount' => 7500,
		  				'fee_percentage' => 0.08,
		  			],
		  			[
		  				'type' => 'escrow',
		  				'to' => [ 'email'=> 'seller@bricks.com', 'name' => 'Vendeur 2'  ],
		  				'description' => 'Gamme Caviar Youarda.C',
		  				'summary' => 'Quantity: 2 - Poids: 1kg',
		  				'amount' => 2500,
		  				'fee_flat' => 50,
		  			]
		  		]
		  	];

		  	try {
		  		curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "Authorization: sandbox_api_m5dZIkcoIqZ960aek04bWNJNGSpVAZmQMkLZbnbFC44BWP5ixYq6LKeSCHFCqPO0"]);
		  		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		  		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
		  		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		  		curl_setopt($ch, CURLOPT_URL, "https://rest.trustshare.io/v1/intents/payment");

		  		$result = curl_exec($ch);
		  		$result = json_decode($result);
		  		curl_close($ch);

		  		if ($result && $result->client_secret) {
		  			return $this->json($result->client_secret, 200);
		  		} else {
		  			return $this->json("Une erreur est survenue", 404);
		  		}
		  	} catch (Exception $e) {
		  		return $this->json($e->getMessage(), 404);
		  	}


	  	// $ch = curl_init();
	  	// $data = [ 'id' => 'participant_wvpE3Zqm46' ];

	  	// try {
	  	// 	curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "Authorization: sandbox_api_m5dZIkcoIqZ960aek04bWNJNGSpVAZmQMkLZbnbFC44BWP5ixYq6LKeSCHFCqPO0"]);
	  	// 	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	  	// 	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
	  	// 	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
	  	// 	curl_setopt($ch, CURLOPT_URL, "https://rest.trustshare.io/v1/verifications");

	  	// 	$result = curl_exec($ch);
	  	// 	$result = json_decode($result);
	  	// 	curl_close($ch);

	  	// 	if ($result && $result->client_secret) {
	  	// 		return $this->json($result->client_secret, 200);
	  	// 	} else {
	  	// 		return $this->json("Une erreur est survenue", 404);
	  	// 	}
	  	// } catch (Exception $e) {
	  	// 	return $this->json($e->getMessage(), 404);
	  	// }
	    
		  }
		}
		  
    return $this->json(false, 404);
  }
}
