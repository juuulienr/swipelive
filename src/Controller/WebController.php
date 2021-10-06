<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Persistence\ObjectManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Cookie;


class WebController extends Controller {

    /**
     * @Route("/", name="index")
     */
    public function index(){
        return $this->render('web/home.html.twig');
    }

    /**
     * @Route("/vendeur", name="vendor")
     */
    public function vendeur(){
        return $this->render('web/vendor.html.twig');
    }

    /**
     * @Route("/influenceur", name="influencer")
     */
    public function influenceur(){
        return $this->render('web/influencer.html.twig');
    }
}

