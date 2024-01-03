<?php

namespace App\Form;

use App\Entity\AppUser;
use App\Entity\Room;
use App\Form\Model\ReservationTypeModel;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'attr' => ['class' => 'form-control']
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control description-textarea']
            ])
            ->add('startDatetime', DateTimeType::class, [
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control datetime-input'],
                'disabled' => !$options['can_edit_after_approved'],
            ])
            ->add('endDatetime', DateTimeType::class, [
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control datetime-input'],
                'disabled' => !$options['can_edit_after_approved'],
            ])
            ->add('visitors', EntityType::class, [
                'class' => AppUser::class,
                'choice_label' => 'username',
                'multiple' => true,
                'required' => false,
                'attr' => ['class' => 'form-control select']
            ]);
            if($options['edit_room']) {
                $builder->add('room', EntityType::class, [
                    'class' => Room::class,
                    'choice_label' => function(Room $room) {
                        return $room->getCodeName();
                    },
                ]);
            }
            if($options['can_edit_reservedFor']) {
                $builder->add('reservedFor', EntityType::class, [
                    'class' => AppUser::class,
                    'choice_label' => 'username',
                    'multiple' => false,
                    'attr' => ['class' => 'form-control'],
                ]);
            }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ReservationTypeModel::class,
            'edit_room' => false,
            'can_edit_reservedFor' => false,
            'can_edit_after_approved' => true,
        ]);
    }
}
