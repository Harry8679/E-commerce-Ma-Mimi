<?php

namespace App\Controller\Admin;

use App\Entity\BlogPost;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;

class BlogPostCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BlogPost::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Article')
            ->setEntityLabelInPlural('Articles')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPageTitle('index', 'Liste des articles')
            ->setPageTitle('new', 'Créer un article')
            ->setPageTitle('edit', 'Modifier l\'article')
            ->setPageTitle('detail', 'Détails de l\'article');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('category', 'Catégorie'))
            ->add(EntityFilter::new('author', 'Auteur'))
            ->add('isPublished');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('title', 'Titre');
        yield SlugField::new('slug', 'Slug')->setTargetFieldName('title')->hideOnIndex();
        yield AssociationField::new('category', 'Catégorie');
        yield AssociationField::new('author', 'Auteur');
        yield TextareaField::new('excerpt', 'Résumé')->hideOnIndex();
        yield TextareaField::new('content', 'Contenu')->hideOnIndex();
        yield ImageField::new('featuredImage', 'Image à la une')
            ->setBasePath('/uploads/blog/')
            ->setUploadDir('public/uploads/blog/')
            ->setUploadedFileNamePattern('[randomhash].[extension]')
            ->setRequired(false)
            ->hideOnIndex();
        yield BooleanField::new('isPublished', 'Publié');
        yield DateTimeField::new('publishedAt', 'Publié le');
        yield DateTimeField::new('createdAt', 'Créé le')->hideOnForm();
    }
}