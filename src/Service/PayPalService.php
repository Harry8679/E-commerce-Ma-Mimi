<?php

namespace App\Service;

use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
use PayPalCheckoutSdk\Orders\OrdersGetRequest;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class PayPalService
{
    private PayPalHttpClient $client;
    private string $mode;

    public function __construct(ParameterBagInterface $params)
    {
        $clientId = $params->get('paypal_client_id');
        $clientSecret = $params->get('paypal_client_secret');
        $this->mode = $params->get('paypal_mode');

        // Créer l'environnement selon le mode
        if ($this->mode === 'live') {
            $environment = new ProductionEnvironment($clientId, $clientSecret);
        } else {
            $environment = new SandboxEnvironment($clientId, $clientSecret);
        }

        $this->client = new PayPalHttpClient($environment);
    }

    /**
     * Créer une commande PayPal
     */
    public function createOrder(array $items, float $total, string $currency = 'EUR', array $metadata = []): array
    {
        $request = new OrdersCreateRequest();
        $request->prefer('return=representation');
        
        // Construire les items pour PayPal
        $paypalItems = [];
        $itemsTotal = 0;

        foreach ($items as $item) {
            $itemTotal = $item['price'] * $item['quantity'];
            $itemsTotal += $itemTotal;

            $paypalItems[] = [
                'name' => $item['name'],
                'description' => $item['description'] ?? '',
                'unit_amount' => [
                    'currency_code' => $currency,
                    'value' => number_format($item['price'], 2, '.', '')
                ],
                'quantity' => (string) $item['quantity'],
            ];
        }

        $body = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'reference_id' => $metadata['order_id'] ?? uniqid(),
                    'description' => 'Commande Dikukuli',
                    'custom_id' => $metadata['order_id'] ?? '',
                    'soft_descriptor' => 'DIKUKULI',
                    'amount' => [
                        'currency_code' => $currency,
                        'value' => number_format($total, 2, '.', ''),
                        'breakdown' => [
                            'item_total' => [
                                'currency_code' => $currency,
                                'value' => number_format($itemsTotal, 2, '.', '')
                            ],
                            'shipping' => [
                                'currency_code' => $currency,
                                'value' => number_format($total - $itemsTotal, 2, '.', '')
                            ]
                        ]
                    ],
                    'items' => $paypalItems
                ]
            ],
            'application_context' => [
                'return_url' => $metadata['return_url'] ?? '',
                'cancel_url' => $metadata['cancel_url'] ?? '',
                'brand_name' => 'Dikukuli',
                'locale' => 'fr-FR',
                'landing_page' => 'BILLING',
                'shipping_preference' => 'NO_SHIPPING',
                'user_action' => 'PAY_NOW'
            ]
        ];

        $request->body = $body;

        try {
            $response = $this->client->execute($request);
            
            return [
                'success' => true,
                'order_id' => $response->result->id,
                'status' => $response->result->status,
                'links' => $response->result->links,
                'approve_url' => $this->getApproveUrl($response->result->links)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Récupérer les détails d'une commande
     */
    public function getOrder(string $orderId): array
    {
        $request = new OrdersGetRequest($orderId);

        try {
            $response = $this->client->execute($request);
            
            return [
                'success' => true,
                'order' => $response->result
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Capturer le paiement d'une commande
     */
    public function captureOrder(string $orderId): array
    {
        $request = new OrdersCaptureRequest($orderId);

        try {
            $response = $this->client->execute($request);
            
            $result = $response->result;
            
            return [
                'success' => true,
                'order_id' => $result->id,
                'status' => $result->status,
                'payer' => $result->payer ?? null,
                'purchase_units' => $result->purchase_units ?? [],
                'capture_id' => $result->purchase_units[0]->payments->captures[0]->id ?? null
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Extraire l'URL d'approbation
     */
    private function getApproveUrl(array $links): ?string
    {
        foreach ($links as $link) {
            if ($link->rel === 'approve') {
                return $link->href;
            }
        }
        return null;
    }

    /**
     * Obtenir le mode actuel
     */
    public function getMode(): string
    {
        return $this->mode;
    }
}