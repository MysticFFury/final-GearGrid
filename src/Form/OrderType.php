<?php

namespace App\Form;

use App\Entity\Order;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class OrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('customer', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'name',
                'label' => 'Customer Name',
                'placeholder' => 'Select a customer',
                'mapped' => false,
                'required' => false,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->where('u.roles NOT LIKE :adminRole')
                        ->andWhere('u.roles NOT LIKE :staffRole')
                        ->setParameter('adminRole', '%ROLE_ADMIN%')
                        ->setParameter('staffRole', '%ROLE_STAFF%')
                        ->orderBy('u.name', 'ASC');
                },
                'attr' => [
                    'class' => 'form-control',
                    'autofocus' => true,
                ],
            ])
            ->add('totalPrice', NumberType::class, [
                'label' => 'Total Price',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '0.00',
                    'step' => '0.01',
                    'min' => '0',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a total price.',
                    ]),
                ],
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'Pending' => 'Pending',
                    'Processing' => 'Processing',
                    'Shipped' => 'Shipped',
                    'Delivered' => 'Delivered',
                    'Completed' => 'Completed',
                    'Cancelled' => 'Cancelled',
                ],
                'attr' => [
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please select a status.',
                    ]),
                ],
            ])
            ->add('createdAt', DateTimeType::class, [
                'label' => 'Created Date',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('products', EntityType::class, [
                'class' => Product::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => false,
                'label' => 'Products',
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
        ]);
    }
}
