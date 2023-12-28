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
            ->add('title', TextType::class)
            ->add('description', TextareaType::class, [
                'required' => false
            ])
            ->add('startDatetime', DateTimeType::class, [
                'widget' => 'single_text'
            ])
            ->add('endDatetime', DateTimeType::class, [
                'widget' => 'single_text'
            ])
            ->add('visitors', EntityType::class, [
                'class' => AppUser::class,
                'choice_label' => 'username',
                'multiple' => true,
                'required' => false,
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
                ]);
            }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ReservationTypeModel::class,
            'edit_room' => false,
            'can_edit_reservedFor' => false,
        ]);
    }
}
