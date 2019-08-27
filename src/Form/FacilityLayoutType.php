<?php

namespace App\Form;

use App\Entity\FacilityLayout;
use App\Entity\Currency;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

/**
 * Class FacilityLayout
 * @package App\Form
 */
class FacilityLayoutType extends BaseType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('currency', EntityType::class, [
                'label' => 'facility.currency',
                'class' => Currency::class
            ])
            ->add('daysInPast', IntegerType::class, [
                'label' => 'facility_layout.days_in_past'
            ])
            ->add('shifts', ChoiceType::class, [
                'label' => 'facility_layout.count',
                'choices'  => [
                    0 => 0,
                    1 => 1,
                    2 => 2,
                    3 => 3,
                    4 => 4,
                    5 => 5,
                    6 => 6,
                    7 => 7,
                ],
            ])
            ->add('reportingWindowCheckbox', CheckboxType::class, [
                'mapped' => false,
                'label' => 'facility_layout.different_reporting_window',
                'attr' => [
                    'class' => 'form-control',
                    'checked' => 'checked'
                ],
            ])
            ->add('enableShiftsCheckbox', CheckboxType::class, [
                'mapped' => false,
                'label' => 'facility_layout.enable_shifts',
                'attr' => [
                    'class' => 'form-control',
                    'checked' => 'checked'
                ],
            ])
            ->add('save', SubmitType::class, [
                'label' => 'button.save',
                'attr' => [
                    'class' => 'btn-submit'
                ],
            ])
            ->addEventSubscriber($this->listener);

        if ($options['isDefaultRoutine'] === false) {
            $builder
                ->add('enableInterface', CheckboxType::class, [
                    'mapped' => false
                ])
                ->add('params', TextareaType::class, [
                    'mapped' => false
                ]);
        }
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => FacilityLayout::class,
            'isDefaultRoutine' => false
        ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['isDefaultRoutine'] = $options['isDefaultRoutine'];
    }
}
