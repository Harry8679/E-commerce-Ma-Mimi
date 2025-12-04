<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/panier')]
class CartController extends AbstractController
{
    public function __construct(
        private CartService $cartService,
        private ProductRepository $productRepository
    ) {
    }

    /**
     * Afficher le panier
     */
    #[Route('', name: 'app_cart_index')]
    public function index(): Response
    {
        $cartItems = $this->cartService->getCartWithDetails();
        $total = $this->cartService->getTotal();

        return $this->render('cart/index.html.twig', [
            'cartItems' => $cartItems,
            'total' => $total,
        ]);
    }

    /**
     * Ajouter un produit au panier
     */
    #[Route('/ajouter/{id}', name: 'app_cart_add', methods: ['POST'])]
    public function add(int $id, Request $request): Response
    {
        $product = $this->productRepository->find($id);

        if (!$product) {
            $this->addFlash('error', 'Produit introuvable.');
            return $this->redirectToRoute('app_shop');
        }

        if (!$product->isActive()) {
            $this->addFlash('error', 'Ce produit n\'est plus disponible.');
            return $this->redirectToRoute('app_shop');
        }

        $quantity = $request->request->getInt('quantity', 1);

        // Vérifier le stock
        if ($product->getStock() < $quantity) {
            $this->addFlash('error', sprintf(
                'Stock insuffisant pour "%s". Il reste %d unité(s).',
                $product->getName(),
                $product->getStock()
            ));
            return $this->redirectToRoute('app_product_show', ['slug' => $product->getSlug()]);
        }

        $this->cartService->add($id, $quantity);

        $this->addFlash('success', sprintf(
            '✅ "%s" a été ajouté au panier (x%d).',
            $product->getName(),
            $quantity
        ));

        // Rediriger vers le panier ou la page du produit selon le bouton cliqué
        $redirectTo = $request->request->get('redirect_to', 'product');
        
        if ($redirectTo === 'cart') {
            return $this->redirectToRoute('app_cart_index');
        }

        return $this->redirectToRoute('app_product_show', ['slug' => $product->getSlug()]);
    }

    /**
     * Mettre à jour la quantité d'un produit
     */
    #[Route('/modifier/{id}', name: 'app_cart_update', methods: ['POST'])]
    public function update(int $id, Request $request): Response
    {
        $quantity = $request->request->getInt('quantity', 1);
        $product = $this->productRepository->find($id);

        if ($product && $product->getStock() < $quantity) {
            $this->addFlash('error', sprintf(
                'Stock insuffisant pour "%s". Il reste %d unité(s).',
                $product->getName(),
                $product->getStock()
            ));
            return $this->redirectToRoute('app_cart_index');
        }

        $this->cartService->updateQuantity($id, $quantity);

        $this->addFlash('success', 'Quantité mise à jour.');

        return $this->redirectToRoute('app_cart_index');
    }

    /**
     * Retirer un produit du panier
     */
    #[Route('/supprimer/{id}', name: 'app_cart_remove', methods: ['POST'])]
    public function remove(int $id): Response
    {
        $this->cartService->remove($id);
        $this->addFlash('success', 'Produit retiré du panier.');

        return $this->redirectToRoute('app_cart_index');
    }

    /**
     * Vider le panier
     */
    #[Route('/vider', name: 'app_cart_clear', methods: ['POST'])]
    public function clear(): Response
    {
        $this->cartService->clear();
        $this->addFlash('success', 'Le panier a été vidé.');

        return $this->redirectToRoute('app_cart_index');
    }
}