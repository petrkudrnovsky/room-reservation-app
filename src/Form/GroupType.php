<?php

namespace App\Form;

use App\Entity\AppUser;
use App\Entity\Group;
use App\Entity\Room;
use App\Form\Model\GroupTypeModel;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GroupType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'attr' => ['class' => 'form-control'],
            ])
            ->add('members', EntityType::class, [
                'attr' => ['class' => 'form-control select'],
                'class' => AppUser::class,
                'choice_label' => 'username',
                'multiple' => true,
                'expanded' => false,
                'required' => false,
            ]);
        if($options['is_super_admin']) {
            $builder
                ->add('admins', EntityType::class, [
                    'attr' => ['class' => 'form-control select'],
                    'class' => AppUser::class,
                    'choice_label' => 'username',
                    'multiple' => true,
                    'expanded' => false,
                    'required' => false,
                ])
                ->add('rooms', EntityType::class, [
                    'attr' => ['class' => 'form-control select'],
                    'class' => Room::class,
                    'choice_label' => 'name',
                    'multiple' => true,
                    'expanded' => false,
                    'required' => false,
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => GroupTypeModel::class,
            'choice_label' => 'username',
            'is_super_admin' => false,
        ]);
    }
}
