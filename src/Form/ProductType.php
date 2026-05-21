<?php

namespace App\Form;

use App\Entity\Product;
use App\Entity\Category;
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
            ->add('name', TextType::class, [
                'attr' => ['placeholder' => 'Product Name']
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'placeholder' => 'Select Category',
                'required' => true,
                'attr' => ['class' => 'gg-input']
            ])
            ->add('price');

        if (!$options['edit_mode']) {
            $builder->add('quantity');
        }

        $builder
            ->add('description')
            ->add('imageFile', FileType::class, [
                'mapped' => false,
                'required' => false,
                'attr' => ['accept' => 'image/*']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
            'edit_mode' => false,
        ]);
        $resolver->setAllowedTypes('edit_mode', 'bool');
    }
}
