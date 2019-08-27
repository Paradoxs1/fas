<?php

namespace App\Form;

use App\Entity\Person;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class PersonType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'account.first_name',
                'constraints' => [
                    new NotBlank([
                        'message' => "security.first_name_not_blank",
                        'groups'  => ["add", "edit"]
                    ]),
                    new Regex([
                        'pattern' => "/[A-Za-z]{2,}/",
                        'message' => "security.first_name_min_length",
                        'groups'  => ["add", "edit"]
                    ]),
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'account.last_name',
                'constraints' => [
                    new NotBlank([
                        'message' => "security.last_name_not_blank",
                        'groups'  => ["add", "edit"]
                    ]),
                    new Regex([
                        'pattern' => "/[A-Za-z]{2,}/",
                        'message' => "security.last_name_min_length",
                        'groups'  => ["add", "edit"]
                    ]),
                ],
            ])
            ->add('telephone', TextType::class, [
                'label' => 'account.telephone',
                'constraints' => [
                    new Regex([
                        'pattern' => "/^([+][0-9]{11})|([0-9]{10,13})$/",
                        'message' => "security.phone_valid",
                        'groups'  => ["add", "edit"]
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Person::class,
        ]);
    }
}
