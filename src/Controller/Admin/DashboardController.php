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
   * @Route("/admin/test", name="admin_test")
   */
  public function test(){
    $facebook = new \Facebook\Facebook([
      'app_id' => '936988141017522',
      'app_secret' => '025a5522cd75e464437fb048ee3cfe23',
      'default_graph_version' => 'v10.0',
    ]);

    $live_video_id = 'LIVE_VIDEO_ID';

    try {
    // Subscribe to live_comments webhook
      $response = $facebook->post(
        '/' . '936988141017522' . '/subscriptions',
        array(
          'object' => 'live_video',
          'callback_url' => 'https://swipelive.fr/api/facebook/webhooks',
          'fields' => 'comments',
          'verify_token' => 'knsdofi4d4sdf8fsq4q6e32d1sqd5sqd4f8',
        ),
        '025a5522cd75e464437fb048ee3cfe23'
      );
      var_dump("Successfully subscribed to live_comments webhook.");

    // Get live video comments
      $comments = $facebook->get(
        '/' . $live_video_id . '/comments',
        $access_token
      )->getGraphEdge();
      foreach ($comments as $comment) {
        var_dump("Comment: " . $comment->getField('message') . "\n");
      }
    } catch (Facebook\Exceptions\FacebookResponseException $e) {
      var_dump('Graph returned an error: ' . $e->getMessage());
    } catch (Facebook\Exceptions\FacebookSDKException $e) {
      var_dump('Facebook SDK returned an error: ' . $e->getMessage());
    }

    return $this->json(false, 404);
  }
}

