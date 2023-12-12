<?php

namespace App\Form;

use App\Entity\AppUser;
use App\Form\Model\AppUserTypeModel;
use Symfony\Component\Form\AbstractType;
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
                'label' => 'Username',
            ]);
        if(!$options['is_edit']) {
            $builder->add('password', PasswordType::class, [
                'label' => 'Password',
                'mapped' => false,
            ]);
        }
        $builder->add('firstName', TextType::class, [
                'label' => 'Given name',
            ])
            ->add('secondName', TextType::class, [
                'label' => 'Family name',
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => false,
            ])
            ->add('phone', TelType::class, [
                'label' => 'Phone number',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AppUserTypeModel::class,
            'is_edit' => false
        ]);
    }
}
