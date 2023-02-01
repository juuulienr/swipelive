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
}

