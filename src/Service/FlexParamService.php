<?php

namespace App\Service;

use App\Entity\AccountingCategory;
use App\Entity\AccountingPosition;
use App\Entity\FacilityLayout;
use App\Entity\FlexParam;
use App\Entity\Report;

use Doctrine\Common\Persistence\ObjectManager;

/**
 * TODO: refactor the methods !!!
 *
 * Class FlexParamService
 * @package App\Service
 */
class FlexParamService
{
    /**
     * ACCOUNTING_CATEGORY_SALES_CATEGORY_KEY const
     */
    const ACCOUNTING_CATEGORY_SALES_CATEGORY_KEY = 'salesCategory';

    /**
     * ACCOUNTING_CATEGORY_CREDIT_CARD_KEY const
     */
    const ACCOUNTING_CATEGORY_CREDIT_CARD_KEY = 'creditCard';

    /**
     * ACCOUNTING_CATEGORY_ACCEPTED_VOUCHER_KEY const
     */
    const ACCOUNTING_CATEGORY_ACCEPTED_VOUCHER_KEY = 'acceptedVoucher';

    /**
     * ACCOUNTING_CATEGORY_ISSUED_VOUCHER_KEY const
     */
    const ACCOUNTING_CATEGORY_ISSUED_VOUCHER_KEY = 'issuedVoucher';

    /**
     * ACCOUNTING_CATEGORY_TIP_KEY const
     */
    const ACCOUNTING_CATEGORY_TIP_KEY = 'tip';

    /**
     * ACCOUNTING_CATEGORY_BILL_KEY const
     */
    const ACCOUNTING_CATEGORY_BILL_KEY = 'bill';

    /**
     * ACCOUNTING_CATEGORY_EXPENSES_KEY const
     */
    const ACCOUNTING_CATEGORY_EXPENSES_KEY = 'expenses';

    /**
     * ACCOUNTING_CATEGORY_CIGARETTES_KEY const
     */
    const ACCOUNTING_CATEGORY_CIGARETTES_KEY = 'cigarettes';

    /**
     * ACCOUNTING_CATEGORY_CASH_KEY const
     */
    const ACCOUNTING_CATEGORY_CASH_KEY = 'cash';

    /**
     * ACCOUNTING_CATEGORY_QUESTION_KEY const
     */
    const ACCOUNTING_CATEGORY_QUESTION_KEY = 'questions';

    /**
     * ACCOUNTING_CATEGORY_COMMENT_KEY  Report only const
     */
    const ACCOUNTING_CATEGORY_COMMENT_KEY = 'comment';

    /**
     * ACCOUNTING_CATEGORY_TOTAL_SALES_KEY Report only const
     */
    const ACCOUNTING_CATEGORY_TOTAL_SALES_KEY = 'totalSales';

    /**
     * @var array
     */
    private $postValuesToCheck = [
        'creditCard'      => 0,
        'acceptedVoucher' => 0,
        'issuedVoucher'   => 0,
        'tip'              => 0,
        'bill'             => 0,
        'expenses'          => 0,
        'cash'             => 0,
        'cigarettes'        => 0
    ];

    /**
     * @var ObjectManager
     */
    private $manager;

    /**
     * @var ObjectManager
     */
    private $accountingPositionService;

    /**
     * FlexParamService constructor.
     * @param ObjectManager $manager
     * @param AccountingPositionService $accountingPositionService
     * @param ReportPositionValueService $reportPositionValueService
     */
    public function __construct(
        ObjectManager $manager,
        AccountingPositionService $accountingPositionService,
        ReportPositionValueService $reportPositionValueService
    ) {
        $this->manager = $manager;
        $this->accountingPositionService = $accountingPositionService;
        $this->reportPositionValueService = $reportPositionValueService;
    }

    /**
     * @param FacilityLayout $facilityLayout
     * @return mixed
     */
    public function getSalesCategories(FacilityLayout $facilityLayout)
    {
        $result = [];

        $salesCategoryCategory = $this->manager->getRepository(AccountingCategory::class)->findOneBy(
            [
                'key' => self::ACCOUNTING_CATEGORY_SALES_CATEGORY_KEY
            ]
        );

        if (!$salesCategoryCategory) {
            return $result;
        }

        $salesCategories = $this->manager->getRepository(FlexParam::class)->findByCategoryAndFacilityLayout(
            $facilityLayout,
            $salesCategoryCategory,
            'backoffice'
        );

        if (!$salesCategories) {
            return $result;
        }

        foreach ($salesCategories as $salesCategory) {
            $accountingPositionId = $salesCategory->getAccountingPosition()->getId();
            $key = $salesCategory->getKey();

            if ('name' == $key) {
                $result[$accountingPositionId]['name'] = $salesCategory->getValue();
            }
            else if ('accountNo' == $key) {
                $result[$accountingPositionId]['value'] = $salesCategory->getValue();
            }
        }

        return $result;
    }

