<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class CustomerProductsController extends AbstractController
{
    #[Route('/customer-products', name: 'app_customer_products', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('customer/products.html.twig');
    }
}

