<?php

namespace App\Controller\Admin;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;


class DashboardController extends Controller {

  /**
   * @Route("/admin/dashboard", name="admin_dashboard")
   */
  public function dashboard(){
    return $this->render('admin/dashboard.html.twig');
  }


  /**
   * @Route("/admin/login", name="admin_login")
   */
  public function login(){
    return $this->render('admin/login.html.twig');
  }


  /**
   * @Route("/admin/facebook", name="admin_facebook")
   */
  public function facebook(){

    $url = "https://streaming-graph.facebook.com/5855641177851710/live_comments?access_token=EAANUL41N2bIBAAmVkR3VzfyfI3V1RGHwxXzpvPiY44IAhdFFVZAqTSxyGa10LWRUe84sOJKLnevO3gYbc4oXyxjqjgEZA28jstyCWmzv8SWydK1duq7h473cWc6gzVbXfsNzEb7ZCPfU2eMZAsmKG9A5Fi3jsmoBEAaaGFQ2rV1yPwQzQu9uCDoir8B9HJYUSZBsZBJth4fm7b78OAZAK9bcjuPR5TLZA8MZD&comment_rate=one_per_two_seconds&fields=from{name,id},message";
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_URL, $url);

    $result = curl_exec($ch);
    var_dump($result);

    $result = json_decode($result);
    curl_close($ch);

    var_dump($result);

    if ($result) {
      echo $result;
    }

  //   $facebook = new \Facebook\Facebook([
  //     'app_id' => '936988141017522',
  //     'app_secret' => '025a5522cd75e464437fb048ee3cfe23',
  //     'default_graph_version' => 'v10.0',
  //   ]);

  //   $live_video_id = '5855435604538934';
  //   $access_token = 'EAANUL41N2bIBAOLhx8ca9ZBuZAJUu9DpGA9XDXGTZCm6SOvJOZC0gxaVzIuyQWmVZB4S7GtfDJRMSRZC9ppp71LQz2oGZBrX3MUzgd2svgYW7430t4RFIVoevsKJ8MCeNsX0uQ3NIoBTgM6OVluUBWfykhQQJs38BMKsN9dpINI7xm6DBQU0iaZAzKFbXJIKqjkEKzmbQWJ08RGvNMFgRgmdoDMnLgrvyEBkcFTpZCgd8jVQfS9DAd34dFBnOZBn7cExYZD';
  //   $appToken = '936988141017522|5Jia8pEjLLz2StmJ2EIpp6e8ZR8';
  //   $url = '/' . '936988141017522' . '/subscriptions';

  //   try {
  //     // Subscribe to live_comments webhook
  //     $response = $facebook->post($url, [ 'object' => 'live_video',
  //         'callback_url' => 'https://swipelive.fr/api/facebook/webhooks',
  //         'fields' => 'comments',
  //         'verify_token' => 'thisisaverifystring'
  //       ], $appToken);

  //     // Get live video comments
  //     $comments = $facebook->get(
  //       '/' . $live_video_id . '/comments',
  //       $access_token
  //     )->getGraphEdge();
  //     foreach ($comments as $comment) {
  //       var_dump("Comment: " . $comment->getField('message') . "\n");
  //     }
  //   } catch (Facebook\Exceptions\FacebookResponseException $e) {
  //     var_dump('Graph returned an error: ' . $e->getMessage());
  //   } catch (Facebook\Exceptions\FacebookSDKException $e) {
  //     var_dump('Facebook SDK returned an error: ' . $e->getMessage());
  //   }

    return $this->json(false, 404);
  }
}

