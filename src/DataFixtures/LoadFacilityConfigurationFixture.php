<?php

namespace App\DataFixtures;

use App\Entity\Currency;
use App\Entity\FacilityLayout;
use App\Entity\Tenant;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class LoadFacilityConfigurationFixture
 * @package App\DataFixtures
 */
class LoadFacilityConfigurationFixture extends Fixture implements FixtureInterface, OrderedFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $currency = $manager->getRepository(Currency::class)->findOneBy(['isoCode' => 'CHF']);
        $tenant = $manager->getRepository(Tenant::class)->findOneBy(['name' => 'Digio GmbH']);

        $TimFacilityLayout = new FacilityLayout();

        $TimFacilityLayout->setShifts(3);
        $TimFacilityLayout->setDaysInPast(3);
        $TimFacilityLayout->setCurrency($currency);
        $TimFacilityLayout->setTenant($tenant);
        $TimFacilityLayout->setFacility($this->getReference('Tim_facility'));
        $TimFacilityLayout->setPaymentMethodOrder(json_encode(range(2,9)));

        $DamienFacilityLayout = new FacilityLayout();

        $DamienFacilityLayout->setShifts(3);
        $DamienFacilityLayout->setDaysInPast(3);
        $DamienFacilityLayout->setCurrency($currency);
        $DamienFacilityLayout->setTenant($tenant);
        $DamienFacilityLayout->setFacility($this->getReference('Damien_facility'));
        $DamienFacilityLayout->setPaymentMethodOrder(json_encode(range(2,9)));

        $ArtemFacilityLayout = new FacilityLayout();

        $ArtemFacilityLayout->setShifts(3);
        $ArtemFacilityLayout->setDaysInPast(3);
        $ArtemFacilityLayout->setCurrency($currency);
        $ArtemFacilityLayout->setTenant($tenant);
        $ArtemFacilityLayout->setFacility($this->getReference('Artem_facility'));
        $ArtemFacilityLayout->setPaymentMethodOrder(json_encode(range(2,9)));

        $NikolayFacilityLayout = new FacilityLayout();

        $NikolayFacilityLayout->setShifts(3);
        $NikolayFacilityLayout->setDaysInPast(3);
        $NikolayFacilityLayout->setCurrency($currency);
        $NikolayFacilityLayout->setTenant($tenant);
        $NikolayFacilityLayout->setFacility($this->getReference('Nikolay_facility'));
        $NikolayFacilityLayout->setPaymentMethodOrder(json_encode(range(2,9)));

        $manager->persist($TimFacilityLayout);
        $manager->persist($DamienFacilityLayout);
        $manager->persist($ArtemFacilityLayout);
        $manager->persist($NikolayFacilityLayout);

        $this->setReference('TimFacilityLayout', $TimFacilityLayout);
        $this->setReference('DamienFacilityLayout', $DamienFacilityLayout);
        $this->setReference('ArtemFacilityLayout', $ArtemFacilityLayout);
        $this->setReference('NikolayFacilityLayout', $NikolayFacilityLayout);

        $manager->flush();
    }

    /**
     * @return int
     */
    public function getOrder()
    {
        return FixtureOrder::FACILITY_CONFIGURATION;
    }
}