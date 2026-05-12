<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\Order;
use App\Entity\Category;
use App\Entity\Log;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;

final class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    #[IsGranted('ROLE_USER')]
    public function index(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $isStaff = $this->isGranted('ROLE_STAFF');

        if ($isAdmin) {
            $totalProducts = $em->getRepository(Product::class)->count([]);
            $totalCategories = $em->getRepository(Category::class)->count([]);
            $totalOrders = $em->getRepository(Order::class)->count([]);
            $totalUsers = $em->getRepository(User::class)->count([]);
            $recentOrders = $em->getRepository(Order::class)->findBy([], ['createdAt' => 'DESC'], 10);
            $recentProducts = $em->getRepository(Product::class)->findBy([], ['createdAt' => 'DESC'], 10);
            $recentLogs = $em->getRepository(Log::class)->findBy([], ['createdAt' => 'DESC'], 10);
        } elseif ($isStaff) {
            // Staff sees the same dashboard structure but only their own operational data.
            $totalProducts = $em->getRepository(Product::class)->count(['createdBy' => $user]);
            $totalCategories = $em->getRepository(Category::class)->count([]);
            $totalOrders = $em->getRepository(Order::class)->count(['createdBy' => $user]);
            $totalUsers = null;
            $recentOrders = $em->getRepository(Order::class)->findBy(['createdBy' => $user], ['createdAt' => 'DESC'], 10);
            $recentProducts = $em->getRepository(Product::class)->findBy(['createdBy' => $user], ['createdAt' => 'DESC'], 10);
            $recentLogs = null;
        } else {
            // Regular user - minimal data
            $totalProducts = null;
            $totalCategories = $em->getRepository(Category::class)->count([]);
            $totalOrders = null;
            $totalUsers = null;
            $recentOrders = [];
            $recentProducts = [];
            $recentLogs = null;
        }

        // Calculate total sales from recent orders
        $recentSales = 0;
        foreach ($recentOrders as $order) {
            $recentSales += $order->getTotalPrice();
        }

        $response = $this->render('dashboard/index.html.twig', [
            'totalProducts' => $totalProducts,
            'totalCategories' => $totalCategories,
            'totalOrders' => $totalOrders,
            'totalUsers' => $totalUsers,
            'recentOrders' => $recentOrders,
            'recentProducts' => $recentProducts,
            'recentLogs' => $recentLogs,
            'recentSales' => $recentSales,
            'isAdmin' => $isAdmin,
            'isStaff' => $isStaff,
        ]);

        // Avoid serving stale dashboard snapshots when navigating back/forward.
        $response->setPrivate();
        $response->headers->addCacheControlDirective('no-store', true);
        $response->headers->addCacheControlDirective('no-cache', true);
        $response->headers->addCacheControlDirective('must-revalidate', true);
        $response->setMaxAge(0);
        $response->setSharedMaxAge(0);

        return $response;
    }
}