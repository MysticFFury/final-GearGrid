<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class CustomerCartController extends AbstractController
{
    #[Route('/customer/cart', name: 'app_customer_cart', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function cart(): Response
    {
        return $this->render('customer/cart.html.twig');
    }
}
