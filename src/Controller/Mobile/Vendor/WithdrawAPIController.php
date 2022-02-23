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
use App\Entity\Withdraw;
use App\Entity\LineItem;
use App\Entity\BankAccount;
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


class WithdrawAPIController extends Controller {

  /**
   * @Route("/vendor/api/bank/add", name="vendor_api_bank_add")
   */
  public function addBank(Request $request, ObjectManager $manager){
    if ($json = $request->getContent()) {
      $param = json_decode($json, true);

      if ($param) {
        $vendor = $this->getUser();

        if ($vendor->getStripeAcc() && $param["number"]) {
          $stripe = new \Stripe\StripeClient($this->getParameter('stripe_sk'));
          $result = $stripe->accounts->createExternalAccount($vendor->getStripeAcc(), [
            'external_account' => [
              "object" => "bank_account",
              "country" => "FR",
              "currency" => "eur",
              "account_number" => $param["number"]
              // "account_number" => "FR1420041010050500013M02606"
            ],
          ]);

          $bank = new BankAccount();
          $bank->setBankId($result->id);
          $bank->setLast4($result->last4);
          $bank->setCountry("FR");
          $bank->setCurrency("eur");
          $bank->setNumber($param["number"]);
          $bank->setVendor($vendor);
          
          $manager->persist($bank);
          $manager->flush();

          return $this->json(true, 200);
        }
      }
    }

    return $this->json(false, 404);
  }

  /**
   * @Route("/vendor/api/withdraw", name="vendor_api_withdraw")
   */
  public function withdraw(Request $request, ObjectManager $manager){
    if ($json = $request->getContent()) {
      $param = json_decode($json, true);

      if ($param) {
        $vendor = $this->getUser();
        dump($vendor->getBankAccounts());

        if ($vendor->getStripeAcc() && $vendor->getBankAccounts()) {
          \Stripe\Stripe::setApiKey($this->getParameter('stripe_sk'));

          $payout = \Stripe\Payout::create([
            'amount' => $vendor->getAvailable() * 100,
            'currency' => 'eur',
          ], [
            'stripe_account' => $vendor->getStripeAcc(),
          ]);

          dd($payout);

          $vendor->setAvailable("0.00");

          $withdraw = new Withdraw();
          $withdraw->setPayoutId($payout->id);
          $withdraw->setAmount($vendor->getAvailable());
          $withdraw->setStatus("completed");
          $withdraw->setLast4($vendor->getBankAccounts()[0]->getLast4());
          
          $manager->persist($withdraw);
          $manager->flush();

          return $this->json(true, 200);
        }
      }
    }

    return $this->json(false, 404);
  }
}
