<?php

namespace App\Service\Facility\Handler;

use App\Entity\Currency;
use App\Entity\Facility;
use App\Entity\FacilityLayout;
use App\Entity\FlexParam;
use App\Service\Facility\AccountingPositionHelper;
use App\Service\Routine\RoutineRegistry;
use Doctrine\ORM\EntityManagerInterface;

class FacilityRoutineParamHandler
{
    use AccountingPositionHelper;

    const DEFAULT_SHIFTS_AND_DAYS = 3;

    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var RoutineRegistry
     */
    protected $routineRegistry;

    /**
     * FacilityRoutineParamHandler constructor.
     * @param EntityManagerInterface $em
     * @param RoutineRegistry $routineRegistry
     */
    public function __construct(EntityManagerInterface $em, RoutineRegistry $routineRegistry)
    {
        $this->em = $em;
        $this->routineRegistry = $routineRegistry;
    }

    /**
     * @param Facility $facility
     */
    public function addExtraParams(Facility $facility)
    {
        /** @var FacilityLayout $tenantFacilityLayout */
        $tenantFacilityLayout = $this->em->getRepository(FacilityLayout::class)->findOneBy(['tenant' => $facility->getTenant(), 'facility' => null]);
        $data = $this->getFacilityLayoutData($tenantFacilityLayout);

        $facilityLayout = new FacilityLayout();
        $facilityLayout->setTenant($facility->getTenant());
        $facilityLayout->setShifts($data['shifts']);
        $facilityLayout->setDaysInPast( $data['days']);
        $facilityLayout->setCurrency($data['currency']);
        $facilityLayout->setPaymentMethodOrder($data['paymentMethodOrder']);

        $accountingPositins = $tenantFacilityLayout ? $tenantFacilityLayout->getAccountingPositions() : [];
        foreach ($accountingPositins as $accountingPosition) {
            $newAccountingPosition = clone $accountingPosition;
            $params = $accountingPosition->getFlexParams();
            foreach ($params as $param) {
                /** @var FlexParam $newParam */
                $newParam = clone $param;
                $newParam->setAccountingPosition($newAccountingPosition);
                $this->em->persist($newParam);
            }
            $this->em->persist($newAccountingPosition);
            $facilityLayout->addAccountingPosition($newAccountingPosition);
        }
        $facility->addFacilityLayouts($facilityLayout);

        // add new positions
        $routine = $this->routineRegistry->getRoutine($facility->getRoutine()->getName());
        $config = $routine->getAccountingPositionsTemplate();
        $accountingPositins = $facilityLayout->getAccountingPositions();

        foreach ($accountingPositins as $accountingPosition) {
            $category = $accountingPosition->getAccountingCategory()->getKey();

            foreach($config['AccountingPositions'] as $n => $configParams) {
                if ($configParams['key'] == $category) {
                    foreach ($accountingPosition->getFlexParams() as $param) {
                        foreach($configParams['flexParameter'] as $i => $configParam) {
                            if ($param->getKey() == $configParam['key']) {
                                unset($config['AccountingPositions'][$n]['flexParameter'][$i]);
                            }
                        }
                    }
                }
            }
        }

        foreach($accountingPositins as $accountingPosition) {
            $category = $accountingPosition->getAccountingCategory()->getKey();

            foreach($config['AccountingPositions'] as $n => $configParams) {
                if ($configParams['key'] == $category) {
                    foreach($configParams['flexParameter'] as $i => $configParam) {
                        $flexParam = new FlexParam();
                        $flexParam->setKey($configParam['key']);
                        $flexParam->setType($configParam['type']);
                        $flexParam->setValue('');
                        $flexParam->setView($configParam['view']);
                        $flexParam->setSequence($configParam['sequence']);
                        $flexParam->setAccountingPosition($accountingPosition);

                        $this->em->persist($flexParam);
                    }
                }
            }
        }

        $this->em->persist($facility);
        $this->em->flush();
    }

    /**
     * @param FacilityLayout|null $tenantFacilityLayout
     * @return array
     */
    private function getFacilityLayoutData(?FacilityLayout $tenantFacilityLayout): array
    {
        $data['shifts'] = $tenantFacilityLayout ? $tenantFacilityLayout->getShifts() : self::DEFAULT_SHIFTS_AND_DAYS;
        $data['days'] = $tenantFacilityLayout ? $tenantFacilityLayout->getDaysInPast() : self::DEFAULT_SHIFTS_AND_DAYS;
        $data['currency'] = $tenantFacilityLayout ? $tenantFacilityLayout->getCurrency() : $this->em->getRepository(Currency::class)->findOneBy(['isoCode' => 'CHF']);
        $data['paymentMethodOrder'] = $tenantFacilityLayout ? $tenantFacilityLayout->getPaymentMethodOrder() : json_encode(range(2,9));

        return $data;
    }
}
