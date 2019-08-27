<?php

namespace App\Service\Facility;

use App\Entity\FacilityLayout;
use App\Service\Facility\Factory\FacilityLayoutFactory;
use App\Service\FacilityService;
use App\Service\FlexParamService;
use Doctrine\ORM\EntityManagerInterface;


class FacilityConfigurationProcessor
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
     * FacilityConfigurationProcessor constructor.
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
    public function process(array $requestData, FacilityLayout $facilityLayout)
    {
        $this->em->getConnection()->beginTransaction();
        try {
            if ($this->configurationParamsHandlerComposite->checkChanges($requestData, $facilityLayout)) {
                $newFacilityLayout = FacilityLayoutFactory::createFromFacilityLayout($facilityLayout);
                $this->flexParamService->updatePaymentMethodOrder($requestData, $newFacilityLayout);
                $this->configurationParamsHandlerComposite->addPositions($requestData, $newFacilityLayout, true);

                $this->em->refresh($facilityLayout->getFacility());
                $facilityLayout->setModifiedAt(new \DateTimeImmutable());
                $routine = $newFacilityLayout->getFacility()->getRoutine();
                if (isset($requestData['facility_layout']['params'])) {
                    $routine->setParams($requestData['facility_layout']['params']);
                }
                $this->em->persist($routine);
                $this->em->persist($newFacilityLayout);
                $this->em->flush();
            }
            //Assigns estimated costs values
            $this->facilityService->assignEstimatedCostsPerDay($requestData);
            $facility = $facilityLayout->getFacility();

            if (isset($requestData['facility_layout']['enableInterface'])) {
                $facility->setEnableInterface(true);
                $this->em->flush();
            } else {
                $facility->setEnableInterface(false);
            }

            $routine = $facility->getRoutine();
            if (isset($requestData['facility_layout']['params'])) {
                $routine->setParams($requestData['facility_layout']['params']);
            }
            $this->em->persist($routine);
            $this->em->flush();

            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollBack();
            throw $e;
        }
    }
}
