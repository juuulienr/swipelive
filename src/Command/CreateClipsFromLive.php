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

class CreateClipsFromLive extends ContainerAwareCommand {
  private $repo;

  public function __construct(ClipRepository $repo, ObjectManager $manager) {
    $this->manager = $manager;
    $this->repo = $repo;

    parent::__construct();
  }

  protected function configure() {
    $this
    ->setName('create:clips')
    ->setDescription('Créer les clips depuis un live')
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $clips = $this->repo->findBy([ "status" => "waiting" ]);
    $now = new \DateTime('now', timezone_open('UTC'));
    
    // $created = $live->getCreatedAt();
    // $now = new \DateTime('now', timezone_open('UTC'));
    // $diff = $now->diff($created);
    // var_dump($createdAt->modify('+10 minutes'));
    // var_dump($now);

    if ($clips) {
      foreach ($clips as $clip) {
        $createdAt = $clip->getCreatedAt();

        // creation du clip sur bambuser
        if (!$clip->getBroadcastId() && $createdAt->modify('+10 minutes') < $now) {
          $data = [
            "source" => [
              "broadcastId" => $clip->getLive()->getBroadcastId(), 
              "start" => $clip->getStart(), 
              "end" => $clip->getEnd()
            ]
          ];

          $ch = curl_init();
          curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "Accept: application/vnd.bambuser.v1+json", "Authorization: Bearer RkbHZdUPzA8Rcu2w4b1jn9"]);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
          curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
          curl_setopt($ch, CURLOPT_URL, "https://api.bambuser.com/broadcasts");

          $result = curl_exec($ch);
          $result = json_decode($result);
          curl_close($ch);

          if ($result && $result->newBroadcastId) {
            $clip->setBroadcastId($result->newBroadcastId);
            $this->manager->flush();
          }
        }

        // mise à jour du clip
        if ($clip->getBroadcastId() && $clip->getResourceUri() && $createdAt->modify('+20 minutes') < $now && $clip->getStatus() == "waiting") {
          $url = "https://api.bambuser.com/broadcasts/" . $clip->getBroadcastId();
          $title = "Clip" . $clip->getId();
          $data = [ 
            "author" => $clip->getVendor()->getBusinessName(), 
            "title" => $title 
          ];

          $ch = curl_init();
          curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "Accept: application/vnd.bambuser.v1+json", "Authorization: Bearer RkbHZdUPzA8Rcu2w4b1jn9"]);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
          curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
          curl_setopt($ch, CURLOPT_URL, $url);

          $result = curl_exec($ch);
          $result = json_decode($result);
          curl_close($ch);


          $clip->setStatus("available");
          $this->manager->flush();
        }
      }
    }
  }
}