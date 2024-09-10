<?php 

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\LiveRepository;
use Psr\Log\LoggerInterface;

class RemoveWaitingLive extends Command
{
  protected static $defaultName = 'remove:lives';
  private $liveRepo;
  private $entityManager;
  private $logger;

  public function __construct(LiveRepository $liveRepo, EntityManagerInterface $entityManager, LoggerInterface $logger)
  {
    parent::__construct();
    $this->liveRepo = $liveRepo;
    $this->entityManager = $entityManager;
    $this->logger = $logger;
  }

  protected function configure()
  {
    $this
    ->setDescription('Delete lives without clips that are older than 1 day with specific statuses');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    // Set the threshold date to 1 day ago
    $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    $thresholdDate = $now->modify('-1 day');

    // Fetch all lives
    $lives = $this->liveRepo->findAll();
    $deletedCount = 0;

    foreach ($lives as $live) {
      if ($this->shouldRemoveLive($live, $thresholdDate)) {
        $this->removeLiveDependencies($live);
        $this->entityManager->remove($live);
        $deletedCount++;
      }
    }

    $this->entityManager->flush();
    $output->writeln("$deletedCount lives removed.");

    // Log the removal action
    $this->logger->info("$deletedCount lives were removed due to missing clips.");

    return Command::SUCCESS;
  }

  private function shouldRemoveLive($live, \DateTimeImmutable $thresholdDate): bool
  {
    return $live->getCreatedAt() < $thresholdDate 
    && in_array($live->getStatus(), [0, 2]) 
    && $live->getClips()->isEmpty();
  }

  private function removeLiveDependencies($live): void
  {
        // Remove live products
    foreach ($live->getLiveProducts() as $liveProduct) {
      $this->entityManager->remove($liveProduct);
    }

        // Remove comments
    foreach ($live->getComments() as $comment) {
      $this->entityManager->remove($comment);
    }
  }
}
