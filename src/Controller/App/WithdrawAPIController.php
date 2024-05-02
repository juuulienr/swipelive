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
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Config\Definition\Exception\Exception;


class WithdrawAPIController extends AbstractController {

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
            try {
              $stripe = new \Stripe\StripeClient($this->getParameter('stripe_sk'));
              $payout = $stripe->payouts->create(
                ['amount' => $withdrawAmount * 100, 'currency' => $bank->getCurrency() ],
                ['stripe_account' => $vendor->getStripeAcc() ]
              );

              $vendor->setAvailable($vendor->getAvailable() - $withdrawAmount);

              // check amount payout
              $withdraw = new Withdraw();
              $withdraw->setAmount($withdrawAmount);
              $withdraw->setStatus("succeeded");
              $withdraw->setLast4($bank->getLast4());
              $withdraw->setPayoutId($payout->id);
              $withdraw->setVendor($vendor);

              $manager->persist($withdraw);
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


  /**
   * @Route("/user/api/verification/document/front", name="user_api_verification_front")
   */
  public function verifFront(Request $request, ObjectManager $manager) {
    if ($json = $request->getContent()) {
      $param = json_decode($json, true);

      if ($param) {
        try {
          $vendor = $this->getUser()->getVendor();
          $stripe = new \Stripe\StripeClient($this->getParameter('stripe_sk'));
          $stripe->accounts->updatePerson($vendor->getStripeAcc(), $vendor->getPersonId(), [ 'person_token' => $param['person_token'] ]);

          return $this->json(true, 200);
        } catch (\Exception $e) {
          return $this->json($e->getMessage(), 404);
        }
      }
    }

    return $this->json("Le document est introuvable !", 404);
  }


  /**
   * @Route("/user/api/verification/document/back", name="user_api_verification_back")
   */
  public function verifBack(Request $request, ObjectManager $manager) {
    if ($json = $request->getContent()) {
      $param = json_decode($json, true);

      if ($param) {
        try {
          $vendor = $this->getUser()->getVendor();
          $stripe = new \Stripe\StripeClient($this->getParameter('stripe_sk'));
          $update = $stripe->accounts->updatePerson($vendor->getStripeAcc(), $vendor->getPersonId(), [ 'person_token' => $param['person_token'] ]);

          return $this->json(true, 200);
        } catch (\Exception $e) {
          return $this->json($e->getMessage(), 404);
        }
      }
    }

    return $this->json("Le document est introuvable !", 404);
  }


  /**
   * @Route("/user/api/verification/company/document", name="user_api_verification_company_document")
   */
  public function verifCompany(Request $request, ObjectManager $manager){
    if ($request->files->get('document')) {
      $file = $request->files->get('document');

      if (!$file) {
        return $this->json("Le document est introuvable !", 404);
      }

      // $filename = md5(time().uniqid()). "." . $file->guessExtension(); 
      // $filepath = $this->getParameter('uploads_directory') . '/' . $filename;
      // file_put_contents($filepath, file_get_contents($file));

      // $upload = new Upload();
      // $upload->setFilename($filename);

      // $manager->persist($upload);
      // $manager->flush();

      return $this->json($upload, 200);
    }

    return $this->json("Le document est introuvable !", 404);
  }
}
