<?php

namespace App\Form;

use App\Entity\Account;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * Class BaseAccountType
 * @package App\Form
 */
class BaseAccountType extends BaseType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('person', PersonType::class)
            ->add('login', TextType::class, [
                'label' => 'account.username',
                'constraints' => [
                    new NotBlank([
                        'message' => "security.login_not_blank",
                        'groups'  => ["add", "edit"]
                    ]),
                    new Regex([
                        'pattern' => "/^[a-zA-Z0-9\S]{2,}$/",
                        'message' => "security.login_min_length",
                        'groups'  => ["add", "edit"]
                    ]),
                ],
            ])
            ->add('passwordHash', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'security.confirm_password_not_identical',
                'options' => [
                    'attr' => [
                        'class' => 'password-field'
                    ]
                ],
                'required' => false,
                'first_options'  => [
                    'label' => 'account.password'
                ],
                'second_options' => [
                    'label' => 'account.password_confirm'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => "security.password_not_blank",
                        'groups'  => ["add"]
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => "security.password_min_length",
                        'groups' => ["add", "password"]
                    ]),
                ],
            ])
            ->add('accountEmail', UserEmailType::class, [
            ])
            ->add('save', SubmitType::class, [
                'label' => 'button.save',
                'attr' => [
                    'class' => 'btn-submit'
                ],
            ])
            ->addEventSubscriber($this->listener);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Account::class,
        ]);
    }
}
