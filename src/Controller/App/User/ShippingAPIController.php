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
use GuzzleHttp\Client;


class ShippingAPIController extends Controller {


  /**
   * @Route("/user/api/shipping/price", name="user_api_shipping_price")
   */
  public function shippingPrice(Request $request, ObjectManager $manager, VariantRepository $variantRepo, ProductRepository $productRepo) {
    if ($json = $request->getContent()) {
	    $param = json_decode($json, true);

	    if ($param) {
	    	$weight = $param["weight"];
	    	$to_country = $param["countryShort"];

	      if (!$weight) {
	        return $this->json("Le poids est obligatoire", 404); 
	      }

	      switch ($weight) {
	      	case "small":
	      		$weight = 500;
	      	break;
	      	case "medium":
	      		$weight = 1000;
	      	break;
	      	case "large":
	      		$weight = 2000;
	      	break;
	      	default:
	      }

	      try {
	      	$params = [
	      		"from_country" => "FR",
	      		"to_country" => $to_country,
	      		"weight" => $weight,
	      		"weight_unit" => "gram",
	      		"contract_pricing" => true 
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
	      				if (str_contains($method->name, 'Chrono Shop2Shop') || str_contains($method->name, 'Colissimo Service Point') || str_contains($method->name, 'Mondial Relay Point Relais') || (str_contains($method->name, 'Colissimo Home') && !str_contains($method->name, 'Colissimo Home Signature'))) {
	      					if (str_contains($method->name, 'Colissimo Home')) {
	      						$array["domicile"][] = [ 
	      							"id" => $method->id,
	      							"carrier" => $value->carrier,
	      							"name" => $value->name,
	      							"price" => $method->pricing->price,
	      							"currency" => $method->pricing->currency
	      						];
	      					} else {
	      						$array["service_point"][] = [ 
	      							"id" => $method->id,
	      							"carrier" => $value->carrier,
	      							"name" => $value->name,
	      							"price" => $method->pricing->price,
	      							"currency" => $method->pricing->currency
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
   * @Route("/api/shipping", name="user_api_shipping")
   */
  public function shipping(Request $request, ObjectManager $manager){
    // $sendCloud = new \Imbue\SendCloud\SendCloudApiClient();
    // $sendCloud->setApiAuth('3826686f2dbc418380898cc254fc0d28', '096e44cb9b26431b90cc5b5defc9e915');

    // // type of shipping method
    // $shippingMethods = $sendCloud->shippingMethods->list();
    // dump($shippingMethods);

    // foreach ($shippingMethods as $method) {
    //  dump($method);
    // }
    
    // // type of status
    // $parcelStatuses = $sendCloud->parcelStatuses->list();
    // dump($parcelStatuses);

    // // parcel
    // $parcels = $sendCloud->parcels->list();
    // dump($parcels);
    // $parcel = $sendCloud->parcels->get(180248575);

    // $parcel = $sendCloud->parcels->create([
    //   'parcel' => [
    //     'name' => 'Loic Rombai',
    //     'company_name' => 'Loic SAS',
    //     'address' => 'rue du coteau',
    //     'house_number' => 54,
    //     'city' => 'Miribel',
    //     'postal_code' => '01700',
    //     'telephone' => '+33666666666',
    //     'request_label' => true,
    //     'email' => 'neoglucogenese@gmail.com',
    //     'country' => 'FR',
    //     'shipment' => [
    //       'id' => 8,
    //     ],
    //     'weight' => '1.000',
    //     'order_number' => '1234567890',
    //     // 'insured_value' => 2000,
    //   ]
    // ]);
    // dd($parcel);

    // $parcelId = $parcel->id();
    // dump($parcelId);
    // $parcels = $sendCloud->parcels->cancel();


    // // create and print label
    // $parcelId = 180248575;
    // $label = $sendCloud->labels->get($parcelId);
    // dump($labels);
    // $pdf = $sendCloud->labels->getLabelAsPdf($parcelId, 'label_printer');
    // $filename = md5(time().uniqid()). ".pdf"; 
    // $filepath = $this->getParameter('uploads_directory') . '/' . $filename;
    // file_put_contents($filepath, $pdf);


    // // tracking parcel
    // $tracking_number = $parcel->tracking_number();
    // $tracking = $sendCloud->tracking->get('SCCWF3BVYJ7W');
    // dump($tracking);

    return true;
  }
}
