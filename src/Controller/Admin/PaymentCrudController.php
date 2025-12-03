<?php

namespace App\Controller\Admin;

use App\Entity\Payment;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class PaymentCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Payment::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Paiement')
            ->setEntityLabelInPlural('Paiements')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPageTitle('index', 'Liste des paiements');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT, Action::DELETE);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield AssociationField::new('order', 'Commande');
        yield ChoiceField::new('paymentMethod', 'Méthode')
            ->setChoices([
                'Stripe (CB)' => Payment::METHOD_STRIPE,
                'PayPal' => Payment::METHOD_PAYPAL,
            ]);
        yield TextField::new('transactionId', 'Transaction ID');
        yield MoneyField::new('amount', 'Montant')->setCurrency('EUR');
        yield ChoiceField::new('status', 'Statut')
            ->setChoices([
                'En attente' => Payment::STATUS_PENDING,
                'Complété' => Payment::STATUS_COMPLETED,
                'Échoué' => Payment::STATUS_FAILED,
                'Remboursé' => Payment::STATUS_REFUNDED,
            ])
            ->renderAsBadges([
                Payment::STATUS_PENDING => 'warning',
                Payment::STATUS_COMPLETED => 'success',
                Payment::STATUS_FAILED => 'danger',
                Payment::STATUS_REFUNDED => 'secondary',
            ]);
        yield DateTimeField::new('paidAt', 'Payé le');
        yield DateTimeField::new('createdAt', 'Créé le')->hideOnForm();
    }
}