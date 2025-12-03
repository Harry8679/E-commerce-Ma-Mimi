<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;

class OrderCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Order::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Commande')
            ->setEntityLabelInPlural('Commandes')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPageTitle('index', 'Liste des commandes')
            ->setPageTitle('detail', 'Détails de la commande');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('status')
            ->add(EntityFilter::new('user', 'Client'))
            ->add('createdAt');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('orderNumber', 'Numéro');
        yield AssociationField::new('user', 'Client');
        yield ChoiceField::new('status', 'Statut')
            ->setChoices([
                'En attente' => Order::STATUS_PENDING,
                'Payée' => Order::STATUS_PAID,
                'En préparation' => Order::STATUS_PROCESSING,
                'Expédiée' => Order::STATUS_SHIPPED,
                'Livrée' => Order::STATUS_DELIVERED,
                'Annulée' => Order::STATUS_CANCELLED,
                'Remboursée' => Order::STATUS_REFUNDED,
            ])
            ->renderAsBadges([
                Order::STATUS_PENDING => 'warning',
                Order::STATUS_PAID => 'info',
                Order::STATUS_PROCESSING => 'primary',
                Order::STATUS_SHIPPED => 'success',
                Order::STATUS_DELIVERED => 'success',
                Order::STATUS_CANCELLED => 'danger',
                Order::STATUS_REFUNDED => 'secondary',
            ]);
        yield MoneyField::new('totalAmount', 'Montant total')->setCurrency('EUR');
        yield AssociationField::new('carrier', 'Transporteur')->hideOnIndex();
        yield TextField::new('shippingFullName', 'Nom livraison')->onlyOnDetail();
        yield TextField::new('shippingCity', 'Ville livraison')->onlyOnDetail();
        yield TextareaField::new('customerNote', 'Note client')->onlyOnDetail();
        yield TextareaField::new('adminNote', 'Note admin')->hideOnIndex();
        yield DateTimeField::new('createdAt', 'Créé le')->hideOnForm();
        yield DateTimeField::new('paidAt', 'Payé le')->hideOnIndex();
    }
}