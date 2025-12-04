<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Payment;
use App\Service\CartService;
use App\Service\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/commande')]
class CheckoutController extends AbstractController
{
    public function __construct(
        private CartService $cartService,
        private StripeService $stripeService,
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Page de vérification avant paiement
     */
    #[Route('/verifier', name: 'app_checkout_verify')]
    public function verify(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if ($this->cartService->isEmpty()) {
            $this->addFlash('error', 'Votre panier est vide.');
            return $this->redirectToRoute('app_shop');
        }

        // Vérifier le stock
        $stockErrors = $this->cartService->validateStock();
        if (!empty($stockErrors)) {
            foreach ($stockErrors as $error) {
                $this->addFlash('error', $error);
            }
            return $this->redirectToRoute('app_cart_index');
        }

        $cartItems = $this->cartService->getCartWithDetails();
        $total = $this->cartService->getTotal();

        return $this->render('checkout/verify.html.twig', [
            'cartItems' => $cartItems,
            'total' => $total,
        ]);
    }

    /**
     * Créer une session de paiement Stripe
     */
    #[Route('/paiement/stripe', name: 'app_checkout_stripe', methods: ['POST'])]
    public function stripeCheckout(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if ($this->cartService->isEmpty()) {
            $this->addFlash('error', 'Votre panier est vide.');
            return $this->redirectToRoute('app_shop');
        }

        // Créer la commande temporaire
        $order = $this->createOrder();

        // Préparer les items pour Stripe
        $stripeItems = [];
        foreach ($this->cartService->getCartWithDetails() as $item) {
            $stripeItems[] = [
                'name' => $item['product']->getName(),
                'description' => $item['product']->getCategory()->getName(),
                'price' => $item['product']->getPrice(),
                'quantity' => $item['quantity'],
                'images' => $item['product']->getImage() 
                    ? [$request->getSchemeAndHttpHost() . '/uploads/products/' . $item['product']->getImage()]
                    : [],
            ];
        }

        /** @var User $user */
        $user = $this->getAuthenticatedUser();

        // Créer la session Stripe
        $session = $this->stripeService->createCheckoutSession(
            $stripeItems,
            $this->generateUrl('app_checkout_success', [
                'orderId' => $order->getId()
            ], UrlGeneratorInterface::ABSOLUTE_URL) . '?session_id={CHECKOUT_SESSION_ID}',  // ← CORRECTION ICI
            $this->generateUrl('app_checkout_cancel', [
                'orderId' => $order->getId()
            ], UrlGeneratorInterface::ABSOLUTE_URL),
            [
                'order_id' => (string) $order->getId(),
                'customer_email' => $user->getEmail(),
            ]
        );

        // Rediriger vers Stripe Checkout
        return $this->redirect($session->url, 303);
    }

    /**
     * Page de succès après paiement
     */
    #[Route('/succes/{orderId}', name: 'app_checkout_success')]
    public function success(int $orderId, Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $order = $this->entityManager->getRepository(Order::class)->find($orderId);

        if (!$order || $order->getUser() !== $this->getUser()) {
            throw $this->createNotFoundException('Commande introuvable');
        }

        $sessionId = $request->query->get('session_id');

        if ($sessionId && $order->getStatus() === Order::STATUS_PENDING) {
            // Récupérer les infos de la session Stripe
            $session = $this->stripeService->retrieveSession($sessionId);

            if ($session && $session->payment_status === 'paid') {
                // Créer le paiement
                $payment = new Payment();
                $payment->setOrder($order);
                $payment->setPaymentMethod(Payment::METHOD_STRIPE);
                $payment->setTransactionId($session->payment_intent);
                $payment->setAmount((string) ($session->amount_total / 100));
                $payment->setCurrency('EUR');
                $payment->setStatus(Payment::STATUS_COMPLETED);
                $payment->setPaidAt(new \DateTimeImmutable());

                $this->entityManager->persist($payment);

                // Mettre à jour la commande
                $order->setStatus(Order::STATUS_PAID);
                $order->setPaidAt(new \DateTimeImmutable());
                $order->setPayment($payment);

                // Décrémenter le stock
                foreach ($order->getOrderItems() as $orderItem) {
                    $product = $orderItem->getProduct();
                    if ($product) {
                        $product->setStock($product->getStock() - $orderItem->getQuantity());
                    }
                }

                $this->entityManager->flush();

                // Vider le panier
                $this->cartService->clear();

                $this->addFlash('success', '🎉 Paiement réussi ! Votre commande a été confirmée.');
            }
        }

        return $this->render('checkout/success.html.twig', [
            'order' => $order,
        ]);
    }

    /**
     * Page d'annulation
     */
    #[Route('/annulation/{orderId}', name: 'app_checkout_cancel')]
    public function cancel(int $orderId): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $order = $this->entityManager->getRepository(Order::class)->find($orderId);

        if ($order && $order->getUser() === $this->getUser() && $order->getStatus() === Order::STATUS_PENDING) {
            $order->setStatus(Order::STATUS_CANCELLED);
            $this->entityManager->flush();
        }

        $this->addFlash('warning', 'Le paiement a été annulé. Votre panier est toujours disponible.');

        return $this->redirectToRoute('app_cart_index');
    }

    /**
     * Créer une commande en base de données
     */
    private function createOrder(): Order
    {
        $order = new Order();
        $order->setUser($this->getUser());
        $order->setStatus(Order::STATUS_PENDING);
        
        $total = 0;

        // Ajouter les items
        foreach ($this->cartService->getCartWithDetails() as $item) {
            $orderItem = new OrderItem();
            $orderItem->setProduct($item['product']);
            $orderItem->setProductName($item['product']->getName());
            $orderItem->setProductPrice((string) $item['product']->getPrice());
            $orderItem->setQuantity($item['quantity']);
            $orderItem->calculateTotalPrice();

            $order->addOrderItem($orderItem);
            $total += $orderItem->getTotalPrice();
        }

        $order->setTotalAmount((string) $total);

        // TODO: Ajouter les adresses de livraison et facturation
        // Pour l'instant, on met des valeurs par défaut
        /** @var User */
        $user = $this->getUser();
        $order->setShippingFullName($user->getFullName());
        $order->setShippingStreet('Adresse temporaire');
        $order->setShippingPostalCode('00000');
        $order->setShippingCity('Ville');
        $order->setShippingCountry('France');

        $order->setBillingFullName($user->getFullName());
        $order->setBillingStreet('Adresse temporaire');
        $order->setBillingPostalCode('00000');
        $order->setBillingCity('Ville');
        $order->setBillingCountry('France');

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        return $order;
    }
}