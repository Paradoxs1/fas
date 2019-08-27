<?php

namespace App\Service\Routine\DataCollector;

use App\Entity\AccountingCategory;
use App\Entity\AccountingPosition;
use App\Entity\Facility;
use App\Entity\FlexParam;
use App\Entity\Report;
use App\Service\FlexParamService;
use App\Service\Report\CategoryReportPositionHandlerComposite;

class RmaDataBackofficerCollector extends RmaAbstractCollector implements RmaDataCollectorIntrface
{
    /**
     * @param Report|null $report
     * @param array $reports
     * @param array $data
     * @param array $requestData
     */
    public function getCreditCards(Report $report = null, array $reports = [], array &$data, array $requestData = []): void
    {
        if (isset($requestData['credit-cards'])) {
            $flexParamRepository = $this->em->getRepository(FlexParam::class);

            foreach ($requestData['credit-cards'] as $terminal) {
                foreach ($terminal as $key => $value) {
                    foreach ($value as $amount) {
                        $this->summationCreditCards($data, $key, $flexParamRepository, CategoryReportPositionHandlerComposite::formatAmount($amount));
                    }
                }
            }
        }
    }

    /**
     * @param array $reports
     * @param array $data
     * @param array $requestData
     * @param Facility|null $facility
     */
    public function getIssuedVouchers(array $reports = [], array &$data, array $requestData = [], Facility $facility = null): void
    {
        if (isset($requestData['issued-vouchers'])) {
            $this->getVouchers($data, $requestData, $facility, FlexParamService::ACCOUNTING_CATEGORY_ISSUED_VOUCHER_KEY, 'issued_vouchers', true);
        }
    }

    /**
     * @param array $reports
     * @param array $data
     * @param array $requestData
     * @param Facility|null $facility
     */
    public function getAcceptedVouchers(array $reports = [], array &$data, array $requestData = [], Facility $facility = null): void
    {
        if (isset($requestData['accepted-vouchers'])) {
            $this->getVouchers($data, $requestData, $facility, FlexParamService::ACCOUNTING_CATEGORY_ACCEPTED_VOUCHER_KEY, 'accepted_vouchers');
        }
    }

    /**
     * @param array $data
     * @param array $requestData
     * @param Facility $facility
     * @param string $category
     * @param string $keyArray
     * @param bool $addTotal
     */
    private function getVouchers(array &$data, array $requestData, Facility $facility, string $category, string $keyArray,  bool $addTotal = false): void
    {
        $category = $this->em->getRepository(AccountingCategory::class)->findOneBy(['key' => $category]);
        $flexParamRepository = $this->em->getRepository(FlexParam::class);
        $accountingPosition = $this->em->getRepository(AccountingPosition::class)->findOneBy([
            'accountingCategory' => $category->getId(),
            'facilityLayout' => $facility->getFacilityLayouts()->last()->getId()
        ]);

        $findParam = ['view' => 'backoffice', 'accountingPosition' => $accountingPosition->getId()];
        $accountNo = $flexParamRepository->findOneBy(array_merge($findParam, ['key' => 'accountNo']));

        if ($addTotal) {
            $addTotal = $flexParamRepository->findOneBy(array_merge($findParam, ['key' => 'addToTotalSalesAmount']));
            $data['addTotal'] = $addTotal->getValue() ? true : false;
        }

        foreach ($requestData[str_replace('_', '-', $keyArray)] as $item) {
            $item['accountNo'] = $accountNo->getValue();
            $item['amount'] = CategoryReportPositionHandlerComposite::formatAmount($item['amount']);
            $data[$keyArray][] = $item;
        }
    }

    /**
     * @param Report|null $report
     * @param array $reports
     * @param array $data
     * @param array $requestData
     */
    public function getSales(Report $report = null, array $reports = [], array &$data, array $requestData = []): void
    {
        if (isset($requestData['sales'])) {
            $flexParamRepository = $this->em->getRepository(FlexParam::class);

            foreach ($requestData['sales'] as $key => $value) {
                $value = CategoryReportPositionHandlerComposite::formatAmount($value);
                $this->transformSales($data, $flexParamRepository, $key, $value);
            }
        }
    }

