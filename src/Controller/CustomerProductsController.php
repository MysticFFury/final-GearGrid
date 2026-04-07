<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

// Notice how the class name now perfectly matches the filename!
class CustomerProductsController extends AbstractController
{
    #[Route('/customer-products', name: 'app_customer_products')]
    public function products(ProductRepository $productRepository): Response
    {
        // 1. Fetch all products from the database
        $products = $productRepository->findAll();

        // 2. Pass them to the Twig template
        return $this->render('customer/products.html.twig', [
            'products' => $products,
        ]);
    }
}