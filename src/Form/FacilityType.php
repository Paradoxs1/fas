<?php

namespace App\Form;

use App\Entity\Facility;
use App\Entity\Routine;
use App\Entity\RoutineTemplate;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * Class FacilityType
 * @package App\Form
 */
class FacilityType extends BaseType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'facility.name',
                'constraints' => [
                    new NotBlank([
                        'message' => "facility.name_not_blank"
                    ]),
                    new Regex([
                        'pattern' => "/[A-Za-z]{2,}/",
                        'message' => "security.last_name_min_length"
                    ]),
                ],
            ])
            ->add('routineTemplate', EntityType::class, [
                'label' => 'facility.routine_template',
                'class' => RoutineTemplate::class,
                'choice_label' => 'name',
                'required'   => false,
                'placeholder' => false,
                'mapped' => false,
            ])
            ->add('address', AddressType::class)
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
            'data_class' => Facility::class,
        ]);
    }
}
