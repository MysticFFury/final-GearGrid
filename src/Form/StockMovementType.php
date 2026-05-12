<?php

namespace App\Form;

use App\Entity\Product;
use App\Entity\StockMovement;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;

class StockMovementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('product', EntityType::class, [
                'class' => Product::class,
                'choice_label' => 'name',
                'placeholder' => 'Select product',
                'required' => true,
                'attr' => ['class' => 'w-full px-4 py-2 rounded-lg border border-gray-700 bg-gray-800/50 text-white focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500'],
            ])
            ->add('amount', IntegerType::class, [
                'label' => 'Quantity to add',
                'attr' => [
                    'class' => 'w-full px-4 py-2 rounded-lg border border-gray-700 bg-gray-800/50 text-white placeholder-gray-400 focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500',
                    'min' => 1,
                    'placeholder' => 'e.g. 5',
                ],
                'constraints' => [
                    new NotBlank(message: 'Enter a quantity to add.'),
                    new Positive(message: 'Quantity must be at least 1.'),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => StockMovement::class,
        ]);
    }
}
