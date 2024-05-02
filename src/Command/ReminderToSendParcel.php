<?php 

namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\Persistence\ObjectManager;
use App\Service\NotifPushService;
use App\Repository\OrderRepository;
use App\Repository\Clip;

class ReminderToSendParcel extends ContainerAwareCommand {
  private $orderRepo;

  public function __construct(OrderRepository $orderRepo, ObjectManager $manager, NotifPushService $notifPushService) {
    $this->manager = $manager;
    $this->orderRepo = $orderRepo;
    $this->notifPushService = $notifPushService;

    parent::__construct();
  }

  protected function configure() {
    $this
    ->setName('reminder:send:parcel')
    ->setDescription('Reminder to print label and send parcel')
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $orders = $this->orderRepo->findAll();
    $now = new \DateTime('now', timezone_open('UTC'));

    if ($orders) {
      foreach ($orders as $order) {
        if ($order->getShippingStatus() != "cancelled" && $order->getStatus() != "cancelled" && !$order->getTrackingNumber() && !$order->getPdf()) {
          $pushToken = $order->getVendor()->getUser()->getPushToken();

          if ($order->getCreatedAt()->modify('+7 days') < $now) {
            // cancel order
            $order->setStatus('cancelled');
            $this->manager->flush();

            // refund customer
            try {
              $stripe = new \Stripe\StripeClient($this->getParameter('stripe_sk'));
              $stripe->refunds->create([
                'payment_intent' => $order->getPaymentId(),
              ]);
            } catch (\Exception $error) {
              $this->get('bugsnag')->notifyError('ErrorType', $error);
            }

            if ($pushToken) {
              try {
                $this->notifPushService->send("SWIPE LIVE", "Commande annulée, le client à été remboursé", $pushToken);
              } catch (\Exception $error) {
                $this->get('bugsnag')->notifyError('ErrorType', $error);
              }
            }

            if ($order->getBuyer()->getPushToken()) {
              try {
                $this->notifPushService->send("SWIPE LIVE", "Commande annulée, le vendeur n'a pas envoyé le colis. Vous allez être remboursé", $order->getBuyer()->getPushToken());
              } catch (\Exception $error) {
                $this->get('bugsnag')->notifyError('ErrorType', $error);
              }
            }
          } elseif ($order->getCreatedAt()->modify('+4 days') < $now) {
            // 2nd reminder
            if ($pushToken) {
              try {
                $this->notifPushService->send("SWIPE LIVE", "Plus que 24h pour expédier ta commande ou elle sera annulé", $pushToken);
              } catch (\Exception $error) {
                $this->get('bugsnag')->notifyError('ErrorType', $error);
              }
            }
          } else if ($order->getCreatedAt()->modify('+2 days') < $now) {
            // 1st reminder
            if ($pushToken) {
              try {
                $this->notifPushService->send("SWIPE LIVE", "N’oublie pas d’imprimer le bon de livraison et d’expédier ta commande", $pushToken);
              } catch (\Exception $error) {
                $this->get('bugsnag')->notifyError('ErrorType', $error);
              }
            }
          }
        }
      }
    }
  }
}