    /**
     * @param array $reports
     * @param array $data
     * @param array $requestData
     * @param Facility|null $facility
     */
    public function getBills(array $reports = [], array &$data, array $requestData = [], Facility $facility = null): void
    {
        if (isset($requestData['bills'])) {
            $flexParamRepository = $this->em->getRepository(FlexParam::class);
            $category = $this->em->getRepository(AccountingCategory::class)->findOneBy(['key' => FlexParamService::ACCOUNTING_CATEGORY_BILL_KEY]);
            $accountingPosition = $this->em->getRepository(AccountingPosition::class)->findOneBy([
                'accountingCategory' => $category->getId(),
                'facilityLayout' => $facility->getFacilityLayouts()->last()->getId()
            ]);

            $findParam = ['view' => 'backoffice', 'accountingPosition' => $accountingPosition->getId()];

            foreach ($requestData['bills'] as $key => $value) {
                $tipAccountNo = $flexParamRepository->findOneBy(array_merge($findParam, ['key' => 'tipAccountNo']));
                $accountNo = $flexParamRepository->findOneBy(array_merge($findParam, ['key' => 'accountNo']));

                if ($accountNo) {
                    $data['bills'][$key]['accountNo'] = $accountNo->getValue();
                }

                if ($tipAccountNo) {
                    $data['bills'][$key]['tipAccountNo'] = $tipAccountNo->getValue();
                }

                if (isset($value['amount'])) {
                    $data['bills'][$key]['amount'] = CategoryReportPositionHandlerComposite::formatAmount($value['amount']);
                }

                if (isset($value['tip'])) {
                    $data['bills'][$key]['tip'] = CategoryReportPositionHandlerComposite::formatAmount($value['tip']);
                }

                if (isset($value['receiver'])) {
                    $data['bills'][$key]['receiver'] = $value['receiver'];
                }
            }
        }
    }

    /**
     * @param array $reports
     * @param array $data
     * @param array $requestData
     * @param Facility|null $facility
     */
    public function getExpenses(array $reports = [],array &$data, array $requestData = [], Facility $facility = null): void
    {
        if (isset($requestData['expenses'])) {
            $flexParamRepository = $this->em->getRepository(FlexParam::class);
            $category = $this->em->getRepository(AccountingCategory::class)->findOneBy(['key' => FlexParamService::ACCOUNTING_CATEGORY_EXPENSES_KEY]);
            $accountingPosition = $this->em->getRepository(AccountingPosition::class)->findOneBy([
                'accountingCategory' => $category->getId(),
                'facilityLayout' => $facility->getFacilityLayouts()->last()->getId()
            ]);

            $findParam = ['view' => 'backoffice', 'accountingPosition' => $accountingPosition->getId()];

            foreach ($requestData['expenses'] as $key => $value) {
                $accountNo = $flexParamRepository->findOneBy(array_merge($findParam, ['key' => 'accountNo']));

                if ($accountNo) {
                    $data['expenses'][$key]['accountNo'] = $accountNo->getValue();
                }

                $data['expenses'][$key]['amount'] = CategoryReportPositionHandlerComposite::formatAmount($value['amount']);
                $data['expenses'][$key]['name'] = $value['name'];
            }
        }
    }

    /**
     * @param array $reports
     * @param array $data
     * @param array $requestData
     * @param Facility|null $facility
     */
    public function getCash(array $reports = [],array &$data, array $requestData = [], Facility $facility = null): void
    {
        if (isset($requestData['cash-income'])) {
            $cashCategory = $this->em->getRepository(AccountingCategory::class)->findOneBy(['key' => FlexParamService::ACCOUNTING_CATEGORY_CASH_KEY]);
            $flexParamRepository = $this->em->getRepository(FlexParam::class);
            $accountingPosition = $this->em->getRepository(AccountingPosition::class)->findOneBy([
                'accountingCategory' => $cashCategory->getId(),
                'facilityLayout' => $facility->getFacilityLayouts()->last()->getId()
            ]);

            $findParam = ['view' => 'backoffice', 'accountingPosition' => $accountingPosition->getId()];
            $accountNo = $flexParamRepository->findOneBy(array_merge($findParam, ['key' => 'accountNo']));
            $name = $flexParamRepository->findOneBy(array_merge($findParam, ['key' => 'name']));

            if ($accountNo) {
                $data['cash']['accountNo'] = $accountNo->getValue();
            }

            if ($name) {
                $data['cash']['name'] = $name->getValue();
            }

            $data['cash']['sum'] = CategoryReportPositionHandlerComposite::formatAmount($requestData['cash-income']);
        }
    }

    /**
     * @param array $reports
     * @param array $data
     * @param array $requestData
     */
    public function getCigarettes(array $reports = [], array &$data, array $requestData = []): void
    {
        if (isset($requestData['cigarettes'])) {
            $data['cigarettes']['sum'] = CategoryReportPositionHandlerComposite::formatAmount($requestData['cigarettes']);
        }
    }

    /**
     * @param array $reports
     * @param array $data
     * @param array $requestData
     */
    public function getTips(array $reports = [], array &$data, array $requestData = []): void
    {
        if (isset($requestData['tips'])) {
            $flexParamRepository = $this->em->getRepository(FlexParam::class);

            foreach ($requestData['tips'] as $key => $value) {
                $param = $flexParamRepository->findOneBy(['key' => 'name', 'view' => 'backoffice', 'accountingPosition' => $key]);
                $data['tips'][$param->getValue()] = CategoryReportPositionHandlerComposite::formatAmount($value);
            }
        }
    }
}
