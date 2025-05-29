<?php

declare(strict_types=1);

namespace App\Controller\App;

use App\Entity\BankAccount;
use App\Entity\User;
use App\Entity\Withdraw;
use App\Repository\BankAccountRepository;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Stripe\StripeClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class WithdrawAPIController extends AbstractController
{
  public function getUser(): ?User
  {
    $user = parent::getUser();

    return $user instanceof User ? $user : null;
  }

  /**
   * @Route("/user/api/bank/add", name="user_api_bank_add")
   */
  public function addBank(Request $request, ObjectManager $manager, BankAccountRepository $bankRepo): JsonResponse
  {
    if ($json = $request->getContent()) {
      $param = \json_decode($json, true);

      if ($param) {
        $vendor   = $this->getUser()->getVendor();
        $oldBanks = $bankRepo->findByVendor($vendor);

        $bank = new BankAccount();
        $bank->setLast4($param['last4']);
        $bank->setCountryCode($param['countryCode']);
        $bank->setCurrency('eur');
        $bank->setNumber($param['number']);
        $bank->setHolderName($param['holderName']);
        $bank->setBusinessName($param['businessName']);
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
          $iban       = $bank->getCountryCode() . $bank->getNumber();
          $stripe     = new StripeClient($this->getParameter('stripe_sk'));
          $stripeBank = $stripe->accounts->createExternalAccount($vendor->getStripeAcc(), [
            'external_account' => [
              'object'               => 'bank_account',
              'default_for_currency' => true,
              'country'              => $bank->getCountryCode(),
              'currency'             => $bank->getCurrency(),
              'account_holder_name'  => 'individual' === $vendor->getBusinessType() ? $bank->getHolderName() : $bank->getBusinessName(),
              'account_holder_type'  => $vendor->getBusinessType(),
              'account_number'       => $iban,
            ],
          ]);

          $bank->setBankId((string) $stripeBank->id);
          $manager->flush();
        } catch (Exception $e) {
          return $this->json($e->getMessage(), 404);
        }

        return $this->json($this->getUser(), 200, [], [
          'groups'                     => 'user:read',
          'circular_reference_limit'   => 1,
          'circular_reference_handler' => fn ($object) => $object->getId(),
        ]);
      }
    }

    return $this->json(false, 404);
  }

  /**
   * @Route("/user/api/withdraw", name="user_api_withdraw")
   */
  public function withdraw(Request $request, ObjectManager $manager, BankAccountRepository $bankRepo): JsonResponse
  {
    if ($json = $request->getContent()) {
      $param = \json_decode($json, true);

      if ($param) {
        $withdrawAmount = $param['withdrawAmount'];
        $vendor         = $this->getUser()->getVendor();
        $bank           = $bankRepo->findOneByVendor($vendor);

        if ($bank) {
          if ($vendor->getAvailable() >= $withdrawAmount) {
            try {
              $stripe = new StripeClient($this->getParameter('stripe_sk'));
              $payout = $stripe->payouts->create(
                ['amount' => (int) ($withdrawAmount * 100), 'currency' => $bank->getCurrency()],
                ['stripe_account' => $vendor->getStripeAcc()]
              );

              $vendor->setAvailable((string) ($vendor->getAvailable() - $withdrawAmount));

              // check amount payout
              $withdraw = new Withdraw();
              $withdraw->setAmount($withdrawAmount);
              $withdraw->setStatus('succeeded');
              $withdraw->setLast4($bank->getLast4());
              $withdraw->setPayoutId((string) $payout->id);
              $withdraw->setVendor($vendor);

              $manager->persist($withdraw);
              $manager->flush();

              return $this->json($this->getUser(), 200, [], [
                'groups'                     => 'user:read',
                'circular_reference_limit'   => 1,
                'circular_reference_handler' => fn ($object) => $object->getId(),
              ]);
            } catch (Exception $e) {
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
  public function verifFront(Request $request, ObjectManager $manager): JsonResponse
  {
    if ($json = $request->getContent()) {
      $param = \json_decode($json, true);

      if ($param) {
        try {
          $vendor = $this->getUser()->getVendor();
          $stripe = new StripeClient($this->getParameter('stripe_sk'));
          $stripe->accounts->updatePerson($vendor->getStripeAcc(), $vendor->getPersonId(), ['person_token' => $param['person_token']]);

          return $this->json(true, 200);
        } catch (Exception $e) {
          return $this->json($e->getMessage(), 404);
        }
      }
    }

    return $this->json('Le document est introuvable !', 404);
  }

  /**
   * @Route("/user/api/verification/document/back", name="user_api_verification_back")
   */
  public function verifBack(Request $request, ObjectManager $manager): JsonResponse
  {
    if ($json = $request->getContent()) {
      $param = \json_decode($json, true);

      if ($param) {
        try {
          $vendor = $this->getUser()->getVendor();
          $stripe = new StripeClient($this->getParameter('stripe_sk'));
          $update = $stripe->accounts->updatePerson($vendor->getStripeAcc(), $vendor->getPersonId(), ['person_token' => $param['person_token']]);

          return $this->json(true, 200);
        } catch (Exception $e) {
          return $this->json($e->getMessage(), 404);
        }
      }
    }

    return $this->json('Le document est introuvable !', 404);
  }

  /**
   * @Route("/user/api/verification/company/document", name="user_api_verification_company_document")
   */
  public function verifCompany(Request $request, ObjectManager $manager): JsonResponse
  {
    $file = $request->files->get('document');

    if (!$file) {
      return $this->json('Le document est introuvable !', 404);
    }

    // Code commenté pour le moment
    // $filename = md5(time().uniqid()). "." . $file->guessExtension();
    // $filepath = $this->getParameter('uploads_directory') . '/' . $filename;
    // file_put_contents($filepath, file_get_contents($file));
    // $upload = new Upload();
    // $upload->setFilename($filename);
    // $manager->persist($upload);
    // $manager->flush();

    return $this->json(true, 200);
  }
}
