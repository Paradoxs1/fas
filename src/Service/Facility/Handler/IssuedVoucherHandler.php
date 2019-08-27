<?php

namespace App\Service\Facility\Handler;

use App\Entity\AccountingCategory;
use App\Entity\FacilityLayout;
use App\Entity\FlexParam;
use App\Service\Facility\ConfigurationParamsHandler;
use App\Service\FlexParamService;


class IssuedVoucherHandler extends ConfigurationParamsHandler
{
    const CATEGORY_NAME = 'issuedVoucher';

    /**
     * @param array $requestData
     * @param FacilityLayout $facilityLayout
     * @return bool
     */
    public function checkChanges(array $requestData, FacilityLayout $facilityLayout): bool
    {
        $issuedVouchers = $this->em->getRepository(FlexParam::class)->findByCategoryAndFacilityLayout(
            $facilityLayout,
            $this->em->getRepository(AccountingCategory::class)->findOneBy(
                [
                    'key' => FlexParamService::ACCOUNTING_CATEGORY_ISSUED_VOUCHER_KEY
                ]
            ),
            'backoffice'
        );

        if ($issuedVouchers && !isset($requestData[static::CATEGORY_NAME])) {
            return true;
        }

        if (!$issuedVouchers && isset($requestData[static::CATEGORY_NAME])) {
            return true;
        }


        if (isset($issuedVouchers) && isset($requestData[static::CATEGORY_NAME])) {
            /** @var FlexParam $acceptedVoucher */
            foreach ($issuedVouchers as $acceptedVoucher) {
                $key = $acceptedVoucher->getKey();
                $accountingPosition = $acceptedVoucher->getAccountingPosition()->getId();

                if (count($issuedVouchers) != count($requestData[static::CATEGORY_NAME][$accountingPosition])) {
                    return true;
                }

                if (isset($requestData[static::CATEGORY_NAME][$accountingPosition][$key])) {
                    if ($acceptedVoucher->getValue() != $requestData[static::CATEGORY_NAME][$accountingPosition][$key]) {
                        return true;
                    }
                } else {
                    return true;
                }
            }
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
        $routine = $this->getRoutine($facilityLayout);
        $config = $routine->getAccountingPositionsTemplate();

        if (!isset($requestData[static::CATEGORY_NAME])) {
            return;
        }

        /** @var AccountingCategory $issuedVoucherCategory */
        $issuedVoucherCategory = $this->em->getRepository(AccountingCategory::class)->findOneBy(
            [
                'key' => static::CATEGORY_NAME
            ]
        );

        foreach ($config['AccountingPositions'] as $category) {
            $key = $category['key'];

            if ($key == static::CATEGORY_NAME) {
                $sequence = 1;
                foreach ($requestData[static::CATEGORY_NAME] as $row) {
                    $accountingPosition = $this->addAccountingPosition($issuedVoucherCategory, $facilityLayout, $sequence);

                    // $item it's param block from config
                    foreach ($category['flexParameter'] as $i => $item) {
                        $paramKey = $item['key'];

                        $value = isset($row[$paramKey]) ? $row[$paramKey] : '';
                        $this->addParam($paramKey, $item['type'], $value, $item['view'], $item['sequence'], $accountingPosition);

                    }
                    $sequence++;
                }
            }
        }
    }

    /**
     * @param array $data
     * @param FacilityLayout $facilityLayout
     */
    public function getPositions(array &$data, FacilityLayout $facilityLayout)
    {
        $params = $this->em->getRepository(FlexParam::class)->findByCategoriesAndFacilityLayout(
            $facilityLayout,
            [static::CATEGORY_NAME]
        );

        if ($params) {
            /** @var FlexParam $param */
            foreach ($params as $param) {
                $data[static::CATEGORY_NAME][$param->getAccountingPosition()->getId()][] = [
                    'key' => $param->getKey(),
                    'value' => $param->getValue(),
                    'type'  => $param->getType()
                ];
            }
        }
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return static::CATEGORY_NAME;
    }
}
