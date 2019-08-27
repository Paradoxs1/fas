<?php

namespace App\Service\Facility\Factory;

use App\Entity\FacilityLayout;

class FacilityLayoutFactory
{
    public static function createFromFacilityLayout(FacilityLayout $entryFacilityLayout)
    {
        $facilityLayout = new FacilityLayout();
        $facilityLayout->setShifts($entryFacilityLayout->getShifts());
        $facilityLayout->setTenant($entryFacilityLayout->getTenant());
        $facilityLayout->setFacility($entryFacilityLayout->getFacility());
        $facilityLayout->setCurrency($entryFacilityLayout->getCurrency());
        $facilityLayout->setDaysInPast($entryFacilityLayout->getDaysInPast());
        $facilityLayout->setPaymentMethodOrder($entryFacilityLayout->getPaymentMethodOrder());

        return $facilityLayout;
    }
}
