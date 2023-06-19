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
  private $clipRepo;

  public function __construct(ClipRepository $clipRepo, ObjectManager $manager) {
    $this->manager = $manager;
    $this->clipRepo = $clipRepo;

    parent::__construct();
  }

  protected function configure() {
    $this
    ->setName('create:clips')
    ->setDescription('Create clips from a live')
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $clips = $this->clipRepo->findAll();
    $now = new \DateTime('now', timezone_open('UTC'));

    if ($clips) {
      foreach ($clips as $clip) {
        // create clip
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

          if ($result && $httpcode !== 500) {
            if (!$clip->getBroadcastId() && $result->newBroadcastId) {
              $clip->setBroadcastId($result->newBroadcastId);
              $this->manager->flush();
            }

            // update broadcast
            if ($result->status == "ok" && $clip->getCreatedAt()->modify('+10 minutes') < $now) {
              $url = "https://api.bambuser.com/broadcasts/" . $clip->getBroadcastId();
              $ch = curl_init();

              curl_setopt($ch, CURLOPT_HTTPHEADER,["Content-Type: application/json","Accept: application/vnd.bambuser.v1+json","Authorization: Bearer RkbHZdUPzA8Rcu2w4b1jn9"]);
              curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
              curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
              curl_setopt($ch, CURLOPT_URL, $url);

              $result = curl_exec($ch);
              $result = json_decode($result);
              curl_close($ch);

              if ($result && $result->resourceUri) {
                $clip->setResourceUri($result->resourceUri);
                $clip->setStatus("available");

                if (!$clip->getPreview()) {
                  $clip->setPreview($clip->getLive()->getPreview());
                }
              }
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