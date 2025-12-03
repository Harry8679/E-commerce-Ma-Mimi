<?php

namespace App\Controller\Admin;

use App\Entity\Carrier;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class CarrierCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Carrier::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Transporteur')
            ->setEntityLabelInPlural('Transporteurs')
            ->setDefaultSort(['position' => 'ASC'])
            ->setPageTitle('index', 'Liste des transporteurs')
            ->setPageTitle('new', 'Créer un transporteur')
            ->setPageTitle('edit', 'Modifier le transporteur');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('name', 'Nom');
        yield TextareaField::new('description', 'Description')->hideOnIndex();
        yield MoneyField::new('price', 'Prix')->setCurrency('EUR');
        yield TextField::new('deliveryTime', 'Délai de livraison');
        yield IntegerField::new('position', 'Position');
        yield ImageField::new('logo', 'Logo')
            ->setBasePath('uploads/carriers/')
            ->setUploadDir('public/uploads/carriers/')
            ->setUploadedFileNamePattern('[randomhash].[extension]')
            ->hideOnIndex();
        yield BooleanField::new('isActive', 'Actif');
        yield DateTimeField::new('createdAt', 'Créé le')->hideOnForm();
    }
}