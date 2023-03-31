<?php

namespace App\Controller\App;

use App\Entity\Clip;
use App\Entity\Live;
use App\Entity\User;
use App\Entity\Vendor;
use App\Entity\Message;
use App\Entity\Product;
use App\Entity\Category;
use App\Entity\Order;
use App\Entity\OrderStatus;
use App\Entity\LineItem;
use App\Entity\ShippingAddress;
use App\Repository\ClipRepository;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\LiveRepository;
use App\Repository\VariantRepository;
use App\Repository\OrderRepository;
use App\Repository\ShippingAddressRepository;
use App\Repository\VendorRepository;
use App\Repository\LiveProductsRepository;
use App\Repository\OrderStatusRepository;
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
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Api\Admin\AdminApi;
use Cloudinary\Cloudinary;


class ShippingAPIController extends Controller {


  /**
   * @Route("/user/api/shipping/price", name="user_api_shipping_price")
   */
  public function shippingPrice(Request $request, ObjectManager $manager, VariantRepository $variantRepo, ProductRepository $productRepo, OrderRepository $orderRepo, ShippingAddressRepository $shippingAddressRepo, VendorRepository $vendorRepo) {
    if ($json = $request->getContent()) {
	    $param = json_decode($json, true);

	    if ($param) {
        $shippingAddress = $shippingAddressRepo->findOneByUser($this->getUser());
	    	$lineItems = $param["lineItems"];
        $nbOrders = 1000 + sizeof($orderRepo->findAll());
        $totalWeight = 0;

	      if (!$lineItems) {
	        return $this->json("Un produit est obligatoire !", 404); 
	      }

        if (!$shippingAddress) {
          return $this->json("Une adresse est obligatoire !", 404); 
        }

        foreach ($lineItems as $lineItem) {
          $vendor = $vendorRepo->findOneById($lineItem["vendor"]);
          if ($lineItem["variant"]) {
            $weightUnit = $lineItem["variant"]["weightUnit"];
            $weight = $lineItem["variant"]["weight"];
          } else {
            $weightUnit = $lineItem["product"]["weightUnit"];
            $weight = $lineItem["product"]["weight"];
          }
          $quantity = $lineItem["quantity"];

          if ($weightUnit == "g") {
            $totalWeight += round($weight / 1000 * $quantity, 2);
            // number_format($weight, 2, '.', '');
          } else {
            $totalWeight += round($weight * $quantity);
          }
        }

	      try {
          // récupérer les prix pour les livraisons
          $data = [
            "order_id" => "Order_" . $nbOrders . "_" . time(), 
            "locale" => "fr_FR", 
            "shipment" => [
              "id" => 0, 
              "type" => 2,
            ], 
            "ship_from" => [
              "pro" => true,
              "postcode" => $vendor->getZip(), 
              "city" => $vendor->getCity(), 
              "country_code" => $vendor->getCountryCode()
            ], 
            "ship_to" => [
              "pro" => false, 
              "postcode" => $shippingAddress->getZip(), 
              "city" => $shippingAddress->getCity(), 
              "country_code" => $shippingAddress->getCountryCode()
            ], 
            "parcels" => [
              [
                "number" => 1,
                "weight" => $totalWeight, 
                "volumetric_weight" => $totalWeight, 
                "x" => 10, 
                "y" => 10, 
                "z" => 10 
              ] 
            ] 
          ]; 


          $ch = curl_init();
          curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "Accept: application/json", "Authorization: Bearer JDJ5JDEzJGdLZWxFYS5TNjh3R2V4UmU3TE9nak9nWE43U3RZR0pGS0pnODRiYWowTXlnTXAuY3hScmgu"]);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
          curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
          curl_setopt($ch, CURLOPT_URL, "https://www.upelgo.com/api/carrier/multi-rate");

          $result = curl_exec($ch);
          $result = json_decode($result);
          curl_close($ch);

          if ($result->success == true) {
            foreach ($result->offers as $value) {
              $data = [ 
                "id" => $value->service_id,
                "carrier" => $value->carrier_name,
                "name" => $value->service_name,
                "code" => $value->service_code,
                "price" => (string) round($value->price_te * 1.2, 2),
                "currency" => $value->currency
              ];

              if (($value->service_id == '78cb1adc-1b18-40d7-85f0-28e2a4b1753d' && $value->service_name == 'Shop2Shop') || ($value->service_id == 'bd644fe6-a77a-4045-bf97-ee1049033f0e' && $value->service_name == 'Mondial Relay')) {
                $array["service_point"][] = $data;
              } else {
                if ($value->service_id == '5d490080-3ccf-48b3-b16a-72d7dd268d51' && $value->service_name == 'UPS Standard®') {
                  $array["domicile"][] = $data;
                }
              }
            }

            $price = array_column($array["service_point"], 'price');
            array_multisort($price, SORT_ASC, $array["service_point"]);
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
   * @Route("/user/api/shipping/create/{id}", name="user_api_create")
   */
  public function shipping(Order $order, Request $request, ObjectManager $manager, OrderStatusRepository $statusRepo) {
  	$shippingAddress = $order->getShippingAddress();
  	$vendor = $order->getVendor();

  	if ($vendor->getBusinessType() == "company") {
  		$companyName = $vendor->getCompany();
  	} else {
  		$companyName = "";
  	}


    try {

      // créer l'étiquette 
      $data = [
        "order_id" => "Test_order_id2", 
        "process_shipment" => true, 
        "shipment" => [
          "id" => 0, 
          "type" => 2, 
          "shipment_date" => "2023-04-24", 
          "service_code" => "DOM", 
          "service_id" => "58da4d87-f7e1-4bb7-901e-ff6b52062b4c", 
          "delivery_type" => "DELIVERY_TO_COLLECTION_POINT", 
          "label_format" => "PDF", 
          "insurance" => true, 
          "insurance_type" => "UPELA", 
          "insurance_price" => 100 
        ], 
        "ship_from" => [
          "address1" => "87 chemin de la lune", 
          "lastname" => "Loic Rombai", 
          "email" => "neoglucogenese@gmail.com", 
          "phone" => "+33666666666", 
          "company" => "Upela" 
        ], 
        "ship_to" => [
          "pro" => false,
          "address1" => "54 rue du coteau", 
          "lastname" => "Marc Reignier", 
          "email" => "marc.reignier@laposte.net", 
          "phone" => "+33666666667", 
          "company" => "Upela" 
        ] 
      ]; 


      $ch = curl_init();
      curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "Accept: application/json", "Authorization: Bearer JDJ5JDEzJGdLZWxFYS5TNjh3R2V4UmU3TE9nak9nWE43U3RZR0pGS0pnODRiYWowTXlnTXAuY3hScmgu"]);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
      curl_setopt($ch, CURLOPT_URL, "https://www.upelgo.com/api/carrier/ship");

      $result = curl_exec($ch);
      $result = json_decode($result);
      dump($result);
      curl_close($ch);


      if ($result->success == true) {
        dump($result->parent_carrier);
        dump($result->shipment_id);
        dump($result->carrier_id);
        dump($result->carrier_name);
        dump($result->carrier_code);
        dump($result->tracking_numbers);
        dump($result->tracking_uri); // url pour tracker directement
        // dump($result->waybills);
        dump($result->waybills_uri);
        dump($result->pickup_code);
        dump($result->documents);
      }
    } catch (\Exception $e) {
      return $this->json($e->getMessage(), 404);
    }


    try {

      // tracker un colis 
      $data = [
        "tracking_number" => "6A25869964850"
      ]; 


      $ch = curl_init();
      curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "Accept: application/json", "Authorization: Bearer JDJ5JDEzJGdLZWxFYS5TNjh3R2V4UmU3TE9nak9nWE43U3RZR0pGS0pnODRiYWowTXlnTXAuY3hScmgu"]);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
      curl_setopt($ch, CURLOPT_URL, "https://www.upelgo.com/api/carrier/32b1463a-a275-46d3-b9fd-ee17a1e8ab33/track");

      $result = curl_exec($ch);
      $result = json_decode($result);
      curl_close($ch);

      dump($result);

      if ($result->success == true) {
        dump($result->delivered);
        dump($result->pickup_date);
        dump($result->incident_date);
        dump($result->delivery_date);
        dump($result->tracking_number);
        dump($result->service_name); 
        dump($result->carrier_name);
        dump($result->requested_shipment_date);
        dump($result->requested_delivery_date);



        if ($result->events) {
          foreach ($result->events as $event) {
            // $event->date
            // $event->location
            // $event->description
            // $event->code
          }
        }
      }


  	// try {
    //   $variable = explode(" ", trim($vendor->getAddress()), 2);
    //   $from_address_1 = trim($variable[1]);
    //   $from_house_number = trim($variable[0]);

  	// 	$data = [
  	// 		"parcel" => [
  	// 			"name" => $order->getBuyer()->getFullName(), 
  	// 			"address" => $shippingAddress->getAddress(), 
  	// 			"house_number" => $shippingAddress->getHouseNumber(), 
  	// 			"city" => $shippingAddress->getCity(), 
  	// 			"postal_code" => $shippingAddress->getZip(), 
  	// 			"country" => $shippingAddress->getCountryCode(), 
  	// 			"telephone" => $shippingAddress->getPhone() ? $shippingAddress->getPhone() : "", 
  	// 			"email" => $order->getBuyer()->getEmail(), 
  	// 			"order_number" => $order->getNumber(), 
  	// 			"weight" => $order->getWeight(),
  	// 			"to_service_point" => $order->getServicePointId(),
  	// 			"request_label" => true, 
  	// 			"shipment" => [
  	// 				"id" => $order->getShippingMethodId()
  	// 			], 
  	// 			"parcel_items" => [], 
  	// 			"from_name" => $vendor->getUser()->getFullName(), 
  	// 			"from_company_name" => $companyName, 
  	// 			"from_address_1" => $from_address_1, 
  	// 			"from_address_2" => "", 
  	// 			"from_house_number" => $from_house_number,
  	// 			"from_city" => $vendor->getCity(), 
  	// 			"from_postal_code" => $vendor->getZip(), 
  	// 			"from_country" => $vendor->getCountryCode(), 
  	// 			"from_email" => $vendor->getUser()->getEmail(), 
  	// 		]
  	// 	]; 

  	// 	$curl = curl_init();
  	// 	curl_setopt_array($curl, [
  	// 		CURLOPT_URL => "https://panel.sendcloud.sc/api/v2/parcels",
  	// 		CURLOPT_RETURNTRANSFER => true,
  	// 		CURLOPT_ENCODING => "",
  	// 		CURLOPT_MAXREDIRS => 10,
  	// 		CURLOPT_TIMEOUT => 30,
  	// 		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  	// 		CURLOPT_CUSTOMREQUEST => "POST",
  	// 		CURLOPT_POSTFIELDS => json_encode($data),
  	// 		CURLOPT_HTTPHEADER => [
  	// 			"Authorization: Basic MzgyNjY4NmYyZGJjNDE4MzgwODk4Y2MyNTRmYzBkMjg6MDk2ZTQ0Y2I5YjI2NDMxYjkwY2M1YjVkZWZjOWU5MTU=",
  	// 			"Content-Type: application/json"
  	// 		],
  	// 	]);

  		// $response = curl_exec($curl);
  		// $result = json_decode($response);
  		// curl_close($curl);

  		// if ($result && array_key_exists("parcel",$result)) {
  		// 	$id = $result->parcel->id;
  		// 	$tracking_number = $result->parcel->tracking_number;
      //   $tracking_url = $result->parcel->tracking_url;
			// 	$url = "https://panel.sendcloud.sc/api/v2/labels/normal_printer/" . $id;
			// 	$curl = curl_init();

			// 	curl_setopt_array($curl, [
			// 		CURLOPT_URL => $url,
			// 		CURLOPT_RETURNTRANSFER => true,
			// 		CURLOPT_ENCODING => "",
			// 		CURLOPT_MAXREDIRS => 10,
			// 		CURLOPT_TIMEOUT => 30,
			// 		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			// 		CURLOPT_CUSTOMREQUEST => "GET",
			// 		CURLOPT_HTTPHEADER => [
			// 			"Authorization: Basic MzgyNjY4NmYyZGJjNDE4MzgwODk4Y2MyNTRmYzBkMjg6MDk2ZTQ0Y2I5YjI2NDMxYjkwY2M1YjVkZWZjOWU5MTU=",
			// 			"Content-Type: application/json"
			// 		],
			// 	]);

			// 	$content = curl_exec($curl);
			// 	curl_close($curl);

			// 	if ($content) {
      //     $filename = md5(time().uniqid()); 
      //     $fullname = $filename. ".pdf"; 
      //     $filepath = $this->getParameter('uploads_directory') . '/' . $fullname;
      //     file_put_contents($filepath, $content);

      //     try {
      //       $result = (new UploadApi())->upload($filepath, [
      //         'public_id' => $filename,
      //         'use_filename' => TRUE,
      //       ]);

      //       unlink($filepath);
      //     } catch (\Exception $e) {
      //       return $this->json($e->getMessage(), 404);
      //     }
      
	  	// 		$order->setTrackingUrl($tracking_url);
      //     $order->setTrackingNumber($tracking_number);
	  	// 		$order->setParcelId($id);
	  	// 		$order->setPdf($filename);
	  	// 		$manager->flush();


      //     if ($order->getTrackingNumber()) {
      //       $url = "https://panel.sendcloud.sc/api/v2/tracking/" . $order->getTrackingNumber();
      //       $curl = curl_init();

      //       curl_setopt_array($curl, [
      //         CURLOPT_URL => $url,
      //         CURLOPT_RETURNTRANSFER => true,
      //         CURLOPT_ENCODING => "",
      //         CURLOPT_MAXREDIRS => 10,
      //         CURLOPT_TIMEOUT => 30,
      //         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      //         CURLOPT_CUSTOMREQUEST => "GET",
      //         CURLOPT_HTTPHEADER => [
      //           "Authorization: Basic MzgyNjY4NmYyZGJjNDE4MzgwODk4Y2MyNTRmYzBkMjg6MDk2ZTQ0Y2I5YjI2NDMxYjkwY2M1YjVkZWZjOWU5MTU=",
      //           "Content-Type: application/json"
      //         ],
      //       ]);

      //       $response = curl_exec($curl);
      //       $result = json_decode($response);
      //       curl_close($curl);

      //       if ($result && array_key_exists("expected_delivery_date", $result)) {
      //         $order->setExpectedDelivery(new \Datetime($result->expected_delivery_date));
      //         $manager->flush();

      //         foreach ($result->statuses as $status) {
      //           $orderStatus = $statusRepo->findOneByStatusId($status->parcel_status_history_id);

      //           if (!$orderStatus) {
      //             $orderStatus = new OrderStatus();
      //             $orderStatus->setUpdateAt(new \Datetime($status->carrier_update_timestamp));
      //             $orderStatus->setMessage($status->carrier_message);
      //             $orderStatus->setStatus($status->parent_status);
      //             $orderStatus->setCode($status->carrier_code);
      //             $orderStatus->setStatusId($status->parcel_status_history_id);
      //             $orderStatus->setShipping($order);
      //             $order->setShippingStatus($status->parent_status);
      //             $order->setUpdatedAt(new \Datetime($status->carrier_update_timestamp));

      //             $manager->persist($orderStatus);
      //             $manager->flush();
      //           }
      //         }
      //       }
      //     }

      //     return $this->json($order, 200, [], [
      //       'groups' => 'order:read', 
      //       'datetime_format' => 'd F Y à H:i' 
      //     ]);
      //   }
  		// } else {
      //   return $this->json($result->error->message, 404);
      // }
  	} catch (\Exception $e) {
  		return $this->json($e->getMessage(), 404);
  	}
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
      
      return $this->json($this->getUser(), 200, [], [
        'groups' => 'user:read', 
        'circular_reference_limit' => 1, 
        'circular_reference_handler' => function ($object) {
          return $object->getId();
        } 
      ]);
    }
    return $this->json("Une erreur est survenue", 404);
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

      return $this->json($this->getUser(), 200, [], [
        'groups' => 'user:read', 
        'circular_reference_limit' => 1, 
        'circular_reference_handler' => function ($object) {
          return $object->getId();
        } 
      ]);
    }
    return $this->json("Une erreur est survenue", 404);
  }

}