    /**
     * @param FacilityLayout $facilityLayout
     * @return mixed
     */
    public function getPaymentMethods(FacilityLayout $facilityLayout)
    {
        $result = [];

        $categories = [
            self::ACCOUNTING_CATEGORY_CREDIT_CARD_KEY,
            self::ACCOUNTING_CATEGORY_TIP_KEY,
            self::ACCOUNTING_CATEGORY_CIGARETTES_KEY,
            self::ACCOUNTING_CATEGORY_EXPENSES_KEY,
            self::ACCOUNTING_CATEGORY_CASH_KEY,
            self::ACCOUNTING_CATEGORY_ACCEPTED_VOUCHER_KEY,
            self::ACCOUNTING_CATEGORY_ISSUED_VOUCHER_KEY,
            self::ACCOUNTING_CATEGORY_BILL_KEY,
        ];

        $paymentMethods = $this->manager->getRepository(FlexParam::class)->findByCategoriesAndFacilityLayout(
            $facilityLayout,
            $categories
        );

        if (!$paymentMethods) {
            return $result;
        }

        $paymentMethodsSorted = [];

        $categoriesIdsOrder = json_decode($facilityLayout->getPaymentMethodOrder());

        if ($categoriesIdsOrder) {
            foreach ($categoriesIdsOrder as $categoriesIdsOrderItem) {
                foreach ($paymentMethods as $paymentMethod) {
                    $categoryId = $paymentMethod->getAccountingPosition()->getAccountingCategory()->getId();

                    if ($categoriesIdsOrderItem == $categoryId) {
                        $paymentMethodsSorted[] = $paymentMethod;
                    }
                }
            }
        }

        $paymentMethods = $paymentMethodsSorted ? $paymentMethodsSorted : $paymentMethods;

        //-------------------------------------

        foreach ($paymentMethods as $paymentMethod) {
            $accountingPosition   = $paymentMethod->getAccountingPosition();
            $accountingPositionId = $accountingPosition->getId();
            $categoryKey          = $accountingPosition->getAccountingCategory()->getKey();
            $key                  = $paymentMethod->getKey();
            $value                = $paymentMethod->getValue();

            //Credit card && Expenses

            if (
                self::ACCOUNTING_CATEGORY_CREDIT_CARD_KEY == $categoryKey ||
                self::ACCOUNTING_CATEGORY_EXPENSES_KEY == $categoryKey    ||
                self::ACCOUNTING_CATEGORY_CASH_KEY == $categoryKey
            ) {
                if ('name' == $key) {
                    $result[$categoryKey][$accountingPositionId]['name'] = $value;
                }
                else if ('accountNo' == $key) {
                    $result[$categoryKey][$accountingPositionId]['value'] = $value;
                }
            }

            //Tip

            if (self::ACCOUNTING_CATEGORY_TIP_KEY == $categoryKey) {
                if ('name' == $key) {
                    $result[$categoryKey][$accountingPositionId]['name'] = $value;
                }
                else if ('tipInPercentage' == $key) {
                    $result[$categoryKey][$accountingPositionId]['value'] = $value;
                }
            }

            //Cigarettes

            if (self::ACCOUNTING_CATEGORY_CIGARETTES_KEY == $categoryKey) {
                if ('name' == $key) {
                    $result[$categoryKey][$accountingPositionId]['name'] = $value;
                }
            }

            //Accepted Voucher

            if (self::ACCOUNTING_CATEGORY_ACCEPTED_VOUCHER_KEY == $categoryKey) {
                if ('name' == $key) {
                    $result[$categoryKey][$accountingPositionId]['name'] = $value;
                }
                else if ('accountNo' == $key) {
                    $result[$categoryKey][$accountingPositionId]['value'] = $value;
                }
            }

            //Issued Voucher

            if (self::ACCOUNTING_CATEGORY_ISSUED_VOUCHER_KEY == $categoryKey) {
                if ('name' == $key) {
                    $result[$categoryKey][$accountingPositionId]['name'] = $value;
                }
                else if ('accountNo' == $key) {
                    $result[$categoryKey][$accountingPositionId]['value'] = $value;
                }
                else if ('addToTotalSalesAmount' == $key) {
                    $result[$categoryKey][$accountingPositionId]['addToTotalSalesAmount'] = $value;
                }
            }

            //Bill

            if (self::ACCOUNTING_CATEGORY_BILL_KEY == $categoryKey) {
                $result[$categoryKey][$accountingPositionId]['predefined'] = $accountingPosition->getPredefined();

                if ('name' == $key) {
                    $result[$categoryKey][$accountingPositionId]['name'] = $value;
                }
                else if ('accountNo' == $key) {
                    $result[$categoryKey][$accountingPositionId]['value'] = $value;
                }
                else if ('tipAccountNo' == $key) {
                    $result[$categoryKey][$accountingPositionId]['tipAccountNo'] = $value;
                }
            }
        }

        return $result;
    }

