<?php

namespace App\Form\Type;

use App\Entity\Environment;
use App\Entity\UserGroup;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\AbstractType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class EnvironmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'required' => true,
                'attr' => ['class' => 'form-control']
            ])
            ->add('code', TextType::class, [
                'required' => true,
                'attr' => ['class' => 'form-control']
            ])
            ->add('group', EntityType::class, [
                'class' => UserGroup::class,
                'choice_label' => 'name',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('g')
                        ->orderBy('g.name', 'ASC');
                },
                'required' => true,
                'attr' => ['class' => 'form-control']
            ])
            ->add('branch', TextType::class, [
                'required' => true,
                'attr' => ['class' => 'form-control']
            ])
            ->add('config', TextareaType::class, [
                'required' => true,
                'attr' => [
                    'class' => 'form-control is-code',
                    'rows' => 24,
                    'spellcheck' => 'false'
                ]
            ])
            ->add('sshKey', TextareaType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-control is-code is-sensitive',
                    'rows' => 10,
                    'spellcheck' => 'false'
                ]
            ])
            ->add('sshPublicKey', TextareaType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-control is-code',
                    'rows' => 2,
                    'spellcheck' => 'false'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Environment::class
        ]);
    }
}