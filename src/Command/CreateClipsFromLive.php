<?php 

namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\Common\Persistence\ObjectManager;
use App\Repository\ClipRepository;
use App\Repository\Clip;

class CreateClipsFromLive extends ContainerAwareCommand
{
    private $repo;

    public function __construct(ClipRepository $repo, ObjectManager $manager)
    {
        $this->manager = $manager;
        $this->repo = $repo;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('create:clips')
            ->setDescription('Créer les clips depuis un live')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $clips = $this->repo->findBy([ "status" => "waiting" ]);
        $now = new \DateTime('now', timezone_open('Europe/Paris'));

        if ($clips) {
            foreach ($clips as $clip) {
                $createdAt = $clip->getCreatedAt();

                // creation du clip sur bambuser
                if (!$clip->getBroadcastId() && $createdAt->modify('+5 minutes') < $now && $clip->getStatus() == "waiting") {
                    $data = [
                      "source" => [
                        "broadcastId" => $clip->getLive()->getBroadcastId(), 
                        "start" => $clip->getStart(), 
                        "end" => $clip->getEnd()
                      ]
                    ];

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "Accept: application/vnd.bambuser.v1+json", "Authorization: Bearer 2NJko17PqQdCDQ1DRkyMYr"]);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                    curl_setopt($ch, CURLOPT_URL, "https://api.bambuser.com/broadcasts");

                    $result = curl_exec($ch);
                    $result = json_decode($result);
                    curl_close($ch);

                    if ($result && $result->newBroadcastId) {
                        $clip->setBroadcastId($result->newBroadcastId);
                    }

                    $this->manager->flush();
                }

                // mise à jour du clip
                if ($clip->getBroadcastId() && $clip->getResourceUri() && $createdAt->modify('+15 minutes') < $now && $clip->getStatus() == "waiting") {
                    $clip->setStatus("available");
                    $this->manager->flush();
                }
            }
        }
    }
}