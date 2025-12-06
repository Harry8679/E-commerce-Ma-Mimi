<?php

namespace App\Form;

use App\Entity\BlogComment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class BlogCommentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('content', TextareaType::class, [
                'label' => 'Votre commentaire',
                'attr' => [
                    'class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500',
                    'rows' => 4,
                    'placeholder' => 'Partagez votre avis sur cet article...'
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Le commentaire ne peut pas être vide',
                    ]),
                    new Assert\Length([
                        'min' => 10,
                        'minMessage' => 'Le commentaire doit contenir au moins {{ limit }} caractères',
                        'max' => 1000,
                        'maxMessage' => 'Le commentaire ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BlogComment::class,
        ]);
    }
}