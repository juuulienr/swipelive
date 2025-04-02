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
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Api\Admin\AdminApi;
use Cloudinary\Cloudinary;


class ShippingAPIController extends AbstractController {

  /**
   * @return User|null
   */
  public function getUser(): ?User
  {
      return parent::getUser();
  }

  /**
   * Récupérer les prix pour les livraisons
   *
   * @Route("/user/api/shipping/price", name="user_api_shipping_price")
   */
  public function shippingPrice(Request $request, ObjectManager $manager, VariantRepository $variantRepo, ProductRepository $productRepo, OrderRepository $orderRepo, ShippingAddressRepository $shippingAddressRepo, VendorRepository $vendorRepo) {
    // Initialize variables
    $array = [];
    $vendor = null;
    
    if ($json = $request->getContent()) {
	    $param = json_decode($json, true);

	    if ($param) {
        $shippingAddress = $shippingAddressRepo->findOneByUser($this->getUser());
        $now = new \DateTime('now', timezone_open('UTC'));
        $lineItems = $param["lineItems"];
        $identifier = "Order_" . time();
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
          } else {
            $totalWeight += $weight * $quantity;
          }
        }

        if ($totalWeight < 0.1) {
          $totalWeight = 0.1;
        }

	      try {
          $data = [
            "order_id" => $identifier, 
            "shipment" => [
              "id" => 0, 
              "type" => 2,
              "shipment_date" => $now->format('Y-m-d')
            ], 
            "ship_from" => [
              "postcode" => $vendor->getZip(), 
              "city" => $vendor->getCity(), 
              "country_code" => $vendor->getCountryCode()
            ], 
            "ship_to" => [
              "postcode" => $shippingAddress->getZip(), 
              "city" => $shippingAddress->getCity(), 
              "country_code" => $shippingAddress->getCountryCode()
            ], 
            "parcels" => [[
              "number" => 1,
              "weight" => $totalWeight, 
              "volumetric_weight" => $totalWeight, 
              "x" => 10, 
              "y" => 10, 
              "z" => 10 
            ]] 
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
                "identifier" => $identifier,
                "carrier_id" => $value->carrier_id,
                "carrier_name" => $value->carrier_name,
                "carrier_logo" => $value->carrier_logo,
                "service_id" => $value->service_id,
                "service_name" => $value->service_name,
                "service_code" => $value->service_code,
                "expectedDelivery" => $value->delivery_date,
                "currency" => $value->currency,
                "price" => (string) $value->price_te
              ];

              // pour la France, check pour les autres pays
              if ($shippingAddress->getCountryCode() == "FR" && $vendor->getCountryCode() == "FR") {
                if (($value->service_id == '78cb1adc-1b18-40d7-85f0-28e2a4b1753d' && $value->service_name == 'Shop2Shop') || ($value->service_id == 'bd644fe6-a77a-4045-bf97-ee1049033f0e' && $value->service_name == 'Mondial Relay')) {
                  if ($value->delivery_to_collection_point == true) {
                    $array["service_point"][] = $data;
                  }
                } else {
                  if ($value->service_id == '40bb5845-6a62-4198-8297-153a9bfc95fb' && $value->service_name == 'Colissimo sans signature') {
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
            } else {
              $array["service_point"] = [];
            }

            if (!array_key_exists('domicile', $array)) {
              $array["domicile"] = [];
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
   * Récupérer les points relais
   *
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
   * Créer l'étiquette pour envoyer un colis
   *
   * @Route("/user/api/shipping/create/{id}", name="user_api_create")
   */
  public function shipping(Order $order, Request $request, ObjectManager $manager, OrderStatusRepository $statusRepo) {
  	$shippingAddress = $order->getShippingAddress();
  	$vendor = $order->getVendor();

    try {
      $data = [
        "order_id" => $order->getIdentifier(), 
        "shipment" => [
          "id" => 0, 
          "type" => 2, 
          "dropoff" => $order->getDropoffName() ? true : false,
          "service_id" => $order->getShippingServiceId(), 
          "delivery_type" => $order->getDropoffName() ? "DELIVERY_TO_COLLECTION_POINT" : "HOME_DELIVERY",
          "label_format" => "PDF", 
        ], 
        "ship_from" => [
          "address1" => $vendor->getAddress(), 
          "lastname" => $vendor->getUser()->getFullName(), 
          "email" => $vendor->getUser()->getEmail(), 
          "phone" => $vendor->getUser()->getPhone(), 
          "company" => $vendor->getBusinessType() === "company" ? $vendor->getCompany() : $vendor->getUser()->getFullName(),
          "pro" => $vendor->getBusinessType() === "company" ? true : false
        ], 
        "ship_to" => [
          "address1" => $shippingAddress->getHouseNumber() . " " . $shippingAddress->getAddress(),
          "lastname" => $shippingAddress->getName(), 
          "company" => $shippingAddress->getName(),
          "phone" => $shippingAddress->getPhone(), 
          "email" => $order->getBuyer()->getEmail()
        ],
      ]; 

      if ($order->getDropoffName()) {
        $data["dropoff_to"] = [
          "country_code" => $order->getDropoffCountryCode(),
          "postcode" => $order->getDropoffPostcode(),
          "lastname" => $order->getDropoffName(),
          "dropoff_location_id" => $order->getDropoffLocationId()
        ];
      }

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "Accept: application/json", "Authorization: Bearer JDJ5JDEzJGdLZWxFYS5TNjh3R2V4UmU3TE9nak9nWE43U3RZR0pGS0pnODRiYWowTXlnTXAuY3hScmgu"]);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
      curl_setopt($ch, CURLOPT_URL, "https://www.upelgo.com/api/carrier/ship");

      $result = curl_exec($ch);
      $result = json_decode($result);
      curl_close($ch);

      if ($result->success) {
        $filename = md5(time().uniqid()); 
        $fullname = $filename. ".pdf"; 
        $filepath = $this->getParameter('uploads_directory') . '/' . $fullname;
        file_put_contents($filepath, base64_decode($result->waybills[0]));

        try {
          Configuration::instance($this->getParameter('cloudinary'));
          (new UploadApi())->upload($filepath, [
            'public_id' => $filename,
            'use_filename' => TRUE,
          ]);

          unlink($filepath);
        } catch (\Exception $e) {
          return $this->json($e->getMessage(), 404);
        }

        $order->setTrackingNumber($result->tracking_numbers[0]);
        $order->setPdf($filename);
        $order->setShippingStatus("open");
        $manager->flush();
      } else {
        return $this->json($result->error, 404);
      }


      try {
        $url = "https://www.upelgo.com/api/carrier/" . $order->getShippingCarrierId() . "/track";
        $data = [
          "tracking_number" => $order->getTrackingNumber()
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

        if ($result->success) {
          $order->setDelivered($result->delivered);

          if ($result->incident_date != "") {
            $order->setIncidentDate(new \Datetime($result->incident_date));
          }

          if ($result->delivery_date != "") {
            $order->setDeliveryDate(new \Datetime($result->delivery_date));
          }

          // update orderStatus
          if ($result->events) {
            foreach ($result->events as $event) {
              $orderStatus = $statusRepo->findOneByShipping($order);

              if (!$orderStatus && $event->date) {
                $orderStatus = new OrderStatus();
                $orderStatus->setDate(new \Datetime($event->date_unformatted));
                $orderStatus->setDescription($event->description);
                $orderStatus->setCode($event->code);
                $orderStatus->setShipping($order);

                if ($event->location) {
                  foreach ($event->location as $location) {
                    $orderStatus->setPostcode($location->postcode);
                    $orderStatus->setCity($location->city);
                    $orderStatus->setLocation($location->location);
                  }
                }

                $order->setUpdatedAt(new \DateTime('now', timezone_open('Europe/Paris')));
                $manager->persist($orderStatus);
                $manager->flush();
              }
            }
          }

          $manager->flush();

          return $this->json($order, 200, [], [
            'groups' => 'order:read', 
          ]);
        } else {
          return $this->json($order, 200, [], [
            'groups' => 'order:read', 
          ]);
        }
      } catch (\Exception $e) {
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
