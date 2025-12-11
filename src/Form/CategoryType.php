<?php

namespace App\Form;

use App\Entity\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class CategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Category Name',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter category name (e.g., Electronics, Clothing)',
                    'autofocus' => true,
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a category name.',
                    ]),
                    new Length([
                        'min' => 2,
                        'max' => 255,
                        'minMessage' => 'Category name must be at least {{ limit }} characters long.',
                        'maxMessage' => 'Category name cannot be longer than {{ limit }} characters.',
                    ]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter a brief description of this category (optional)',
                    'rows' => 5,
                ],
                'constraints' => [
                    new Length([
                        'max' => 1000,
                        'maxMessage' => 'Description cannot be longer than {{ limit }} characters.',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Category::class,
        ]);
    }
}
