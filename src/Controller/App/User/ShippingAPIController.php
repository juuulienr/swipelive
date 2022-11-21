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
use App\Entity\ShippingAddress;
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
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;


class ShippingAPIController extends Controller {


  /**
   * @Route("/user/api/shipping/price", name="user_api_shipping_price")
   */
  public function shippingPrice(Request $request, ObjectManager $manager, VariantRepository $variantRepo, ProductRepository $productRepo) {
    if ($json = $request->getContent()) {
	    $param = json_decode($json, true);

	    if ($param) {
	    	$weight = $param["weight"];
	    	$weightUnit = $param["weightUnit"];
	    	$to_country = $param["countryShort"];

	      if (!$weight || !$weightUnit) {
	        return $this->json("Le poids est obligatoire", 404); 
	      }

	      if ($weightUnit == "kg") {
	      	$weight = $weight * 1000;
	      } else {
	      	$weight = round($weight);
	      }

	      try {
	      	$params = [
	      		"from_country" => "FR",
	      		"to_country" => $to_country,
	      		"weight" => $weight,
	      		"weight_unit" => "gram"
	      	];
	      	$url = "https://panel.sendcloud.sc/api/v2/shipping-products" . '?' . http_build_query($params);
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
	      	curl_close($curl);

	      	$shippingProducts = json_decode($response);
	      	$array = [];

	      	if ($shippingProducts) {
	      		foreach ($shippingProducts as $value) {
	      			foreach ($value->methods as $method) {
	      				if (str_contains($method->name, 'Chrono Shop2Shop') || str_contains($method->name, 'Mondial Relay Point Relais') || (str_contains($method->name, 'Colissimo Home') && !str_contains($method->name, 'Colissimo Home Signature'))) {
	      					if (str_contains($method->name, 'Colissimo Home')) {

	      						$params = [
	      							"from_country" => "FR",
	      							"to_country" => $to_country,
	      							"weight" => $weight,
	      							"weight_unit" => "gram",
	      							"shipping_method_id" => $method->id 
	      						];

	      						$url = "https://panel.sendcloud.sc/api/v2/shipping-price" . '?' . http_build_query($params);
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
	      						curl_close($curl);

	      						$result = json_decode($response);

	      						$array["domicile"][] = [ 
	      							"id" => $method->id,
	      							"carrier" => $value->carrier,
	      							"name" => $value->name,
	      							"price" => $result[0]->price,
	      							"currency" => $result[0]->currency
	      						];

	      					} else {

	      						$params = [
	      							"from_country" => "FR",
	      							"to_country" => $to_country,
	      							"weight" => $weight,
	      							"weight_unit" => "gram",
	      							"shipping_method_id" => $method->id 
	      						];

	      						$url = "https://panel.sendcloud.sc/api/v2/shipping-price" . '?' . http_build_query($params);
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
	      						curl_close($curl);

	      						$result = json_decode($response);

	      						$array["service_point"][] = [ 
	      							"id" => $method->id,
	      							"carrier" => $value->carrier,
	      							"name" => $value->name,
	      							"price" => $result[0]->price,
	      							"currency" => $result[0]->currency
	      						];
	      					}
	      				}
	      			}
	      		}
	      	}

	      	return $this->json($array, 200);
	      } catch (Exception $e) {
	      	return $this->json($e, 500);
	      }
			}
		}
    
    return $this->json(false, 404);
  }


  /**
   * Ajouter une adresse
   *
   * @Route("/user/api/shipping/address", name="user_api_shipping_address", methods={"POST"})
   */
  public function addShippingAddress(Request $request, ObjectManager $manager, SerializerInterface $serializer) {
    if ($json = $request->getContent()) {
      $shippingAddress = $serializer->deserialize($json, ShippingAddress::class, "json");
      $shippingAddress->setUser($this->getUser());

      $manager->persist($shippingAddress);
      $manager->flush();

      return $this->json(true, 200);
    }

    return $this->json([ "error" => "Une erreur est survenue"], 404);
  }


