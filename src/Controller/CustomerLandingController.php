<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class CustomerLandingController extends AbstractController
{
    #[Route('/customer-landing', name: 'app_customer_landing', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('customer/landing.html.twig');
    }
}

