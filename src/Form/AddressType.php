<?php

namespace App\Form;

use App\Entity\Address;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fullName', TextType::class, [
                'label' => 'Nom complet',
                'attr' => ['class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500'],
            ])
            ->add('phone', TelType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'attr' => ['class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500'],
            ])
            ->add('street', TextType::class, [
                'label' => 'Adresse',
                'attr' => ['class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500'],
            ])
            ->add('streetComplement', TextType::class, [
                'label' => 'Complément d\'adresse (optionnel)',
                'required' => false,
                'attr' => ['class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500'],
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'Code postal',
                'attr' => ['class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500'],
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville',
                'attr' => ['class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500'],
            ])
            ->add('country', TextType::class, [
                'label' => 'Pays',
                'data' => 'France',
                'attr' => ['class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500'],
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type d\'adresse',
                'choices' => [
                    'Livraison uniquement' => Address::TYPE_SHIPPING,
                    'Facturation uniquement' => Address::TYPE_BILLING,
                    'Livraison et Facturation' => Address::TYPE_BOTH,
                ],
                'attr' => ['class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Address::class,
        ]);
    }
}