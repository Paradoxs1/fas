<?php

namespace App\Service;

use App\Entity\Currency;
use App\Entity\Facility;
use App\Entity\FacilityLayout;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class FacilityLayoutService
 * @package App\Service
 */
class FacilityLayoutService
{
    /**
     * @var ObjectManager
     */
    private $manager;

    /**
     * FacilityLayoutService constructor.
     * @param ObjectManager $manager
     */
    public function __construct(
        ObjectManager $manager
    ) {
        $this->manager = $manager;
    }

    /**
     * @param Facility $facility
     * @return FacilityLayout
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function addLayoutForNewFacility(
        Facility $facility
    ) {
        $currency = $this->manager->getRepository(Currency::class)->findOneByISO('CHF');
        $facilityLayout = new FacilityLayout();
        $facilityLayout->setShifts(0);
        $facilityLayout->setCurrency($currency);
        $facilityLayout->setFacility($facility);
        $facilityLayout->setDaysInPast(0);
        $facilityLayout->setTenant($facility->getTenant());
        $facilityLayout->setFacility($facility);

        $this->manager->persist($facilityLayout);
        $this->manager->flush();

        return $facilityLayout;
    }
}
