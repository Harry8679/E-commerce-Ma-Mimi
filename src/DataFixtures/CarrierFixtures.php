<?php

namespace App\DataFixtures;

use App\Entity\Carrier;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CarrierFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $carriers = [
            [
                'name' => 'Colissimo',
                'description' => 'Livraison à domicile par La Poste avec suivi',
                'price' => '6.90',
                'deliveryTime' => '2-3 jours ouvrés',
                'position' => 1,
            ],
            [
                'name' => 'Chronopost',
                'description' => 'Livraison express en 24h',
                'price' => '12.90',
                'deliveryTime' => '24h',
                'position' => 2,
            ],
            [
                'name' => 'Mondial Relay',
                'description' => 'Livraison en point relais',
                'price' => '4.90',
                'deliveryTime' => '3-5 jours ouvrés',
                'position' => 3,
            ],
            [
                'name' => 'La Poste - Lettre Suivie',
                'description' => 'Envoi en lettre suivie (petits colis uniquement)',
                'price' => '3.50',
                'deliveryTime' => '3-4 jours ouvrés',
                'position' => 4,
            ],
        ];

        foreach ($carriers as $carrierData) {
            $carrier = new Carrier();
            $carrier->setName($carrierData['name']);
            $carrier->setDescription($carrierData['description']);
            $carrier->setPrice($carrierData['price']);
            $carrier->setDeliveryTime($carrierData['deliveryTime']);
            $carrier->setPosition($carrierData['position']);
            $carrier->setIsActive(true);

            $manager->persist($carrier);
        }

        $manager->flush();
    }
}