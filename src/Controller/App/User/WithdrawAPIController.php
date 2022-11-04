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
      if ($available == ($user->getAvailable() * 100)) {
        // dump("identique");
      } else {
        // dump($available);
        // dump($user->getAvailable() * 100);
      }

      // $user->setAvailable("0.00");

      // check amount payout
      $withdraw = new Withdraw();
      // $withdraw->setPayoutId($payout->id);
      $withdraw->setAmount($user->getAvailable());
      $withdraw->setStatus("completed");
      $withdraw->setLast4($user->getBankAccounts()[0]->getLast4());
      $withdraw->setVendor($user);

      $manager->persist($withdraw);
      $manager->flush();

      return $this->json(true, 200);
    }

    return $this->json(false, 404);
  }
}
