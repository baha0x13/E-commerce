<?php

namespace App\Form;

use App\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class)
            ->add('price', NumberType::class)
            ->add('stock', IntegerType::class)
            ->add('description', TextareaType::class, [
                'required' => false,
            ])
            ->add('category', ChoiceType::class, [
                'choices' => [
                    'Electronics' => 'Electronics',
                    'Vêtements' => 'Vêtements',
                    'Alimentation' => 'Alimentation',
                    'Maison' => 'Maison',
                    'Jardin' => 'Jardin',
                    'Sport' => 'Sport'
                ],
                'placeholder' => 'Choose a category',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}

