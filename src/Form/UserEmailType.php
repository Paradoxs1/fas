<?php

namespace App\Form;

use App\Entity\AccountEmail;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserEmailType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', TextType::class, [
                'label' => 'account.email',
                'constraints' => [
                    new NotBlank([
                        'message' => "security.email_not_blank",
                        'groups'  => ["add", "edit"]
                    ]),
                    new Email([
                        'message' => "security.email_valid",
                        'groups'  => ["add", "edit"]
                    ]),
                ],

            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => AccountEmail::class,
        ]);
    }
}