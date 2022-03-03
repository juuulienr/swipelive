<?php

namespace App\Controller\Mobile\Vendor;

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
   * @Route("/api/shipping", name="vendor_api_shipping")
   */
  public function shipping(Request $request, ObjectManager $manager){

    $sendCloud = new \Imbue\SendCloud\SendCloudApiClient();
    $sendCloud->setApiAuth('3826686f2dbc418380898cc254fc0d28', '096e44cb9b26431b90cc5b5defc9e915');

    $integrations = $sendCloud->integrations->list();
    $shippingMethods = $sendCloud->shippingMethods->list();
    $parcels = $sendCloud->parcels->list();
    $parcelStatuses = $sendCloud->parcelStatuses->list();
    dump($integrations);
    dump($shippingMethods);
    dump($parcels);
    dump($parcelStatuses);

    // $parcel = $sendCloud->parcels->create([
    //   'parcel' => [
    //     'name' => 'Julie Appleseed',
    //     'company_name' => 'SendCloud',
    //     'address' => '54 rue du coteau',
    //     'house_number' => 115,
    //     'city' => 'Miribel',
    //     'postal_code' => '01700',
    //     'telephone' => '+31612345678',
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

    // $this->parcels
    // $this->parcelStatuses 
    // $this->shippingMethods
    // $this->senderAddresses
    // $this->labels 
    // $this->invoices
    // $this->user
    // $this->integrations
    // $this->integrationShipments

    die();
  }
}
