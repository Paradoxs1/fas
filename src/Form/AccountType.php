<?php

namespace App\Form;

use App\Entity\Tenant;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

/**
 * Class AccountType
 * @package App\Form
 */
class AccountType extends BaseAccountType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder,$options);

        $builder->add('tenant', EntityType::class, [
            'class' => Tenant::class,
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('t')->where('t.deletedAt IS NULL');
            },
        ]);
    }
}
