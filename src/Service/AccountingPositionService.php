<?php

namespace App\Service;

use App\Entity\AccountingCategory;
use App\Entity\AccountingPosition;
use App\Entity\FacilityLayout;

use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class AccountingPositionService
 * @package App\Service
 */
class AccountingPositionService
{
    /**
     * AccountingPositionService constructor.
     * @param ObjectManager $manager
     */
    public function __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @param AccountingCategory $accountingCategory
     * @param FacilityLayout $facilityLayout
     * @param int $sequence
     * @param int $predefined
     * @return AccountingPosition
     */
    public function addAccountingPosition(
        AccountingCategory $accountingCategory,
        FacilityLayout $facilityLayout,
        int $sequence = 0,
        int $predefined = 0
    ) {
        $accountingPosition = new AccountingPosition();
        $accountingPosition->setAccountingCategory($accountingCategory);
        $accountingPosition->setCurrency($facilityLayout->getCurrency());
        $accountingPosition->setFacilityLayout($facilityLayout);
        $accountingPosition->setSequence($sequence);
        $accountingPosition->setPredefined($predefined);

        $this->manager->persist($accountingPosition);

        return $accountingPosition;
    }
}
