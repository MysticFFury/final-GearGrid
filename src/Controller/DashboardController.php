<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\Order;
use App\Entity\Category;
use App\Entity\Log;
use App\Entity\User; // ✅ Add User Entity
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
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

        if ($isAdmin) {
            // Admin sees all data
            $totalProducts = $em->getRepository(Product::class)->count([]);
            $totalCategories = $em->getRepository(Category::class)->count([]);
            $totalOrders = $em->getRepository(Order::class)->count([]);
            $totalUsers = $em->getRepository(User::class)->count([]);
            $recentOrders = $em->getRepository(Order::class)->findBy([], ['createdAt' => 'DESC'], 5);
        } else {
            // Staff sees only their own data
            $totalProducts = $em->getRepository(Product::class)->count(['createdBy' => $user]);
            $totalCategories = $em->getRepository(Category::class)->count([]); // Categories are shared
            $totalOrders = $em->getRepository(Order::class)->count(['createdBy' => $user]);
            $totalUsers = null; // Staff cannot see user count
            $recentOrders = $em->getRepository(Order::class)->findBy(
                ['createdBy' => $user],
                ['createdAt' => 'DESC'],
                5
            );
        }

        // Calculate total sales from recent orders
        $recentSales = 0;
        foreach ($recentOrders as $order) {
            $recentSales += $order->getTotalPrice();
        }

        return $this->render('dashboard/index.html.twig', [
            'totalProducts' => $totalProducts,
            'totalCategories' => $totalCategories,
            'totalOrders' => $totalOrders,
            'totalUsers' => $totalUsers,
            'recentOrders' => $recentOrders,
            'recentSales' => $recentSales,
            'isAdmin' => $isAdmin,
        ]);
    }
}
 