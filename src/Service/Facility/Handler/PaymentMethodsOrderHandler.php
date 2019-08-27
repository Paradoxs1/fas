<?php

namespace App\Service\Facility\Handler;

use App\Entity\FacilityLayout;
use App\Service\Facility\ConfigurationParamsHandler;
use App\Service\FlexParamService;


class PaymentMethodsOrderHandler extends ConfigurationParamsHandler
{
    /**
     * @var FlexParamService
     */
    private $flexParamService;

    /**
     * PaymentMethodsOrderHandler constructor.
     * @param FlexParamService $flexParamService
     */
    public function __construct(FlexParamService $flexParamService)
    {
        $this->flexParamService = $flexParamService;
    }

    /**
     * @param array $requestData
     * @param FacilityLayout $facilityLayout
     * @return bool
     */
    public function checkChanges(array $requestData, FacilityLayout $facilityLayout): bool
    {
        if ($this->flexParamService->paymentMethodOrderChanged($requestData, $facilityLayout)) {
            return true;
        }

        return false;
    }

    /**
     * @param array $requestData
     * @param FacilityLayout $facilityLayout
     * @param bool $update
     */
    public function addPositions(array $requestData, FacilityLayout $facilityLayout, $update = false)
    {
    }

    /**
     * @param array $data
     * @param FacilityLayout $facilityLayout
     */
    public function getPositions(array &$data, FacilityLayout $facilityLayout)
    {
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return '';
    }
}
