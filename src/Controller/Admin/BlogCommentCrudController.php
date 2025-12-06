<?php

namespace App\Controller\Admin;

use App\Entity\BlogComment;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;

class BlogCommentCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BlogComment::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Commentaire')
            ->setEntityLabelInPlural('Commentaires')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPageTitle('index', 'Liste des commentaires');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('post', 'Article'))
            ->add(EntityFilter::new('author', 'Auteur'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield AssociationField::new('post', 'Article');
        yield AssociationField::new('author', 'Auteur');
        yield TextareaField::new('content', 'Commentaire');
        yield DateTimeField::new('createdAt', 'Créé le');
    }
}