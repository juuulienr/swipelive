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
   * @Route("/user/api/bank/add", name="user_api_bank_add")
   */
  public function addBank(Request $request, ObjectManager $manager){
    if ($json = $request->getContent()) {
      $param = json_decode($json, true);

      if ($param) {
        $vendor = $this->getUser()->getVendor();
        $oldBank = $bankRepo->findOneByVendor($vendor);

        if ($vendor->getStripeAcc() && $param["number"]) {
          $stripe = new \Stripe\StripeClient($this->getParameter('stripe_sk'));
          $result = $stripe->accounts->createExternalAccount($vendor->getStripeAcc(), [
            'external_account' => [
              "object" => "bank_account",
              "country" => "FR",
              "currency" => "eur",
              "account_number" => $param["number"]
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

          if ($oldBank) {
            $result = $stripe->accounts->deleteExternalAccount($user->getStripeAcc(), $oldBank->getBankId(), []);
            $manager->remove($oldBank);
            $manager->flush();
          }

          return $this->json(true, 200);
        }
      }
    }

    return $this->json(false, 404);
  }


  /**
   * @Route("/user/api/withdraw", name="user_api_withdraw")
   */
  public function withdraw(Request $request, ObjectManager $manager){
    $user = $this->getUser();

    if ($user->getStripeAcc() && sizeof($user->getBankAccounts()->toArray()) > 0) {
      \Stripe\Stripe::setApiKey($this->getParameter('stripe_sk'));

      $balance = \Stripe\Balance::retrieve(
        ['stripe_account' => $user->getStripeAcc()]
      );

      $available = $balance->available[0]->amount;
      $pending = $balance->pending[0]->amount;

      if ($available == ($user->getAvailable() * 100)) {
        dump("identique");
      } else {
        dump($available);
        dump($user->getAvailable() * 100);
      }

      try {
        $payout = \Stripe\Payout::create([
          // 'amount' => $user->getAvailable() * 100,
          'amount' => 1000,
          'currency' => 'eur',
        ], [
          'stripe_account' => $user->getStripeAcc(),
        ]);

        dump($payout);

        $user->setAvailable("0.00");

        // check amount payout
        $withdraw = new Withdraw();
        $withdraw->setPayoutId($payout->id);
        $withdraw->setAmount($user->getAvailable());
        $withdraw->setStatus("completed");
        $withdraw->setLast4($user->getBankAccounts()[0]->getLast4());
        $withdraw->setVendor($user);

        $manager->persist($withdraw);
        $manager->flush();

      } catch(\Stripe\Exception\CardException $e) {
        // Since it's a decline, \Stripe\Exception\CardException will be caught
        dump($e->getHttpStatus());
        dump($e->getError()->type);
        dump($e->getError()->code);
        dump($e->getError()->message);
      } catch (\Stripe\Exception\RateLimitException $e) {
        // Too many requests made to the API too quickly
        dump($e->getHttpStatus());
        dump($e->getError()->type);
        dump($e->getError()->code);
        dump($e->getError()->message);
      } catch (\Stripe\Exception\InvalidRequestException $e) {
        dump($e->getHttpStatus());
        dump($e->getError()->type);
        dump($e->getError()->code);
        dump($e->getError()->message);
        // Invalid parameters were supplied to Stripe's API
      } catch (\Stripe\Exception\AuthenticationException $e) {
        dump($e->getHttpStatus());
        dump($e->getError()->type);
        dump($e->getError()->code);
        dump($e->getError()->message);
        // Authentication with Stripe's API failed
        // (maybe you changed API keys recently)
      } catch (\Stripe\Exception\ApiConnectionException $e) {
        // Network communication with Stripe failed
        dump($e->getHttpStatus());
        dump($e->getError()->type);
        dump($e->getError()->code);
        dump($e->getError()->message);
      } catch (\Stripe\Exception\ApiErrorException $e) {
        dump($e->getHttpStatus());
        dump($e->getError()->type);
        dump($e->getError()->code);
        dump($e->getError()->message);

        // Display a very generic error to the user, and maybe send
        // yourself an email
      } catch (Exception $e) {
        dump($e);
        // Something else happened, completely unrelated to Stripe
      }

      return $this->json(true, 200);
    }

    return $this->json(false, 404);
  }


  // /**
  //  * @Route("/user/api/verification/document/front", name="user_api_verification_document_front")
  //  */
  // public function verifFront(Request $request, ObjectManager $manager) {
  //   if ($json = $request->getContent()) {
  //     $param = json_decode($json, true);

  //     if ($param) {
  //       $personToken = $param['person_token'];
  //       $user = $this->getUser();

  //       if ($personToken && $user->getStripeAcc()) {
  //         $stripe = new \Stripe\StripeClient($this->getParameter('stripe_sk'));
  //         $update = $stripe->accounts->updatePerson($user->getStripeAcc(), $user->getPersonId(), [ 'person_token' => $personToken ]);
  //       }

  //       return $this->json(true, 200);
  //     }
  //   }

  //   return $this->json("Le document est introuvable !", 404);
  // }


  // /**
  //  * @Route("/user/api/verification/document/back", name="user_api_verification_document_back")
  //  */
  // public function verifBack(Request $request, ObjectManager $manager) {
  //   if ($json = $request->getContent()) {
  //     $param = json_decode($json, true);

  //     if ($param) {
  //       $personToken = $param['person_token'];
  //       $user = $this->getUser();

  //       if ($personToken && $user->getStripeAcc()) {
  //         $stripe = new \Stripe\StripeClient($this->getParameter('stripe_sk'));
  //         $update = $stripe->accounts->updatePerson($user->getStripeAcc(), $user->getPersonId(), [ 'person_token' => $personToken ]);
  //       }

  //       return $this->json(true, 200);
  //     }
  //   }

  //   return $this->json("Le document est introuvable !", 404);
  // }


  // /**
  //  * @Route("/user/api/verification/company/document", name="user_api_verification_company_document")
  //  */
  // public function verifCompany(Request $request, ObjectManager $manager){
  //   if ($request->files->get('document')) {
  //     $file = $request->files->get('document');

  //     if (!$file) {
  //       return $this->json("Le document est introuvable !", 404);
  //     }

  //     // $filename = md5(time().uniqid()). "." . $file->guessExtension(); 
  //     // $filepath = $this->getParameter('uploads_directory') . '/' . $filename;
  //     // file_put_contents($filepath, file_get_contents($file));

  //     // $upload = new Upload();
  //     // $upload->setFilename($filename);

  //     // $manager->persist($upload);
  //     // $manager->flush();

  //     return $this->json($upload, 200);
  //   }

  //   return $this->json("Le document est introuvable !", 404);
  // }
}
