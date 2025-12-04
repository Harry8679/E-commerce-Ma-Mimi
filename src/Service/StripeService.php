<?php

namespace App\Service;

use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;

class StripeService
{
    private string $secretKey;
    private string $publicKey;

    public function __construct(string $stripeSecretKey, string $stripePublicKey)
    {
        $this->secretKey = $stripeSecretKey;
        $this->publicKey = $stripePublicKey;
        
        Stripe::setApiKey($this->secretKey);
    }

    /**
     * Créer une session de paiement Stripe Checkout
     */
    public function createCheckoutSession(array $items, string $successUrl, string $cancelUrl, array $metadata = []): Session
    {
        $lineItems = [];
        
        foreach ($items as $item) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $item['name'],
                        'description' => $item['description'] ?? null,
                        'images' => $item['images'] ?? [],
                    ],
                    'unit_amount' => (int) ($item['price'] * 100), // Prix en centimes
                ],
                'quantity' => $item['quantity'],
            ];
        }

        return Session::create([
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => $metadata,
            'customer_email' => $metadata['customer_email'] ?? null,
            'locale' => 'fr',
        ]);
    }

    /**
     * Récupérer une session de paiement
     */
    public function retrieveSession(string $sessionId): ?Session
    {
        try {
            return Session::retrieve($sessionId);
        } catch (ApiErrorException $e) {
            return null;
        }
    }

    /**
     * Obtenir la clé publique
     */
    public function getPublicKey(): string
    {
        return $this->publicKey;
    }
}