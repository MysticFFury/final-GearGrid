<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class CustomerServicesController extends AbstractController
{
    #[Route('/customer-services', name: 'app_customer_services', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('customer/services.html.twig');
    }
}

