<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Payment;
use App\Entity\User;
use App\Repository\AddressRepository;
use App\Repository\CarrierRepository;
use App\Service\CartService;
use App\Service\CheckoutService;
use App\Service\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Service\PayPalService;

#[Route('/commande')]
class CheckoutController extends AbstractController
{
    public function __construct(
        private CartService $cartService,
        private CheckoutService $checkoutService,
        private AddressRepository $addressRepository,
        private CarrierRepository $carrierRepository,
        private StripeService $stripeService,
        private PayPalService $paypalService,
        private EntityManagerInterface $entityManager
    ) {
    }

    private function getAuthenticatedUser(): User
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Vous devez être connecté.');
        }
        
        return $user;
    }

    /**
     * Étape 1 : Choix de l'adresse de livraison
     */
    #[Route('/adresse', name: 'app_checkout_address')]
    public function address(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if ($this->cartService->isEmpty()) {
            $this->addFlash('error', 'Votre panier est vide.');
            return $this->redirectToRoute('app_shop');
        }

        $user = $this->getAuthenticatedUser();
        $addresses = $this->addressRepository->findBy(['user' => $user]);

        if (empty($addresses)) {
            $this->addFlash('warning', 'Vous devez d\'abord ajouter une adresse de livraison.');
            return $this->redirectToRoute('app_account_address_new');
        }

        $selectedAddress = $this->checkoutService->getSelectedAddress();

        return $this->render('checkout/address.html.twig', [
            'addresses' => $addresses,
            'selectedAddress' => $selectedAddress,
            'cartTotal' => $this->cartService->getTotal(),
        ]);
    }

    /**
     * Sauvegarder l'adresse sélectionnée
     */
    #[Route('/adresse/choisir/{id}', name: 'app_checkout_address_select', methods: ['POST'])]
    public function selectAddress(int $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getAuthenticatedUser();
        $address = $this->addressRepository->find($id);

        if (!$address || $address->getUser() !== $user) {
            throw $this->createNotFoundException('Adresse introuvable');
        }

        $this->checkoutService->setAddress($id);
        $this->addFlash('success', '✅ Adresse de livraison sélectionnée.');

        return $this->redirectToRoute('app_checkout_carrier');
    }

    /**
     * Étape 2 : Choix du transporteur
     */
    #[Route('/transporteur', name: 'app_checkout_carrier')]
    public function carrier(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if ($this->cartService->isEmpty()) {
            $this->addFlash('error', 'Votre panier est vide.');
            return $this->redirectToRoute('app_shop');
        }

        $selectedAddress = $this->checkoutService->getSelectedAddress();
        if (!$selectedAddress) {
            $this->addFlash('warning', 'Veuillez d\'abord choisir une adresse de livraison.');
            return $this->redirectToRoute('app_checkout_address');
        }

        $carriers = $this->carrierRepository->findActiveCarriers();
        $selectedCarrier = $this->checkoutService->getSelectedCarrier();

        return $this->render('checkout/carrier.html.twig', [
            'carriers' => $carriers,
            'selectedCarrier' => $selectedCarrier,
            'selectedAddress' => $selectedAddress,
            'cartTotal' => $this->cartService->getTotal(),
        ]);
    }

    /**
     * Sauvegarder le transporteur sélectionné
     */
    #[Route('/transporteur/choisir/{id}', name: 'app_checkout_carrier_select', methods: ['POST'])]
    public function selectCarrier(int $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $carrier = $this->carrierRepository->find($id);

        if (!$carrier || !$carrier->isActive()) {
            throw $this->createNotFoundException('Transporteur introuvable');
        }

        $this->checkoutService->setCarrier($id);
        $this->addFlash('success', '✅ Transporteur sélectionné.');

        return $this->redirectToRoute('app_checkout_summary');
    }

    /**
     * Étape 3 : Récapitulatif et paiement
     */
    #[Route('/recapitulatif', name: 'app_checkout_summary')]
    public function summary(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if ($this->cartService->isEmpty()) {
            $this->addFlash('error', 'Votre panier est vide.');
            return $this->redirectToRoute('app_shop');
        }

        if (!$this->checkoutService->isComplete()) {
            $this->addFlash('warning', 'Veuillez compléter toutes les étapes.');
            return $this->redirectToRoute('app_checkout_address');
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
        $cartTotal = $this->cartService->getTotal();
        $selectedAddress = $this->checkoutService->getSelectedAddress();
        $selectedCarrier = $this->checkoutService->getSelectedCarrier();
        
        $shippingCost = (float) $selectedCarrier->getPrice();
        $totalAmount = $cartTotal + $shippingCost;

        return $this->render('checkout/summary.html.twig', [
            'cartItems' => $cartItems,
            'cartTotal' => $cartTotal,
            'selectedAddress' => $selectedAddress,
            'selectedCarrier' => $selectedCarrier,
            'shippingCost' => $shippingCost,
            'totalAmount' => $totalAmount,
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

        if (!$this->checkoutService->isComplete()) {
            $this->addFlash('warning', 'Veuillez compléter toutes les étapes.');
            return $this->redirectToRoute('app_checkout_address');
        }

        // Créer la commande
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

        // Ajouter les frais de livraison
        $carrier = $this->checkoutService->getSelectedCarrier();
        $stripeItems[] = [
            'name' => 'Livraison - ' . $carrier->getName(),
            'description' => $carrier->getDeliveryTime(),
            'price' => $carrier->getPrice(),
            'quantity' => 1,
        ];

        $user = $this->getAuthenticatedUser();

        // Créer la session Stripe
        $session = $this->stripeService->createCheckoutSession(
            $stripeItems,
            $this->generateUrl('app_checkout_success', [
                'orderId' => $order->getId()
            ], UrlGeneratorInterface::ABSOLUTE_URL) . '?session_id={CHECKOUT_SESSION_ID}',
            $this->generateUrl('app_checkout_cancel', [
                'orderId' => $order->getId()
            ], UrlGeneratorInterface::ABSOLUTE_URL),
            [
                'order_id' => (string) $order->getId(),
                'customer_email' => $user->getEmail(),
            ]
        );

        return $this->redirect($session->url, 303);
    }

    /**
     * Page de succès après paiement
     */
    #[Route('/succes/{orderId}', name: 'app_checkout_success')]
    public function success(int $orderId, Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getAuthenticatedUser();
        $order = $this->entityManager->getRepository(Order::class)->find($orderId);

        if (!$order || $order->getUser() !== $user) {
            throw $this->createNotFoundException('Commande introuvable');
        }

        $sessionId = $request->query->get('session_id');

        if ($sessionId && $order->getStatus() === Order::STATUS_PENDING) {
            $session = $this->stripeService->retrieveSession($sessionId);

            if ($session && $session->payment_status === 'paid') {
                $payment = new Payment();
                $payment->setOrder($order);
                $payment->setPaymentMethod(Payment::METHOD_STRIPE);
                $payment->setTransactionId($session->payment_intent);
                $payment->setAmount((string) ($session->amount_total / 100));
                $payment->setCurrency('EUR');
                $payment->setStatus(Payment::STATUS_COMPLETED);
                $payment->setPaidAt(new \DateTimeImmutable());

                $this->entityManager->persist($payment);

                $order->setStatus(Order::STATUS_PAID);
                $order->setPaidAt(new \DateTimeImmutable());
                $order->setPayment($payment);

                foreach ($order->getOrderItems() as $orderItem) {
                    $product = $orderItem->getProduct();
                    if ($product) {
                        $newStock = $product->getStock() - $orderItem->getQuantity();
                        $product->setStock(max(0, $newStock));
                    }
                }

                $this->entityManager->flush();

                // Vider le panier et le checkout
                $this->cartService->clear();
                $this->checkoutService->clear();

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

        $user = $this->getAuthenticatedUser();
        $order = $this->entityManager->getRepository(Order::class)->find($orderId);

        if ($order && $order->getUser() === $user && $order->getStatus() === Order::STATUS_PENDING) {
            $order->setStatus(Order::STATUS_CANCELLED);
            $this->entityManager->flush();
        }

        $this->addFlash('warning', 'Le paiement a été annulé.');

        return $this->redirectToRoute('app_checkout_summary');
    }

    /**
     * Créer une commande en base de données
     */
    private function createOrder(): Order
    {
        $user = $this->getAuthenticatedUser();
        $address = $this->checkoutService->getSelectedAddress();
        $carrier = $this->checkoutService->getSelectedCarrier();
        
        $order = new Order();
        $order->setUser($user);
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
            $total += (float) $orderItem->getTotalPrice();
        }

        // Ajouter le transporteur
        $order->setCarrier($carrier);
        $order->copyCarrierInfo();
        $total += (float) $carrier->getPrice();

        $order->setTotalAmount((string) $total);

        // Adresse de livraison
        $order->setShippingFullName($address->getFullName());
        $order->setShippingPhone($address->getPhone());
        $order->setShippingStreet($address->getStreet());
        $order->setShippingStreetComplement($address->getStreetComplement());
        $order->setShippingPostalCode($address->getPostalCode());
        $order->setShippingCity($address->getCity());
        $order->setShippingCountry($address->getCountry());

        // Adresse de facturation (même que livraison pour l'instant)
        $order->setBillingFullName($address->getFullName());
        $order->setBillingPhone($address->getPhone());
        $order->setBillingStreet($address->getStreet());
        $order->setBillingStreetComplement($address->getStreetComplement());
        $order->setBillingPostalCode($address->getPostalCode());
        $order->setBillingCity($address->getCity());
        $order->setBillingCountry($address->getCountry());

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        return $order;
    }

    /**
     * Créer une commande PayPal
     */
    #[Route('/paiement/paypal', name: 'app_checkout_paypal', methods: ['POST'])]
    public function paypalCheckout(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if ($this->cartService->isEmpty()) {
            $this->addFlash('error', 'Votre panier est vide.');
            return $this->redirectToRoute('app_shop');
        }

        if (!$this->checkoutService->isComplete()) {
            $this->addFlash('warning', 'Veuillez compléter toutes les étapes.');
            return $this->redirectToRoute('app_checkout_address');
        }

        // Créer la commande en base
        $order = $this->createOrder();

        // Préparer les items pour PayPal
        $paypalItems = [];
        foreach ($this->cartService->getCartWithDetails() as $item) {
            $paypalItems[] = [
                'name' => $item['product']->getName(),
                'description' => $item['product']->getCategory()->getName(),
                'price' => (float) $item['product']->getPrice(),
                'quantity' => $item['quantity'],
            ];
        }

        // Ajouter les frais de livraison
        $carrier = $this->checkoutService->getSelectedCarrier();
        $paypalItems[] = [
            'name' => 'Livraison - ' . $carrier->getName(),
            'description' => $carrier->getDeliveryTime(),
            'price' => (float) $carrier->getPrice(),
            'quantity' => 1,
        ];

        $total = (float) $order->getTotalAmount();

        // Créer la commande PayPal
        $result = $this->paypalService->createOrder(
            $paypalItems,
            $total,
            'EUR',
            [
                'order_id' => (string) $order->getId(),
                'return_url' => $this->generateUrl('app_checkout_paypal_success', [
                    'orderId' => $order->getId()
                ], UrlGeneratorInterface::ABSOLUTE_URL),
                'cancel_url' => $this->generateUrl('app_checkout_cancel', [
                    'orderId' => $order->getId()
                ], UrlGeneratorInterface::ABSOLUTE_URL),
            ]
        );

        if (!$result['success']) {
            $this->addFlash('error', 'Erreur lors de la création du paiement PayPal : ' . $result['error']);
            return $this->redirectToRoute('app_checkout_summary');
        }

        // Rediriger vers PayPal pour l'approbation
        return $this->redirect($result['approve_url']);
    }

    /**
     * Page de succès après paiement PayPal
     */
    #[Route('/paypal/succes/{orderId}', name: 'app_checkout_paypal_success')]
    public function paypalSuccess(int $orderId, Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getAuthenticatedUser();
        $order = $this->entityManager->getRepository(Order::class)->find($orderId);

        if (!$order || $order->getUser() !== $user) {
            throw $this->createNotFoundException('Commande introuvable');
        }

        $paypalOrderId = $request->query->get('token');

        if (!$paypalOrderId) {
            $this->addFlash('error', 'Token PayPal manquant.');
            return $this->redirectToRoute('app_checkout_summary');
        }

        // Vérifier si la commande n'est pas déjà payée
        if ($order->getStatus() !== Order::STATUS_PENDING) {
            $this->addFlash('info', 'Cette commande a déjà été traitée.');
            return $this->render('checkout/success.html.twig', [
                'order' => $order,
            ]);
        }

        // Capturer le paiement PayPal
        $result = $this->paypalService->captureOrder($paypalOrderId);

        if (!$result['success']) {
            $this->addFlash('error', 'Erreur lors de la capture du paiement : ' . $result['error']);
            return $this->redirectToRoute('app_checkout_summary');
        }

        if ($result['status'] === 'COMPLETED') {
            // Créer l'enregistrement de paiement
            $payment = new Payment();
            $payment->setOrder($order);
            $payment->setPaymentMethod(Payment::METHOD_PAYPAL);
            $payment->setTransactionId($result['capture_id']);
            $payment->setAmount($order->getTotalAmount());
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
                    $newStock = $product->getStock() - $orderItem->getQuantity();
                    $product->setStock(max(0, $newStock));
                }
            }

            $this->entityManager->flush();

            // Vider le panier et le checkout
            $this->cartService->clear();
            $this->checkoutService->clear();

            $this->addFlash('success', '🎉 Paiement PayPal réussi ! Votre commande a été confirmée.');
        } else {
            $this->addFlash('warning', 'Le paiement PayPal est en attente de confirmation.');
        }

        return $this->render('checkout/success.html.twig', [
            'order' => $order,
        ]);
    }
}