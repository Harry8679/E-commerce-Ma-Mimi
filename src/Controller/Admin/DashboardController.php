<?php

namespace App\Controller\Admin;

use App\Entity\Address;
use App\Entity\Carrier;
use App\Entity\Category;
use App\Entity\Invoice;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Payment;
use App\Entity\Product;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        // Redirection vers la liste des commandes par défaut
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        return $this->redirect($adminUrlGenerator->setController(OrderCrudController::class)->generateUrl());
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('🥃 Rhum Shop - Administration')
            ->setFaviconPath('data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 128 128%22><text y=%221.2em%22 font-size=%2296%22>🥃</text></svg>');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        yield MenuItem::section('E-commerce');
        yield MenuItem::linkToCrud('Commandes', 'fas fa-shopping-cart', Order::class);
        yield MenuItem::linkToCrud('Produits', 'fas fa-wine-bottle', Product::class);
        yield MenuItem::linkToCrud('Catégories', 'fas fa-tags', Category::class);

        yield MenuItem::section('Livraison');
        yield MenuItem::linkToCrud('Transporteurs', 'fas fa-truck', Carrier::class);

        yield MenuItem::section('Clients');
        yield MenuItem::linkToCrud('Utilisateurs', 'fas fa-users', User::class);
        yield MenuItem::linkToCrud('Adresses', 'fas fa-map-marker-alt', Address::class);

        yield MenuItem::section('Facturation');
        yield MenuItem::linkToCrud('Paiements', 'fas fa-credit-card', Payment::class);
        yield MenuItem::linkToCrud('Factures', 'fas fa-file-invoice', Invoice::class);

        yield MenuItem::section('Retour au site');
        yield MenuItem::linkToRoute('Voir le site', 'fas fa-eye', 'app_home');
        yield MenuItem::linkToLogout('Déconnexion', 'fas fa-sign-out-alt');
    }
}