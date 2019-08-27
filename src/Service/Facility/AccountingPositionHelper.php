<?php

namespace App\Service\Facility;

use App\Entity\AccountingCategory;
use App\Entity\AccountingPosition;
use App\Entity\FacilityLayout;
use App\Entity\FlexParam;


trait AccountingPositionHelper
{
    /**
     * @param string $key
     * @param string $type
     * @param string $value
     * @param string $view
     * @param int $sequence
     * @param AccountingPosition $accountingPosition
     */
    public function addParam(string $key, string $type, string $value, string $view, int $sequence, AccountingPosition $accountingPosition)
    {
        $flexParam = new FlexParam();
        $flexParam->setKey($key);
        $flexParam->setType($type);
        $flexParam->setValue($value);
        $flexParam->setView($view);
        $flexParam->setSequence($sequence);
        $flexParam->setAccountingPosition($accountingPosition);
        $this->em->persist($flexParam);
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

        $this->em->persist($accountingPosition);

        return $accountingPosition;
    }
}
