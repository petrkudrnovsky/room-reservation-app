<?php

namespace App\Form;

use App\Entity\AppUser;
use App\Entity\Building;
use App\Entity\Group;
use App\Form\Model\RoomTypeModel;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RoomType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'attr' => ['class' => 'form-control'],
                'label' => 'Room name',
            ])
            ->add('code', TextType::class, [
                'attr' => ['class' => 'form-control'],
                'label' => 'Room code',
            ])
            ->add('isPrivate', CheckboxType::class, [
                'attr' => ['class' => 'form-control checkbox'],
                'label' => 'Private room',
                'required' => false,
            ])
            ->add('building', EntityType::class, [
                'attr' => ['class' => 'form-control select'],
                'class' => Building::class,
                'choice_label' => 'name',
            ]);
        if($options['can_edit_members'] || $options['is_super_admin']) {
            $builder->add('members', EntityType::class, [
                'attr' => ['class' => 'form-control select'],
                'class' => AppUser::class,
                'choice_label' => 'username',
                'multiple' => true,
                'expanded' => false,
                'required' => false,
            ]);
        }
        if($options['can_edit_admins'] || $options['is_super_admin']) {
            $builder->add('admins', EntityType::class, [
                'attr' => ['class' => 'form-control select'],
                'class' => AppUser::class,
                'choice_label' => 'username',
                'multiple' => true,
                'expanded' => false,
                'required' => false,
            ]);
        }
        if($options['is_super_admin']) {
           $builder->add('owningGroups', EntityType::class, [
               'attr' => ['class' => 'form-control select'],
               'class' => Group::class,
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
            'data_class' => RoomTypeModel::class,
            'is_super_admin' => false,
            'can_edit_members' => false,
            'can_edit_admins' => false,
        ]);
    }
}
