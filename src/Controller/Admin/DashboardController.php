<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Entity\Category;
use App\Entity\Order;
use App\Entity\Log;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        // Redirect to unified dashboard (admin will see full data)
        return $this->redirectToRoute('app_dashboard');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('GearGrid Admin');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        yield MenuItem::section('Management');

        // yield MenuItem::linkToCrud('Products', 'fa fa-box', Product::class);
        // yield MenuItem::linkToCrud('Categories', 'fa fa-tags', Category::class);
        // yield MenuItem::linkToCrud('Orders', 'fa fa-shopping-cart', Order::class);
        // yield MenuItem::linkToCrud('Logs', 'fa fa-file', Log::class);
    }
}
