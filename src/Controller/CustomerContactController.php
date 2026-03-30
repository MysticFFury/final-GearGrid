<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class CustomerContactController extends AbstractController
{
    #[Route('/customer-contact', name: 'app_customer_contact', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('customer/contact.html.twig');
    }
}

