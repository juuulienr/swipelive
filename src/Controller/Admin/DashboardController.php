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

    $live_video_id = '5855435604538934';
    $access_token = 'EAANUL41N2bIBAOLhx8ca9ZBuZAJUu9DpGA9XDXGTZCm6SOvJOZC0gxaVzIuyQWmVZB4S7GtfDJRMSRZC9ppp71LQz2oGZBrX3MUzgd2svgYW7430t4RFIVoevsKJ8MCeNsX0uQ3NIoBTgM6OVluUBWfykhQQJs38BMKsN9dpINI7xm6DBQU0iaZAzKFbXJIKqjkEKzmbQWJ08RGvNMFgRgmdoDMnLgrvyEBkcFTpZCgd8jVQfS9DAd34dFBnOZBn7cExYZD';
    $appToken = '936988141017522|5Jia8pEjLLz2StmJ2EIpp6e8ZR8';
    $url = '/' . '936988141017522' . '/subscriptions';

    try {
    // Subscribe to live_comments webhook
      $response = $facebook->post($url, [ 'object' => 'live_video',
          'callback_url' => 'https://swipelive.fr/api/facebook/webhooks',
          'fields' => 'comments',
          'verify_token' => 'thisisaverifystring'
        ],$appToken);
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

