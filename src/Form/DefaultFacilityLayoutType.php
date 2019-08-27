<?php

namespace App\Form;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class FacilityLayout
 * @package App\Form
 */
class DefaultFacilityLayoutType extends FacilityLayoutType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        parent::buildForm($builder,$options);

        $builder
            ->remove('currency')
            ->remove('daysInPast')
            ->remove('reportingWindowCheckbox')
            ->remove('enableShiftsCheckbox')
            ->remove('params')
            ->remove('enableInterface')
            ->add('enableShiftsCheckbox', CheckboxType::class, [
                'mapped' => false,
                'label' => 'default_facility_layout.enable_shifts',
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
        ;
    }
}
