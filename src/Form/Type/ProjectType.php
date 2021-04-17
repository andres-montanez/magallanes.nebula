<?php

namespace App\Form\Type;

use App\Entity\Project;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class ProjectType extends AbstractType
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
            ->add('description', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('repository', TextType::class, [
                'required' => true,
                'attr' => ['class' => 'form-control']
            ])
            ->add('repositorySSHKey', TextareaType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-control is-code is-sensitive',
                    'rows' => 10,
                    'spellcheck' => 'false'
                ]
            ])
            ->add('config', TextareaType::class, [
                'required' => true,
                'attr' => [
                    'class' => 'form-control is-code',
                    'rows' => 17,
                    'spellcheck' => 'false'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Project::class,
        ]);
    }
}