<?php 

namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\Common\Persistence\ObjectManager;
use App\Repository\LiveRepository;

class RemoveWaitingLive extends ContainerAwareCommand
{
    private $repo;

    public function __construct(LiveRepository $repo, ObjectManager $manager)
    {
        $this->manager = $manager;
        $this->repo = $repo;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('remove:waiting:live')
            ->setDescription('Supprimer les lives en attente depuis 1 jour')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $lives = $this->repo->findAll();
        $now = new \DateTime('now', timezone_open('Europe/Paris'));
        $now->modify('-1 day');

        if ($lives) {
            foreach ($lives as $live) {
                if ($live->getCreatedAt() < $now && $live->getStatus() == 0) {
                    $liveProducts = $live->getLiveProducts();

                    if ($liveProducts) {
                        foreach ($liveProducts as $liveProduct) {
                            $this->manager->remove($liveProduct);
                        }
                    }
                    $this->manager->flush();
                    $this->manager->remove($live);
                }             
            }
        }
        
        $this->manager->flush();
    }
}