<?php

namespace App\Form;

use App\Entity\AppUser;
use App\Entity\Group;
use App\Entity\Room;
use App\Form\Model\AppUserTypeModel;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AppUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'attr' => [
                    'class' => 'form__input',
                    'placeholder' => 'Your username',
                ],
                'label' => 'Username',
                'label_attr' => [
                    'class' => 'form__label',
                ],
            ]);
        if(!$options['is_edit']) {
            $builder->add('password', PasswordType::class, [
                'attr' => [
                    'class' => 'form__input',
                    'placeholder' => 'Your password',
                ],
                'label' => 'Password',
                'label_attr' => [
                    'class' => 'form__label',
                ],
                'mapped' => false,
            ]);
        }
        $builder->add('firstName', TextType::class, [
                'attr' => [
                    'class' => 'form__input',
                    'placeholder' => 'Your first name',
                ],
                'label' => 'Given name',
                'label_attr' => [
                    'class' => 'form__label',
                ],
            ])
            ->add('secondName', TextType::class, [
                'attr' => [
                    'class' => 'form__input',
                    'placeholder' => 'Your family name',
                ],
                'label' => 'Family name',
                'label_attr' => [
                    'class' => 'form__label',
                ],
            ])
            ->add('email', EmailType::class, [
                'attr' => [
                    'class' => 'form__input',
                    'placeholder' => 'Your email',
                ],
                'label' => 'Email',
                'label_attr' => [
                    'class' => 'form__label',
                ],
                'required' => false,
            ])
            ->add('phone', TelType::class, [
                'attr' => [
                    'class' => 'form__input',
                    'placeholder' => 'Your phone number',
                ],
                'label' => 'Phone number',
                'label_attr' => [
                    'class' => 'form__label',
                ],
                'required' => false,
            ]);
            if(!$options['is_registration'] && $options['is_super_admin']) {
                $builder->add('memberGroups', EntityType::class, [
                    'class' => Group::class,
                    'choice_label' => 'name',
                    'multiple' => true,
                    'expanded' => false,
                    'required' => false,
                ])
                ->add('adminGroups', EntityType::class, [
                    'class' => Group::class,
                    'choice_label' => 'name',
                    'multiple' => true,
                    'expanded' => false,
                    'required' => false,
                ])
                ->add('memberRooms', EntityType::class, [
                    'class' => Room::class,
                    'choice_label' => 'codeName',
                    'multiple' => true,
                    'expanded' => false,
                    'required' => false,
                ])
                ->add('adminRooms', EntityType::class, [
                    'class' => Room::class,
                    'choice_label' => 'codeName',
                    'multiple' => true,
                    'expanded' => false,
                    'required' => false,
                ])
                ->add('isSuperAdmin', CheckboxType::class, [
                    'label' => 'Super admin',
                    'label_attr' => [
                        'class' => 'form__label',
                    ],
                    'required' => false,
                ]);
            }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AppUserTypeModel::class,
            'is_edit' => false,
            'is_registration' => true,
            'is_super_admin' => false,
        ]);
    }
}
