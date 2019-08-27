<?php

namespace App\Form;

use App\Entity\Address;
use App\Entity\Country;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\Regex;

class AddressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('street', TextType::class, [
                'label' => 'address.street_label',
                'constraints' => [
                    new NotBlank([
                        'message' => "tenant.street_not_blank"
                    ]),
                    new Regex([
                        'pattern' => "/^[a-zA-Z0-9\s\x7f-\xff]{3,}$/",
                        'message' => "tenant.street_min_length"
                    ]),
                ],
            ])
            ->add('zip', IntegerType::class, [
                'label' => 'address.zip_label',
                'constraints' => [
                    new NotBlank([
                        'message' => "tenant.zip_not_blank"
                    ]),
                    new Regex([
                        'pattern' => "/^[a-zA-Z0-9\s]{4,8}$/",
                        'message' => "tenant.zip_length"
                    ]),
                ],
            ])
            ->add('city', TextType::class, [
                'label' => 'address.city_label',
                'constraints' => [
                    new NotBlank([
                        'message' => "tenant.city_not_blank"
                    ]),
                    new Regex([
                        'pattern' =>"/^[a-zA-Z\s\x7f-\xff]{2,}$/",
                        'message' => "tenant.city_min_length"
                    ]),
                ],
            ])
            ->add('country', EntityType::class, [
                'label' => 'facility.country',
                'class' => Country::class,
                'constraints' => [
                    new NotBlank([
                        'message' => "tenant.country_not_blank"
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Address::class,
        ]);
    }
}
