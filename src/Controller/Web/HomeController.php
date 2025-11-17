<?php

declare(strict_types=1);

namespace App\Controller\Web;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    /**
     * @Route("/", name="landing")
     */
    public function landing(): Response
    {
        return $this->render('web/landing.html.twig');
    }

    /**
     * @Route("/regles-communaute", name="rules")
     */
    public function rules(): Response
    {
        return $this->render('web/rules.html.twig');
    }
}
