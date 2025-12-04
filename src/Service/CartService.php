<?php

namespace App\Service;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class CartService
{
    private const CART_SESSION_KEY = 'cart';

    public function __construct(
        private RequestStack $requestStack,
        private ProductRepository $productRepository
    ) {
    }

    /**
     * Ajouter un produit au panier
     */
    public function add(int $productId, int $quantity = 1): void
    {
        $cart = $this->getCart();
        
        if (isset($cart[$productId])) {
            $cart[$productId] += $quantity;
        } else {
            $cart[$productId] = $quantity;
        }
        
        $this->saveCart($cart);
    }

    /**
     * Retirer un produit du panier
     */
    public function remove(int $productId): void
    {
        $cart = $this->getCart();
        
        if (isset($cart[$productId])) {
            unset($cart[$productId]);
            $this->saveCart($cart);
        }
    }

    /**
     * Mettre à jour la quantité d'un produit
     */
    public function updateQuantity(int $productId, int $quantity): void
    {
        if ($quantity <= 0) {
            $this->remove($productId);
            return;
        }
        
        $cart = $this->getCart();
        
        if (isset($cart[$productId])) {
            $cart[$productId] = $quantity;
            $this->saveCart($cart);
        }
    }

    /**
     * Vider le panier
     */
    public function clear(): void
    {
        $this->saveCart([]);
    }

    /**
     * Obtenir le panier brut (ID produit => quantité)
     */
    public function getCart(): array
    {
        $session = $this->requestStack->getSession();
        return $session->get(self::CART_SESSION_KEY, []);
    }

    /**
     * Obtenir le panier avec les détails des produits
     */
    public function getCartWithDetails(): array
    {
        $cart = $this->getCart();
        $cartWithDetails = [];
        
        foreach ($cart as $productId => $quantity) {
            $product = $this->productRepository->find($productId);
            
            if ($product && $product->isActive()) {
                $cartWithDetails[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'subtotal' => $product->getPrice() * $quantity,
                ];
            } else {
                // Retirer le produit s'il n'existe plus ou n'est plus actif
                $this->remove($productId);
            }
        }
        
        return $cartWithDetails;
    }

    /**
     * Obtenir le nombre total d'articles dans le panier
     */
    public function getCount(): int
    {
        $cart = $this->getCart();
        return array_sum($cart);
    }

    /**
     * Obtenir le montant total du panier
     */
    public function getTotal(): float
    {
        $cartWithDetails = $this->getCartWithDetails();
        $total = 0;
        
        foreach ($cartWithDetails as $item) {
            $total += $item['subtotal'];
        }
        
        return $total;
    }

    /**
     * Vérifier si le panier est vide
     */
    public function isEmpty(): bool
    {
        return empty($this->getCart());
    }

    /**
     * Sauvegarder le panier en session
     */
    private function saveCart(array $cart): void
    {
        $session = $this->requestStack->getSession();
        $session->set(self::CART_SESSION_KEY, $cart);
    }

    /**
     * Vérifier la disponibilité du stock avant validation
     */
    public function validateStock(): array
    {
        $errors = [];
        $cartWithDetails = $this->getCartWithDetails();
        
        foreach ($cartWithDetails as $item) {
            $product = $item['product'];
            $quantity = $item['quantity'];
            
            if ($product->getStock() < $quantity) {
                $errors[] = sprintf(
                    'Le produit "%s" n\'a que %d unité(s) en stock.',
                    $product->getName(),
                    $product->getStock()
                );
            }
        }
        
        return $errors;
    }
}