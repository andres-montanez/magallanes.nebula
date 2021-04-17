<?php

namespace App\Form\Type;

use App\Entity\User;
use App\Entity\UserGroup;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'required' => true,
                'attr' => ['class' => 'form-control']
            ])
            ->add('password', PasswordType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('name', TextType::class, [
                'required' => true,
                'attr' => ['class' => 'form-control']
            ])
            ->add('roles', ChoiceType::class, [
                'required' => true,
                'multiple' => true,
                'choices' => [
                    'User' => User::ROLE_USER,
                    'Administrator' => User::ROLE_ADMINISTRATOR,
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('groups', EntityType::class, [
                'class' => UserGroup::class,
                'choice_label' => 'name',
                'multiple' => true,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('g')
                        ->orderBy('g.name', 'ASC');
                },
                'required' => true,
                'attr' => ['class' => 'form-control']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class
        ]);
    }
}