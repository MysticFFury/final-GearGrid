<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class CustomerAboutController extends AbstractController
{
    #[Route('/customer-about', name: 'app_customer_about', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('customer/about.html.twig');
    }
}

