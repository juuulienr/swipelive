<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\LiveRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveWaitingLive extends Command
{
    protected static $defaultName = 'remove:lives';

    public function __construct(private readonly LiveRepository $liveRepo, private readonly EntityManagerInterface $entityManager, private readonly LoggerInterface $logger)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
        ->setDescription('Delete lives without clips that are older than 1 day with specific statuses');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
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
                ++$deletedCount;
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
        && \in_array($live->getStatus(), [0, 2], true)
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
