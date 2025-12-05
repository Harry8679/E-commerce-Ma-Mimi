<?php

namespace App\Controller;

use App\Entity\Address;
use App\Entity\User;
use App\Form\AddressType;
use App\Repository\AddressRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/mon-compte/adresses')]
class AddressController extends AbstractController
{
    public function __construct(
        private AddressRepository $addressRepository,
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
     * Liste des adresses
     */
    #[Route('', name: 'app_account_addresses')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getAuthenticatedUser();
        $addresses = $this->addressRepository->findBy(['user' => $user]);

        return $this->render('account/addresses/index.html.twig', [
            'addresses' => $addresses,
        ]);
    }

    /**
     * Ajouter une adresse
     */
    #[Route('/ajouter', name: 'app_account_address_new')]
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getAuthenticatedUser();

        $address = new Address();
        $address->setUser($user);

        $form = $this->createForm(AddressType::class, $address);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Si c'est la première adresse, la définir par défaut
            if ($this->addressRepository->count(['user' => $user]) === 0) {
                $address->setIsDefault(true);
            }

            $this->entityManager->persist($address);
            $this->entityManager->flush();

            $this->addFlash('success', '✅ Adresse ajoutée avec succès.');

            return $this->redirectToRoute('app_account_addresses');
        }

        return $this->render('account/addresses/form.html.twig', [
            'form' => $form,
            'isEdit' => false,
        ]);
    }

    /**
     * Modifier une adresse
     */
    #[Route('/{id}/modifier', name: 'app_account_address_edit')]
    public function edit(int $id, Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getAuthenticatedUser();
        $address = $this->addressRepository->find($id);

        if (!$address || $address->getUser() !== $user) {
            throw $this->createNotFoundException('Adresse introuvable');
        }

        $form = $this->createForm(AddressType::class, $address);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', '✅ Adresse modifiée avec succès.');

            return $this->redirectToRoute('app_account_addresses');
        }

        return $this->render('account/addresses/form.html.twig', [
            'form' => $form,
            'isEdit' => true,
            'address' => $address,
        ]);
    }

    /**
     * Supprimer une adresse
     */
    #[Route('/{id}/supprimer', name: 'app_account_address_delete', methods: ['POST'])]
    public function delete(int $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getAuthenticatedUser();
        $address = $this->addressRepository->find($id);

        if (!$address || $address->getUser() !== $user) {
            throw $this->createNotFoundException('Adresse introuvable');
        }

        $this->entityManager->remove($address);
        $this->entityManager->flush();

        $this->addFlash('success', '✅ Adresse supprimée avec succès.');

        return $this->redirectToRoute('app_account_addresses');
    }

    /**
     * Définir une adresse par défaut
     */
    #[Route('/{id}/definir-par-defaut', name: 'app_account_address_set_default', methods: ['POST'])]
    public function setDefault(int $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getAuthenticatedUser();
        $address = $this->addressRepository->find($id);

        if (!$address || $address->getUser() !== $user) {
            throw $this->createNotFoundException('Adresse introuvable');
        }

        // Retirer le défaut des autres adresses
        foreach ($user->getAddresses() as $userAddress) {
            $userAddress->setIsDefault(false);
        }

        $address->setIsDefault(true);
        $this->entityManager->flush();

        $this->addFlash('success', '✅ Adresse définie par défaut.');

        return $this->redirectToRoute('app_account_addresses');
    }
}