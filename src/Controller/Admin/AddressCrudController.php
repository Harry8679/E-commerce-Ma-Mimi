<?php

namespace App\Controller\Admin;

use App\Entity\Address;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class AddressCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Address::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Adresse')
            ->setEntityLabelInPlural('Adresses')
            ->setPageTitle('index', 'Liste des adresses');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield AssociationField::new('user', 'Utilisateur');
        yield TextField::new('fullName', 'Nom complet');
        yield TextField::new('street', 'Adresse');
        yield TextField::new('postalCode', 'Code postal');
        yield TextField::new('city', 'Ville');
        yield TextField::new('country', 'Pays')->hideOnIndex();
        yield TelephoneField::new('phone', 'Téléphone')->hideOnIndex();
        yield ChoiceField::new('type', 'Type')
            ->setChoices([
                'Livraison' => 'shipping',
                'Facturation' => 'billing',
                'Les deux' => 'both',
            ]);
        yield BooleanField::new('isDefault', 'Par défaut');
    }
}