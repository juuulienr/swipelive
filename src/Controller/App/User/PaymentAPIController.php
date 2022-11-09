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
	    	$customer = $this->getUser();
	      $param["quantity"] ? $quantity = $param["quantity"] : $quantity = 1;

	      $order = new Order();
	      $order->setBuyer($customer);
	      $manager->persist($order);

	      if ($param["variant"]) {
	        $variant = $variantRepo->findOneById($param["variant"]);

	        if ($variant) {
	          $title = $variant->getProduct()->getTitle() . " - " . $variant->getTitle();
	          $total = $variant->getPrice() * $quantity;
	      		$vendor = $variant->getProduct()->getVendor();

	          $lineItem = new LineItem();
	          $lineItem->setQuantity($quantity);
	          $lineItem->setProduct($variant->getProduct());
	          $lineItem->setVariant($variant);
	          $lineItem->setPrice($variant->getPrice());
	          $lineItem->setTotal($total);
	          $lineItem->setTitle($title);
	          $lineItem->setOrderId($order);
	          $manager->persist($lineItem);

	          $order->setVendor($vendor);
	        } else {
	          return $this->json("Le variant est introuvable", 404); 
	        }
	      } elseif ($param["product"]) {
	        $product = $productRepo->findOneById($param["product"]);

	        if ($product) {
	          $title = $product->getTitle();
	          $total = $product->getPrice() * $quantity;
	      		$vendor = $product->getVendor();

	          $lineItem = new LineItem();
	          $lineItem->setQuantity($quantity);
	          $lineItem->setProduct($product);
	          $lineItem->setTitle($title);
	          $lineItem->setPrice($product->getPrice());
	          $lineItem->setTotal($total);
	          $lineItem->setOrderId($order);
	          $manager->persist($lineItem);

	          $order->setVendor($vendor);
	        } else {
	          return $this->json("Le produit est introuvable", 404); 
	        }
	      } else {
	        return $this->json("Un produit ou un variant est obligatoire", 404); 
	      }

	      $fees = str_replace('.', '', $total) * 8;
	      $amount = str_replace('.', '', $total) * 100;
	      $summary = "Quantité : " . $quantity;

	      $order->setSubTotal($total);
	      $order->setTotal($total);
	      $order->setFees($fees / 100);
	      $order->setStatus("created");
	      $manager->flush();

				return $this->json(true, 200);
		  }
		}

    return $this->json(false, 404);
  }

}
