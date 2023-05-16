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
use App\Entity\Withdraw;
use App\Entity\LineItem;
use App\Entity\BankAccount;
use App\Repository\ClipRepository;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\BankAccountRepository;
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
   * @Route("/user/api/bank/add", name="user_api_bank_add")
   */
  public function addBank(Request $request, ObjectManager $manager, BankAccountRepository $bankRepo){
  	if ($json = $request->getContent()) {
  		$param = json_decode($json, true);

  		if ($param) {
  			$vendor = $this->getUser()->getVendor();
  			$oldBanks = $bankRepo->findByVendor($vendor);

				$bank = new BankAccount();
				$bank->setLast4($param["last4"]);
				$bank->setCountryCode($param["countryCode"]);
				$bank->setCurrency("eur");
				$bank->setNumber($param["number"]);
        $bank->setHolderName($param["holderName"]);
        $bank->setBusinessName($param["businessName"]);
				$bank->setVendor($vendor);
				
				$manager->persist($bank);
				$manager->flush();

        if ($oldBanks) {
          foreach ($oldBanks as $oldBank) {
            $manager->remove($oldBank);
            $manager->flush();
          }
        }

        try {
          $iban = $bank->getCountryCode() . $bank->getNumber();
          $stripe = new \Stripe\StripeClient($this->getParameter('stripe_sk'));
          $stripeBank = $stripe->accounts->createExternalAccount($vendor->getStripeAcc(), [
            'external_account' => [
              'object' => 'bank_account',
              'default_for_currency' => true,
              'country' => $bank->getCountryCode(),
              'currency' => $bank->getCurrency(),
              'account_holder_name' => $vendor->getBusinessType() === "individual" ? $bank->getHolderName() : $bank->getBusinessName(),
              'account_holder_type' => $vendor->getBusinessType(),
              'account_number' => $iban,
            ],
          ]);

          $bank->setBankId($stripeBank->id);
          $manager->flush();
        } catch (\Exception $e) {
          return $this->json($e->getMessage(), 404);
        }

        return $this->json($this->getUser(), 200, [], [
          'groups' => 'user:read', 
          'circular_reference_limit' => 1, 
          'circular_reference_handler' => function ($object) {
            return $object->getId();
          } 
        ]);
	  	}
  	}

  	return $this->json(false, 404);
  }


  /**
  * @Route("/user/api/withdraw", name="user_api_withdraw")
  */
  public function withdraw(Request $request, ObjectManager $manager, BankAccountRepository $bankRepo){
    if ($json = $request->getContent()) {
      $param = json_decode($json, true);

      if ($param) {
        $withdrawAmount = $param["withdrawAmount"];
        $vendor = $this->getUser()->getVendor();
        $bank = $bankRepo->findOneByVendor($vendor);

        if ($bank) {
          if ($vendor->getAvailable() >= $withdrawAmount) {
            $vendor->setAvailable($vendor->getAvailable() - $withdrawAmount);

            // check amount payout
            $withdraw = new Withdraw();
            $withdraw->setAmount($withdrawAmount);
            $withdraw->setStatus("completed");
            $withdraw->setLast4($bank->getLast4());
            $withdraw->setVendor($vendor);

            $manager->persist($withdraw);
            $manager->flush();

            try {
              $stripe = new \Stripe\StripeClient($this->getParameter('stripe_sk'));
              $payout = $stripe->payouts->create(
                ['amount' => 1000, 'currency' => $bank->getCurrency() ],
                ['stripe_account' => $vendor->getStripeAcc() ]
              );

              $withdraw->setPayoutId($payout->id);
              $manager->flush();

              return $this->json($this->getUser(), 200, [], [
                'groups' => 'user:read', 
                'circular_reference_limit' => 1, 
                'circular_reference_handler' => function ($object) {
                  return $object->getId();
                } 
              ]);
            } catch (\Exception $e) {
              return $this->json($e->getMessage(), 404);
            }
          } else {
            return $this->json("Le montant demandé est supérieur à l'argent disponible", 404);
          }
        }
      }
    }

    return $this->json(false, 404);
  }
}