  /**
   * Editer une adresse
   *
   * @Route("/user/api/shipping/address/edit/{id}", name="user_api_shipping_address_edit", methods={"POST"})
   */
  public function editShippingAddress(ShippingAddress $shippingAddress, Request $request, ObjectManager $manager, SerializerInterface $serializer) {
    if ($json = $request->getContent()) {
      $serializer->deserialize($json, ShippingAddress::class, "json", [AbstractNormalizer::OBJECT_TO_POPULATE => $shippingAddress]);
      $manager->flush();

      return $this->json(true, 200);
    }

    return $this->json([ "error" => "Une erreur est survenue"], 404);
  }


  /**
   * @Route("/user/api/shipping/create/{id}", name="user_api_create")
   */
  public function shipping(Order $order, Request $request, ObjectManager $manager) {
  	$shippingAddress = $order->getShippingAddress();
  	$vendor = $order->getVendor();

  	if ($vendor->getBusinessType() == "company") {
  		$companyName = $vendor->getCompany();
  	} else {
  		$companyName = "";
  	}

  	try {
  		$data = [
  			"parcel" => [
  				"name" => $order->getBuyer()->getFullName(), 
  				"address" => $shippingAddress->getAddress(), 
  				"house_number" => $shippingAddress->getHouseNumber(), 
  				"city" => $shippingAddress->getCity(), 
  				"postal_code" => $shippingAddress->getZip(), 
  				"country" => $shippingAddress->getCountryCode(), 
  				"telephone" => $shippingAddress->getPhone(), 
  				"email" => $order->getBuyer()->getEmail(), 
  				"order_number" => $order->getNumber(), 
  				"weight" => $order->getWeight(),
  				// "to_service_point" => $order->getServicePointId(),
  				"request_label" => true, 
  				"shipment" => [
  					"id" => $order->getShippingMethodId()
  				], 
  				"parcel_items" => [], 
  				"from_name" => $vendor->getUser()->getFullName(), 
  				"from_company_name" => $companyName, 
  				"from_address_1" => trim($vendor->getAddress()), 
  				"from_address_2" => "", 
  				"from_house_number" => "110",
  				"from_city" => $vendor->getCity(), 
  				"from_postal_code" => $vendor->getZip(), 
  				"from_country" => $vendor->getCountryCode(), 
  				"from_email" => $vendor->getUser()->getEmail(), 
  			]
  		]; 


  		$curl = curl_init();
  		curl_setopt_array($curl, [
  			CURLOPT_URL => "https://panel.sendcloud.sc/api/v2/parcels",
  			CURLOPT_RETURNTRANSFER => true,
  			CURLOPT_ENCODING => "",
  			CURLOPT_MAXREDIRS => 10,
  			CURLOPT_TIMEOUT => 30,
  			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  			CURLOPT_CUSTOMREQUEST => "POST",
  			CURLOPT_POSTFIELDS => json_encode($data),
  			CURLOPT_HTTPHEADER => [
  				"Authorization: Basic MzgyNjY4NmYyZGJjNDE4MzgwODk4Y2MyNTRmYzBkMjg6MDk2ZTQ0Y2I5YjI2NDMxYjkwY2M1YjVkZWZjOWU5MTU=",
  				"Content-Type: application/json"
  			],
  		]);

  		$response = curl_exec($curl);
  		$result = json_decode($response);
  		curl_close($curl);

  		if ($result && array_key_exists("parcel",$result)) {
  			$id = $result->parcel->id;
  			$tracking_number = $result->parcel->tracking_number;

				$url = "https://panel.sendcloud.sc/api/v2/labels/normal_printer/" . $id;
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

				$content = curl_exec($curl);
				curl_close($curl);

				if ($content) {
			    $filename = md5(time().uniqid()). ".pdf"; 
			    $filepath = $this->getParameter('uploads_directory') . '/' . $filename;
			    file_put_contents($filepath, $content);

		  		$array = [
		  			"tracking_number" => $tracking_number,
		  			"pdf" => $filename,
		  		];

	  			$order->setTrackingNumber($tracking_number);
	  			$order->setParcelId($id);
	  			$order->setPdf($filename);
	  			$manager->flush();

  				return $this->json($array, 200);
				}
  		} else {
        return $this->json($result->error->message, 404);
      }
  	} catch (\Exception $e) {
  		return $this->json($e->getMessage(), 404);
  	}

  }
}