    /**
     * @param FacilityLayout $facilityLayout
     * @param Report|null $report
     * @param bool $answers
     * @return array
     */
    public function getQuestions(FacilityLayout $facilityLayout, Report $report = null, $answers = false)
    {
        $result = [];

        $questionsCategory = $this->manager->getRepository(AccountingCategory::class)->findOneBy(
            [
                'key' => self::ACCOUNTING_CATEGORY_QUESTION_KEY
            ]
        );

        if (!$questionsCategory) {
            return $result;
        }

        $questions = $this->manager->getRepository(FlexParam::class)->findByCategoryAndFacilityLayout(
            $facilityLayout,
            $questionsCategory,
            'backoffice'
        );

        if (!$questions) {
            return $result;
        }

        foreach ($questions as $question) {
            $accountingPosition = $question->getAccountingPosition();
            $accountingPositionId = $accountingPosition->getId();
            $key = $question->getKey();

            if ('questionName' == $key) {
                $result[$accountingPositionId]['name'] = $question->getValue();

                if ($report && $answers) {
                    $reportPositionValue = $this->reportPositionValueService
                        ->getReportPositionValueByAccountingPositionAndReport($accountingPosition, $report);

                    if ($reportPositionValue) {
                        $result[$accountingPositionId]['answer'] = $reportPositionValue->getValue();
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param array $requestData
     * @return array
     */
    private function filterFlexParamsData(array $requestData = [])
    {
        if (!isset($requestData['sales-category-name'])) {
            return $requestData;
        }

        //Clean Sales category

        foreach ($requestData['sales-category-name'] as $k => $item) {
            $categoryName = trim($item);

            if (!$categoryName) {
                unset($requestData['sales-category-name'][$k]);
            }
        }

        //Clean Credit Cards

        if (isset($requestData['credit-card-name'])) {
            foreach ($requestData['credit-card-name'] as $k => $item) {
                $creditCardName = trim($item);

                if (!$creditCardName) {
                    unset($requestData['credit-card-name'][$k]);
                }
            }
        }

        //Clean Tips

        if (isset($requestData['tip-name'])) {
            foreach ($requestData['tip-name'] as $k => $item) {
                $tipName = trim($item);

                if (!$tipName) {
                    unset($requestData['tip-name'][$k]);
                }
            }
        }

        //Clean Expenses

        if (isset($requestData['expense-name'])) {
            foreach ($requestData['expense-name'] as $k => $item) {
                $expenseName = trim($item);

                if (!$expenseName) {
                    unset($requestData['expense-name'][$k]);
                }
            }
        }

        //Clean Cigarettes

        if (isset($requestData['cigarette-name'])) {
            foreach ($requestData['cigarette-name'] as $k => $item) {
                $cigaretteName = trim($item);

                if (!$cigaretteName) {
                    unset($requestData['cigarette-name'][$k]);
                }
            }
        }

        //Clean Cash

        if (isset($requestData['cash-name'])) {
            foreach ($requestData['cash-name'] as $k => $item) {
                $cashName = trim($item);

                if (!$cashName) {
                    unset($requestData['cash-name'][$k]);
                }
            }
        }

        //Clean Accepted Voucher

        if (isset($requestData['accepted-voucher-name'])) {
            foreach ($requestData['accepted-voucher-name'] as $k => $item) {
                $acceeptedVoucherName = trim($item);

                if (!$acceeptedVoucherName) {
                    unset($requestData['accepted-voucher-name'][$k]);
                }
            }
        }

        //Clean Issued Voucher

        if (isset($requestData['issued-voucher-name'])) {
            foreach ($requestData['issued-voucher-name'] as $k => $item) {
                $issuedVoucherName = trim($item);

                if (!$issuedVoucherName) {
                    unset($requestData['issued-voucher-name'][$k]);
                }
            }
        }

        //Clean Bill

        if (isset($requestData['bill-name'])) {
            foreach ($requestData['bill-name'] as $k => $item) {
                $voucherName = trim($item);

                if (!$voucherName) {
                    unset($requestData['bill-name'][$k]);
                }
            }
        }

        //Clean questions

        if (isset($requestData['question-name'])) {
            foreach ($requestData['question-name'] as $k => $item) {
                $questionName = trim($item);

                if (!$questionName) {
                    unset($requestData['question-name'][$k]);
                }
            }
        }

        return $requestData;
    }

    /**
     * @param $requestData
     * @param FacilityLayout $facilityLayout
     * @return bool
     */
    public function paymentMethodOrderChanged($requestData, FacilityLayout $facilityLayout)
    {
        $i = 0;
        $postValuesToCheck = [];

        $paymentMethodsOrder = $facilityLayout->getPaymentMethodOrder();

        $paymentMethodsOrder = $paymentMethodsOrder ? json_decode($paymentMethodsOrder) : [];

        foreach ($requestData as $k => $requestDataItem) {
            ++$i;

            foreach ($this->postValuesToCheck as $postValueToCheck => $position) {
                if ($k == $postValueToCheck) {
                    $postValuesToCheck[$postValueToCheck] = $i;
                }
            }
        }

        asort($postValuesToCheck);

        $postValuesKey = $this->postValuesToKeys($postValuesToCheck);

        if (count($paymentMethodsOrder) != count($postValuesKey)) {
            return true;
        }

        foreach ($postValuesKey as $postValue => $categoryKey) {
            $category = $this->manager->getRepository(AccountingCategory::class)->findOneBy(['key' => $categoryKey]);

            if ($category->getId() != current($paymentMethodsOrder)) {
                return true;
            }

            next($paymentMethodsOrder);
        }
    }

    /**
     * @param $postValuesToCheck
     * @return array
     */
    private function postValuesToKeys($postValuesToCheck)
    {
        if (!$postValuesToCheck) {
            return [];
        }

        foreach ($postValuesToCheck as $postValuesToCheckItem => $sequence) {
            if ('creditCard' == $postValuesToCheckItem) {
                $postValuesToCheck[$postValuesToCheckItem] = 'creditCard';
            }

            if ('acceptedVoucher' == $postValuesToCheckItem) {
                $postValuesToCheck[$postValuesToCheckItem] = 'acceptedVoucher';
            }

            if ('issuedVoucher' == $postValuesToCheckItem) {
                $postValuesToCheck[$postValuesToCheckItem] = 'issuedVoucher';
            }

            if ('tip' == $postValuesToCheckItem) {
                $postValuesToCheck[$postValuesToCheckItem] = 'tip';
            }

            if ('bill' == $postValuesToCheckItem) {
                $postValuesToCheck[$postValuesToCheckItem] = 'bill';
            }

            if ('expenses' == $postValuesToCheckItem) {
                $postValuesToCheck[$postValuesToCheckItem] = 'expenses';
            }

            if ('cash' == $postValuesToCheckItem) {
                $postValuesToCheck[$postValuesToCheckItem] = 'cash';
            }

            if ('cigarettes' == $postValuesToCheckItem) {
                $postValuesToCheck[$postValuesToCheckItem] = 'cigarettes';
            }
        }

        return $postValuesToCheck;
    }

    /**
     * @param $requestData
     * @param FacilityLayout $facilityLayout
     */
    public function updatePaymentMethodOrder($requestData, FacilityLayout $facilityLayout)
    {
        $postValuesToCheck = $paymentMethodsOrder = [];
        $i = 0;

        foreach ($requestData as $k => $requestDataItem) {
            ++$i;

            foreach ($this->postValuesToCheck as $postValueToCheck => $position) {
                if ($k == $postValueToCheck) {
                    $postValuesToCheck[$postValueToCheck] = $i;
                }
            }
        }

        asort($postValuesToCheck);

        $postValuesKey = $this->postValuesToKeys($postValuesToCheck);

        foreach ($postValuesKey as $postValue => $categoryKey) {
            $category = $this->manager->getRepository(AccountingCategory::class)->findOneBy([
                'key' => $categoryKey
            ]);

            $paymentMethodsOrder[] = $category->getId();
        }

        $facilityLayout->setPaymentMethodOrder(json_encode($paymentMethodsOrder));
        $this->manager->persist($facilityLayout);
    }
}
