<?php

namespace App\Service;

use App\Entity\Address;
use App\Entity\Carrier;
use App\Repository\AddressRepository;
use App\Repository\CarrierRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class CheckoutService
{
    private const CHECKOUT_SESSION_KEY = 'checkout';

    public function __construct(
        private RequestStack $requestStack,
        private AddressRepository $addressRepository,
        private CarrierRepository $carrierRepository
    ) {
    }

    /**
     * Obtenir les données de checkout
     */
    public function getCheckoutData(): array
    {
        $session = $this->requestStack->getSession();
        return $session->get(self::CHECKOUT_SESSION_KEY, [
            'address_id' => null,
            'carrier_id' => null,
        ]);
    }

    /**
     * Définir l'adresse de livraison
     */
    public function setAddress(int $addressId): void
    {
        $data = $this->getCheckoutData();
        $data['address_id'] = $addressId;
        $this->saveCheckoutData($data);
    }

    /**
     * Définir le transporteur
     */
    public function setCarrier(int $carrierId): void
    {
        $data = $this->getCheckoutData();
        $data['carrier_id'] = $carrierId;
        $this->saveCheckoutData($data);
    }

    /**
     * Obtenir l'adresse sélectionnée
     */
    public function getSelectedAddress(): ?Address
    {
        $data = $this->getCheckoutData();
        if (!$data['address_id']) {
            return null;
        }
        return $this->addressRepository->find($data['address_id']);
    }

    /**
     * Obtenir le transporteur sélectionné
     */
    public function getSelectedCarrier(): ?Carrier
    {
        $data = $this->getCheckoutData();
        if (!$data['carrier_id']) {
            return null;
        }
        return $this->carrierRepository->find($data['carrier_id']);
    }

    /**
     * Vérifier si le checkout est complet
     */
    public function isComplete(): bool
    {
        $data = $this->getCheckoutData();
        return !empty($data['address_id']) && !empty($data['carrier_id']);
    }

    /**
     * Vider les données de checkout
     */
    public function clear(): void
    {
        $session = $this->requestStack->getSession();
        $session->remove(self::CHECKOUT_SESSION_KEY);
    }

    /**
     * Sauvegarder les données de checkout
     */
    private function saveCheckoutData(array $data): void
    {
        $session = $this->requestStack->getSession();
        $session->set(self::CHECKOUT_SESSION_KEY, $data);
    }
}