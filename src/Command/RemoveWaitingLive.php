<?php 

namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\Persistence\ObjectManager;
use App\Repository\LiveRepository;

class RemoveWaitingLive extends ContainerAwareCommand
{
  private $liveRepo;

  public function __construct(LiveRepository $liveRepo, ObjectManager $manager)
  {
    $this->manager = $manager;
    $this->liveRepo = $liveRepo;

    parent::__construct();
  }

  protected function configure()
  {
    $this
    ->setName('remove:lives')
    ->setDescription('Delete live without clip')
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $lives = $this->liveRepo->findAll();
    $now = new \DateTime('now', timezone_open('UTC'));
    $now->modify('-1 day');

    if ($lives) {
      foreach ($lives as $live) {
        if ($live->getCreatedAt() < $now) {
          if ($live->getStatus() == 0 || $live->getStatus() == 2) {
            $clips = $live->getClips()->toArray();

            if (!$clips) {
              $liveProducts = $live->getLiveProducts();
              $comments = $live->getComments();

              if ($liveProducts) {
                foreach ($liveProducts as $liveProduct) {
                  $this->manager->remove($liveProduct);
                }
              }

              if ($comments) {
                foreach ($comments as $comment) {
                  $this->manager->remove($comment);
                }
              }

              $this->manager->remove($live);
            }
          }             
        }
      }
    }
    
    $this->manager->flush();
  }
}