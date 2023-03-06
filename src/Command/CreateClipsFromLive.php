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

    if ($clips) {
      foreach ($clips as $clip) {
        // creation du clip sur bambuser
        if ($clip->getCreatedAt()->modify('+2 minutes') < $now && $clip->getStatus() != "available") {
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
          $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
          $result = json_decode($result);
          curl_close($ch);

          if ($result && $result->newBroadcastId && $httpcode !== 500) {
            if (!$clip->getBroadcastId()) {
              $clip->setBroadcastId($result->newBroadcastId);
            }
            if ($result->status == "ok") {
              $clip->setStatus("available");
            } else {
              $clip->setStatus($result->status);
            }
            $this->manager->flush();
          }
        }
      }
    }
  }
}