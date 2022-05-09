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


class ShippingAPIController extends Controller {

  /**
   * @Route("/api/shipping", name="user_api_shipping")
   */
  public function shipping(Request $request, ObjectManager $manager){

    $sendCloud = new \Imbue\SendCloud\SendCloudApiClient();
    $sendCloud->setApiAuth('3826686f2dbc418380898cc254fc0d28', '096e44cb9b26431b90cc5b5defc9e915');

    // intégrations sendcloud
    $integrations = $sendCloud->integrations->list();

    // méthodes de livraison
    $shippingMethods = $sendCloud->shippingMethods->list();

    // status de livraison
    $parcelStatuses = $sendCloud->parcelStatuses->list();

    // Colis
    $parcels = $sendCloud->parcels->list();
    // $parcel = $sendCloud->parcels->get($id);

    // $parcel = $sendCloud->parcels->create([
    //   'parcel' => [
    //     'name' => 'Julie Appleseed',
    //     'company_name' => 'SendCloud',
    //     'address' => 'rue du coteau',
    //     'house_number' => 54,
    //     'city' => 'Miribel',
    //     'postal_code' => '01700',
    //     'telephone' => '+33666666666',
    //     'request_label' => true,
    //     'email' => 'julie@appleseed.com',
    //     'country' => 'FR',
    //     'shipment' => [
    //       'id' => 8,
    //     ],
    //     'weight' => '10.000',
    //     'order_number' => '1234567890',
    //     'insured_value' => 2000,
    //   ]
    // ]);
    // dump($parcel);

    // $parcelId = $parcel->id();
    $parcelId = 180248575;
    // dump($parcelId);



    // $label = $sendCloud->labels->get($parcelId);
    // // dump($label);
    // $pdf = $sendCloud->labels->getLabelAsPdf($parcelId, 'normal_printer');
    // $pdf = $sendCloud->labels->getLabelAsPdf($parcelId, 'label_printer');
    // // dump($pdf);

    // $filename = md5(time().uniqid()). ".pdf"; 
    // $filepath = $this->getParameter('uploads_directory') . '/' . $filename;
    // file_put_contents($filepath, $pdf);



    // $senderAddresses = $sendCloud->senderAddresses->list();
    // $user = $sendCloud->user->get();

    // dump($integrations);
    // dump($shippingMethods);
    // dump($parcels);
    // dump($parcelStatuses);
    // // dump($labels);
    // // dump($senderAddresses);
    // // dump($user);


    die();
  }
}
