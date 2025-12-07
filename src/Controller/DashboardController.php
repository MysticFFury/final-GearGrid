<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\Order;
use App\Entity\Category;
use App\Entity\Log;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

final class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(EntityManagerInterface $em): Response
    {
        $totalProducts = $em->getRepository(Product::class)->count([]);
        $totalCategories = $em->getRepository(Category::class)->count([]);
        $totalOrders = $em->getRepository(Order::class)->count([]);
        $recentOrders = $em->getRepository(Order::class)->findBy([], ['createdAt' => 'DESC'], 5);

        // Calculate total sales of recent orders
        $recentSales = 0;
        foreach ($recentOrders as $order) {
            $recentSales += $order->getTotalPrice();
        }

        return $this->render('dashboard/index.html.twig', [
            'totalProducts' => $totalProducts,
            'totalCategories' => $totalCategories,
            'totalOrders' => $totalOrders,
            'recentOrders' => $recentOrders,
            'recentSales' => $recentSales,
        ]);
    }
}
