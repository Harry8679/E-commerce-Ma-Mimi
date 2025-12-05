<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\User;
use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/mon-compte/commandes')]
class OrderController extends AbstractController
{
    public function __construct(
        private OrderRepository $orderRepository
    ) {
    }

    /**
     * Récupérer l'utilisateur connecté avec le bon typage
     */
    private function getAuthenticatedUser(): User
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }
        
        return $user;
    }

    /**
     * Liste des commandes de l'utilisateur
     */
    #[Route('', name: 'app_account_orders')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getAuthenticatedUser();
        
        // Récupérer toutes les commandes de l'utilisateur, triées par date décroissante
        $orders = $this->orderRepository->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC']
        );

        return $this->render('account/orders/index.html.twig', [
            'orders' => $orders,
        ]);
    }

    /**
     * Détail d'une commande
     */
    #[Route('/{id}', name: 'app_account_order_show')]
    public function show(int $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getAuthenticatedUser();
        $order = $this->orderRepository->find($id);

        // Vérifier que la commande existe et appartient bien à l'utilisateur
        if (!$order || $order->getUser() !== $user) {
            throw $this->createNotFoundException('Commande introuvable');
        }

        return $this->render('account/orders/show.html.twig', [
            'order' => $order,
        ]);
    }
}