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
        $orderId = "Order_" . time();
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
            $totalWeight += $weight * $quantity;
          }
        }

        if ($totalWeight < 0.1) {
          $totalWeight = 0.1;
        }

	      try {
          // récupérer les prix pour les livraisons
          $data = [
            "order_id" => $orderId, 
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
                "order_id" => $orderId,
                "carrier_id" => $value->carrier_id,
                "carrier_name" => $value->carrier_name,
                "service_id" => $value->service_id,
                "service_name" => $value->service_name,
                "service_code" => $value->service_code,
                "currency" => $value->currency,
                "price" => (string) round($value->price_te * 1.2, 2)
              ];

              // pour la France, check pour les autres pays
              if ($shippingAddress->getCountryCode() == "FR" && $vendor->getCountryCode() == "FR") {
                if (($value->service_id == '78cb1adc-1b18-40d7-85f0-28e2a4b1753d' && $value->service_name == 'Shop2Shop') || ($value->service_id == 'bd644fe6-a77a-4045-bf97-ee1049033f0e' && $value->service_name == 'Mondial Relay')) {
                  $array["service_point"][] = $data;
                } else {
                  if ($value->service_id == '5d490080-3ccf-48b3-b16a-72d7dd268d51' && $value->service_name == 'UPS Standard®') {
                    $array["domicile"][] = $data;
                  }
                }
              } else {
                return $this->json("Livraison indisponible dans ce pays", 404);
              }
            }

            if (array_key_exists('service_point', $array)) {
              $price = array_column($array["service_point"], 'price');
              array_multisort($price, SORT_ASC, $array["service_point"]);
            }

            return $this->json($array, 200);
          }
	      } catch (Exception $e) {
	      	return $this->json($e, 500);
	      }
			}
		}
    
    return $this->json("Un erreur est survenue", 404);
  }


  /**
   * @Route("/user/api/dropoff-locations", name="user_api_dropoff_locations")
   */
  public function dropoffLocations(Request $request, ObjectManager $manager, VariantRepository $variantRepo, ProductRepository $productRepo, OrderRepository $orderRepo, ShippingAddressRepository $shippingAddressRepo, VendorRepository $vendorRepo) {
    if ($json = $request->getContent()) {
      $param = json_decode($json, true);

      if ($param) {
        $servicePoints = $param["service_point"];
        $shippingAddress = $shippingAddressRepo->findOneByUser($this->getUser());
        $array = [];
    
        if (!$shippingAddress) {
          return $this->json("Une adresse est obligatoire !", 404); 
        }

        foreach ($servicePoints as $point) {
          try {
            // récupérer les points relais
            $url = "https://www.upelgo.com/api/carrier/" . $point["carrier_id"] . "/dropoff-locations";
            $data = [
              "address" => $shippingAddress->getAddress(), 
              "postcode" => $shippingAddress->getZip(), 
              "city" => $shippingAddress->getCity(), 
              "country_code" => $shippingAddress->getCountryCode()
            ]; 

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "Accept: application/json", "Authorization: Bearer JDJ5JDEzJGdLZWxFYS5TNjh3R2V4UmU3TE9nak9nWE43U3RZR0pGS0pnODRiYWowTXlnTXAuY3hScmgu"]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_URL, $url);

            $result = curl_exec($ch);
            $result = json_decode($result);
            curl_close($ch);

            if ($result->success == true) {
              foreach ($result->locations as $value) {
                $opening = [];
                foreach ($value->hours as $hour) {
                  $opening[] = [ 
                    "day" => $hour->day, 
                    "opening_hours" => $hour->opening_hours
                  ];
                }

                if ($point["carrier_id"] == "b139ac1f-bbb9-4235-b87e-aedcb3c32132") {
                  $distance = round($value->distance * 1000, 2);
                } else {
                  $distance = $value->distance;
                }

                $array[] = [
                  "carrier_id" => $point["carrier_id"],
                  "carrier_name" => $point["carrier_name"],
                  "location_id" => $value->location_id,
                  "name" => trim($value->name),
                  "address1" => trim($value->address1),
                  "address2" => trim($value->address2),
                  "postcode" => $value->postcode,
                  "city" => trim($value->city),
                  "country_code" => $value->country_code,
                  "latitude" => $value->latitude,
                  "longitude" => $value->longitude,
                  "distance" => $distance,
                  "hours" => $opening,
                  "dropoff_location_id" => $value->dropoff_location_id,
                  "image_url" => $value->image_url,
                  "number" => $value->number,
                ];
              }
            }
          } catch (Exception $e) {
            return $this->json($e, 500);
          }
        }

        $distance = array_column($array, 'distance');
        array_multisort($distance, SORT_ASC, $array);

        return $this->json($array, 200);
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
        "shipment" => [
          "id" => 0, 
          "type" => 2, 
          "dropoff": false, // true if collection point
          "service_id" => "58da4d87-f7e1-4bb7-901e-ff6b52062b4c", 
          "delivery_type" => "DELIVERY_TO_COLLECTION_POINT",  //HOME_DELIVERY
          "label_format" => "PDF", 
          // "insurance" => true, 
          // "insurance_type" => "UPELA", 
          // "insurance_price" => 100 
        ], 
        "ship_from" => [
          "address1" => $vendor->getAddress(), 
          "lastname" => $vendor->getUser()->getFullName(), 
          "email" => $vendor->getUser()->getEmail(), 
          "phone" => $vendor->getUser()->getPhone(), 
          "company" => $vendor->getCompany()
        ], 
        "ship_to" => [
          "pro" => false,
          "address1" => $shippingAddress->getAddress(), 
          "lastname" => $order->getBuyer()->getFullName(), 
          "email" => $order->getBuyer()->getEmail(), 
          "phone" => $shippingAddress->getPhone(), 
          "company" => "Swipe Live" 
        ],
        // "dropoff_to" => [
        //   "country_code" => "FR",
        //   "postcode" => "69140",
        //   "lastname" => "ESPACE DIGITAL",
        //   "dropoff_location_id" => "006274"
        // ]
      ]; 


      $ch = curl_init();
      curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "Accept: application/json", "Authorization: Bearer JDJ5JDEzJGdLZWxFYS5TNjh3R2V4UmU3TE9nak9nWE43U3RZR0pGS0pnODRiYWowTXlnTXAuY3hScmgu"]);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
      curl_setopt($ch, CURLOPT_URL, "https://www.upelgo.com/api/carrier/ship");

      $result = curl_exec($ch);
      $result = json_decode($result);
      curl_close($ch);

      dump($result);

      if ($result->success == true) {
       // $id = $result->parcel->id;
       // $tracking_number = $result->parcel->tracking_number;
       // $tracking_url = $result->parcel->tracking_url;

       // $order->setTrackingUrl($tracking_url);
       // $order->setTrackingNumber($tracking_number);
       // $order->setParcelId($id);
       // $order->setPdf($filename);
       // $manager->flush();

        // dump($result->parent_carrier);
        // dump($result->shipment_id);
        // dump($result->carrier_id);
        // dump($result->carrier_name);
        // dump($result->carrier_code);
        // dump($result->tracking_numbers);
        // dump($result->tracking_uri); // url pour tracker directement
        // // dump($result->waybills);
        // dump($result->waybills_uri);
        // dump($result->pickup_code);
        // dump($result->documents);

        return $this->json($order, 200, [], [
          'groups' => 'order:read', 
        ]);
      }
    } catch (\Exception $e) {
      return $this->json($e->getMessage(), 404);
    }

    return $this->json(false, 404);
  }


  /**
   * Suivre une commande
   *
   * @Route("/user/api/orders/track", name="user_api_shipping_address", methods={"POST"})
   */
  public function tracking(Order $order, Request $request, ObjectManager $manager) {
    try {
      // tracker un colis 
      $url = "https://www.upelgo.com/api/carrier/32b1463a-a275-46d3-b9fd-ee17a1e8ab33/track";
      $data = [
        "tracking_number" => "6A25869964850"
      ]; 

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "Accept: application/json", "Authorization: Bearer JDJ5JDEzJGdLZWxFYS5TNjh3R2V4UmU3TE9nak9nWE43U3RZR0pGS0pnODRiYWowTXlnTXAuY3hScmgu"]);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
      curl_setopt($ch, CURLOPT_URL, $url);

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
    } catch (\Exception $e) {
      return $this->json($e->getMessage(), 404);
    }

    return $this->json(true, 200);
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
