<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class CategoryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Category::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Catégorie')
            ->setEntityLabelInPlural('Catégories')
            ->setDefaultSort(['name' => 'ASC'])
            ->setPageTitle('index', 'Liste des catégories')
            ->setPageTitle('new', 'Créer une catégorie')
            ->setPageTitle('edit', 'Modifier la catégorie');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('name', 'Nom');
        yield SlugField::new('slug', 'Slug')->setTargetFieldName('name')->hideOnIndex();
        yield TextareaField::new('description', 'Description')->hideOnIndex();
        yield ImageField::new('image', 'Image')
            ->setBasePath('uploads/categories/')
            ->setUploadDir('public/uploads/categories/')
            ->setUploadedFileNamePattern('[randomhash].[extension]')
            ->hideOnIndex();
        yield BooleanField::new('isActive', 'Active');
        yield DateTimeField::new('createdAt', 'Créé le')->hideOnForm();
    }
}