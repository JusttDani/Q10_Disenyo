<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => [
                    'placeholder' => 'tu@email.com',
                    'autocomplete' => 'email',
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options' => [
                    'label' => 'Contraseña',
                    'attr' => [
                        'placeholder' => 'Mínimo 6 caracteres',
                        'autocomplete' => 'new-password',
                    ],
                ],
                'second_options' => [
                    'label' => 'Repetir contraseña',
                    'attr' => [
                        'placeholder' => 'Repite tu contraseña',
                        'autocomplete' => 'new-password',
                    ],
                ],
                'invalid_message' => 'Las contraseñas no coinciden.',
                'constraints' => [
                    new NotBlank(['message' => 'La contraseña es obligatoria.']),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'La contraseña debe tener al menos {{ limit }} caracteres.',
                        'max' => 4096,
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
