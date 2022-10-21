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
use \Swagger\Client\Model\AllOfKnownParticipantStatus;
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
   * @Route("/api/shipping", name="user_api_shipping")
   */
  public function shipping(Request $request, ObjectManager $manager){

    $sendCloud = new \Imbue\SendCloud\SendCloudApiClient();
    $sendCloud->setApiAuth('3826686f2dbc418380898cc254fc0d28', '096e44cb9b26431b90cc5b5defc9e915');

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


    // create and print label
    // $parcelId = 180248575;
    // $label = $sendCloud->labels->get($parcelId);
    // dump($labels);
    // $pdf = $sendCloud->labels->getLabelAsPdf($parcelId, 'label_printer');
    // $filename = md5(time().uniqid()). ".pdf"; 
    // $filepath = $this->getParameter('uploads_directory') . '/' . $filename;
    // file_put_contents($filepath, $pdf);


    // tracking parcel
    // $tracking_number = $parcel->tracking_number();
    // $tracking = $sendCloud->tracking->get('SCCWF3BVYJ7W');
    // dump($tracking);

    die();
  }

  /**
   * @Route("/api/test", name="test")
   */
  public function test(Request $request, ObjectManager $manager){
		// $apiInstance = new \Swagger\Client\Api\ParticipantsApi(new \GuzzleHttp\Client());

		// $body = new \Swagger\Client\Model\V1ParticipantsBody([
		// 	'email' => 'dfdfdfdfdf@gmail.com',
		// 	'name' => 'Marie Joseph'
		// ]); 

		// $authorization = "sandbox_api_m5dZIkcoIqZ960aek04bWNJNGSpVAZmQMkLZbnbFC44BWP5ixYq6LKeSCHFCqPO0";

		// try {
		//     $result = $apiInstance->createParticipant($body, $authorization);
		//     dump($result);
		// } catch (Exception $e) {
		//     dump('Exception when calling ParticipantsApi->createParticipant: ', $e->getMessage());
		// }


		// $apiInstance = new \Swagger\Client\Api\ParticipantsApi(new \GuzzleHttp\Client());
		// $authorization = "sandbox_api_m5dZIkcoIqZ960aek04bWNJNGSpVAZmQMkLZbnbFC44BWP5ixYq6LKeSCHFCqPO0"; 

		// $id = "participant_yLmWk8TLBO"; // string | A unique ID of an existing participant.  A string in the format: `participant_[0-9a-z]`

		// try {
		//     $result = $apiInstance->getParticipant($authorization, $id);
		//     dump($result);
		// } catch (Exception $e) {
		//     dump('Exception when calling ParticipantsApi->getParticipant: ', $e->getMessage());
		// }

		$apiInstance = new \Swagger\Client\Api\PaymentsApi(new \GuzzleHttp\Client());
		$authorization = "sandbox_api_m5dZIkcoIqZ960aek04bWNJNGSpVAZmQMkLZbnbFC44BWP5ixYq6LKeSCHFCqPO0";

		$body = new \Swagger\Client\Model\IntentsPaymentBody([
			  'type' => 'checkout',
			  'currency' => 'gbp',
			  'from' => [ 'email'=> 'buyer@trustshare.co' ],
			  'fee_flat' => 7500,
			  'settlements' => [
			    [
			      'type' => 'escrow',
			      'to' => [ 'email'=> 'seller@lumber.com' ],
			      'description' => 'Lumber - Pine Planks',
			      'amount' => 750000,
			    ],
			    [
			      'type' => 'escrow',
			      'to' => [ 'email'=> 'seller@bricks.com' ],
			      'description' => 'Welsh Stone Bricks',
			      'summary' => 'Quantity: 1400 - Weight: 1.2t',
			      'amount' => 250000,
			      'fee_flat' => 5000,
			    ]
			  ]
			]
		); 

		try {
			$result = $apiInstance->createPaymentIntent($body, $authorization);
		  dump($result);
		  dump($result->client_secret);
		} catch (Exception $e) {
		  dump('Exception when calling PaymentsApi->createPaymentIntent: ', $e->getMessage());
		}

  }
}
