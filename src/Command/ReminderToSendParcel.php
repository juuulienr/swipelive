<?php 

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\FirebaseMessagingService;
use App\Repository\OrderRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ReminderToSendParcel extends Command
{
  protected static $defaultName = 'reminder:send:parcel';
  private $orderRepo;
  private $entityManager;
  private $firebaseMessagingService;
  private $parameterBag;
  private $logger;
  private $bugsnag;

  public function __construct(
    OrderRepository $orderRepo,
    EntityManagerInterface $entityManager,
    FirebaseMessagingService $firebaseMessagingService,
    ParameterBagInterface $parameterBag,
    LoggerInterface $logger,
    \Bugsnag\Client $bugsnag
  ) {
    parent::__construct();
    $this->orderRepo = $orderRepo;
    $this->entityManager = $entityManager;
    $this->firebaseMessagingService = $firebaseMessagingService;
    $this->parameterBag = $parameterBag;
    $this->logger = $logger;
    $this->bugsnag = $bugsnag;
  }

  protected function configure(): void
  {
    $this->setDescription('Reminder to print label and send parcel.');
  }

  protected function execute(InputInterface $input, OutputInterface $output): int
  {
    $orders = $this->orderRepo->findAll();
    $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

    foreach ($orders as $order) {
      if ($this->shouldRemindOrCancel($order)) {
        $createdAt = $order->getCreatedAt();
        if ($createdAt->modify('+7 days') < $now) {
          $this->cancelOrder($order);
        }
        // } elseif ($createdAt->modify('+4 days') < $now) {
        //   $this->sendSecondReminder($order);
        // } elseif ($createdAt->modify('+2 days') < $now) {
        //   $this->sendFirstReminder($order);
        // }
      }
    }

    return Command::SUCCESS;
  }

  private function shouldRemindOrCancel($order): bool
  {
    return $order->getShippingStatus() !== 'cancelled' 
    && $order->getStatus() !== 'cancelled'
    && !$order->getTrackingNumber() 
    && !$order->getPdf();
  }

  private function cancelOrder($order): void
  {
    $order->setStatus('cancelled');
    $this->entityManager->flush();

    $this->refundCustomer($order);
    $this->sendPushNotification(
      $order->getVendor()->getUser()->getPushToken(),
      'Commande annulée, le client a été remboursé',
      $order
    );

    $this->sendPushNotification(
      $order->getBuyer()->getPushToken(),
      'Commande annulée, le vendeur n\'a pas envoyé le colis. Vous allez être remboursé',
      $order
    );

    $this->logger->info('Order cancelled and customer refunded for order ID: ' . $order->getId());
  }

  private function refundCustomer($order): void
  {
    try {
      // Récupérer la clé secrète Stripe depuis les paramètres
      $stripeSecretKey = $this->parameterBag->get('stripe_sk');
      $stripe = new \Stripe\StripeClient($stripeSecretKey);
      $stripe->refunds->create([
        'payment_intent' => $order->getPaymentId(),
      ]);
    } catch (\Exception $error) {
      $this->logger->error('Failed to refund order ID: ' . $order->getId(), ['exception' => $error]);
      $this->bugsnag->notifyException($error);
    }
  }

  private function sendFirstReminder($order): void
  {
    $this->sendPushNotification(
      $order->getVendor()->getUser()->getPushToken(),
      'N’oublie pas d’imprimer le bon de livraison et d’expédier ta commande',
      $order
    );
    $this->logger->info('First reminder sent for order ID: ' . $order->getId());
  }

  private function sendSecondReminder($order): void
  {
    $this->sendPushNotification(
      $order->getVendor()->getUser()->getPushToken(),
      'Plus que 24h pour expédier ta commande ou elle sera annulée',
      $order
    );
    $this->logger->info('Second reminder sent for order ID: ' . $order->getId());
  }

  private function sendPushNotification(?string $pushToken, string $message, $order): void
  {
    if ($pushToken) {
      try {
        $data = [
          'route' => 'ListOrders',
          'isOrder' => true,
          'orderId' => $order->getId()
        ];

        $this->firebaseMessagingService->sendNotification('SWIPE LIVE', $message, $pushToken, $data);
      } catch (\Exception $error) {
        $this->logger->error('Failed to send push notification', ['exception' => $error]);
        $this->bugsnag->notifyException($error);
      }
    }
  }
}

