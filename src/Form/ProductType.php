<?php

namespace App\Form;

use App\Entity\Order;
use App\Entity\Product;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class)
            ->add('category', TextType::class, [
                'required' => false,
                'attr' => ['placeholder' => 'Enter category']
            ])
            ->add('price')
            ->add('quantity')
            ->add('description')
            ->add('imageFile', FileType::class, [
                'mapped' => false, // Because the entity uses 'image' field
                'required' => false,
                'attr' => ['accept' => 'image/*']
            ])
            ->add('createdAt', null, [
                'widget' => 'single_text'
            ])
            ->add('orders', EntityType::class, [
    'class' => Order::class,
    'choice_label' => 'id',
    'multiple' => true,
    'required' => false,  // <- now optional
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
