<?php

namespace App\Service\Facility;

use App\Entity\FacilityLayout;
use App\Entity\Tenant;
use App\Service\FacilityService;
use App\Service\FlexParamService;
use Doctrine\ORM\EntityManagerInterface;


class TenantConfigurationProcessor
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var FlexParamService
     */
    protected $flexParamService;

    /**
     * @var ConfigurationParamsHandlerComposite
     */
    protected $configurationParamsHandlerComposite;

    /**
     * @var FacilityService
     */
    protected $facilityService;

    /**
     * TenantConfigurationProcessor constructor.
     * @param EntityManagerInterface $em
     * @param FlexParamService $flexParamService
     * @param ConfigurationParamsHandlerComposite $configurationParamsHandlerComposite
     * @param FacilityService $facilityService
     */
    public function __construct(
        EntityManagerInterface $em,
        FlexParamService $flexParamService,
        ConfigurationParamsHandlerComposite $configurationParamsHandlerComposite,
        FacilityService $facilityService
    )
    {
        $this->em = $em;
        $this->flexParamService = $flexParamService;
        $this->configurationParamsHandlerComposite = $configurationParamsHandlerComposite;
        $this->facilityService = $facilityService;
    }

    /**
     * @param array $requestData
     * @param FacilityLayout $facilityLayout
     * @throws \Exception
     */
    public function process(array $requestData, FacilityLayout $facilityLayout, Tenant $tenant)
    {
        $this->em->getConnection()->beginTransaction();
        try {
            $facilityLayout->setTenant($tenant);
            $facilityLayout->setDaysInPast(0);
            if (!array_key_exists('enableShiftsCheckbox', $requestData['default_facility_layout'])) {
                $facilityLayout->setShifts(0);
            }
            $this->flexParamService->updatePaymentMethodOrder($requestData, $facilityLayout);
            $this->em->persist($facilityLayout);
            $this->configurationParamsHandlerComposite->addPositions($requestData, $facilityLayout);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollBack();
            throw $e;
        }
    }
}
