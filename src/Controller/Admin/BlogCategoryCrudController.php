<?php

namespace App\Controller\Admin;

use App\Entity\BlogCategory;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ColorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class BlogCategoryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BlogCategory::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Catégorie de blog')
            ->setEntityLabelInPlural('Catégories de blog')
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
        yield ColorField::new('color', 'Couleur');
        yield DateTimeField::new('createdAt', 'Créé le')->hideOnForm();
    }
}