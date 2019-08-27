<?php

namespace App\Form;

use App\Entity\Country;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class CountryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('administrativeName', TextType::class, [
                'label' => 'country.street_label',
                'constraints' => [
                    new NotBlank(),
                    new Length([
                        'min' => 2
                    ]),
                ],
            ])
            ->add('isoCode', TextType::class, [
                'label' => 'country.iso_code_label',
                'constraints' => [
                    new NotBlank(),
                    new Length([
                        'min' => 2
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Country::class,
        ]);
    }
}
