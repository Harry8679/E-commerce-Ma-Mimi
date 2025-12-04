<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;

class ProductCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Produit')
            ->setEntityLabelInPlural('Produits')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPageTitle('index', 'Liste des produits')
            ->setPageTitle('new', 'Créer un produit')
            ->setPageTitle('edit', 'Modifier le produit');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('category', 'Catégorie'))
            ->add('isActive')
            ->add('isFeatured');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('name', 'Nom');
        yield SlugField::new('slug', 'Slug')->setTargetFieldName('name')->hideOnIndex();
        yield AssociationField::new('category', 'Catégorie');
        yield TextareaField::new('description', 'Description')->hideOnIndex();
        // yield MoneyField::new('price', 'Prix')->setCurrency('EUR');
        // Remplacez MoneyField par NumberField
        // Remplacez MoneyField par NumberField
        yield NumberField::new('price', 'Prix (€)')
            ->setNumDecimals(2)
            ->setStoredAsFractional(false);
        yield IntegerField::new('stock', 'Stock');
        yield TextField::new('alcoholVolume', 'Degré d\'alcool')->hideOnIndex();
        yield TextField::new('volume', 'Volume')->hideOnIndex();
        yield TextField::new('origin', 'Origine')->hideOnIndex();
        yield ImageField::new('image', 'Image principale')
            ->setBasePath('/uploads/products/')  // ← AJOUT DU SLASH ICI
            ->setUploadDir('public/uploads/products/')
            ->setUploadedFileNamePattern('[randomhash].[extension]')
            ->setRequired(false);
        yield BooleanField::new('isFeatured', 'À la une');
        yield BooleanField::new('isActive', 'Actif');
        yield DateTimeField::new('createdAt', 'Créé le')->hideOnForm();
    }
}