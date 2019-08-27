<?php

namespace App\Form;

use App\Entity\Tenant;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * Class TenantType
 * @package App\Form
 */
class TenantType extends BaseType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'tenant.name',
                'constraints' => [
                    new NotBlank([
                        'message' => "tenant.name_not_blank"
                    ]),
                    new Regex([
                        'pattern' => "/^[a-zA-Z0-9\s]{3,}$/",
                        'message' => "tenant.city_min_length"
                    ]),
                ],
            ])
            ->add('address', AddressType::class)
            ->add('save', SubmitType::class, [
                'label' => 'button.save',
                'attr' => [
                    'class' => 'btn-submit'
                ],
            ])
            ->remove('tenant')
            ->addEventSubscriber($this->listener);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Tenant::class,
        ]);
    }
}