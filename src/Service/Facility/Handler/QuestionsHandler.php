<?php

namespace App\Service\Facility\Handler;

use App\Entity\AccountingCategory;
use App\Entity\FacilityLayout;
use App\Entity\FlexParam;
use App\Service\Facility\ConfigurationParamsHandler;
use App\Service\FlexParamService;


class QuestionsHandler extends ConfigurationParamsHandler
{
    const CATEGORY_NAME = 'questions';

    /**
     * @param array $requestData
     * @param FacilityLayout $facilityLayout
     * @return bool
     */
    public function checkChanges(array $requestData, FacilityLayout $facilityLayout): bool
    {
        $params = $this->em->getRepository(FlexParam::class)->findByCategoryAndFacilityLayout(
            $facilityLayout,
            $this->em->getRepository(AccountingCategory::class)->findOneBy(
                [
                    'key' => FlexParamService::ACCOUNTING_CATEGORY_QUESTION_KEY
                ]
            ),
            'backoffice'
        );

        if ($params && !isset($requestData['questions'])) {
            return true;
        }

        if (!$params && isset($requestData['questions'])) {
            return true;
        }


        if (isset($params) && isset($requestData['questions'])) {
            /** @var FlexParam $param */
            foreach ($params as $param) {
                $key = $param->getKey();
                $accountingPosition = $param->getAccountingPosition()->getId();

                if (isset($requestData['questions'][$accountingPosition][$key])) {
                    if ($param->getValue() != $requestData['questions'][$accountingPosition][$key]) {
                        return true;
                    }
                } else {
                    return true;
                }

                unset($requestData['questions'][$accountingPosition][$key]);
                if (count($requestData['questions'][$accountingPosition]) == 0) {
                    unset($requestData['questions'][$accountingPosition]);
                }
            }

            if (count($requestData['questions']) > 0) {
                return true;
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

        /** @var AccountingCategory $acceptedVoucherCategory */
        $acceptedVoucherCategory = $this->em->getRepository(AccountingCategory::class)->findOneBy(
            [
                'key' => static::CATEGORY_NAME
            ]
        );

        foreach ($config['AccountingPositions'] as $category) {
            $key = $category['key'];

            if ($key == static::CATEGORY_NAME) {
                $sequence = 1;
                foreach ($requestData[static::CATEGORY_NAME] as $row) {
                    $accountingPosition = $this->addAccountingPosition($acceptedVoucherCategory, $facilityLayout, $sequence);

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
