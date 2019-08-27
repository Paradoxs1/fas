<?php

namespace App\Service\Routine;

use App\Component\Api\Response;
use App\Entity\Facility;
use App\Entity\Report;

class AbaninjaRoutine implements RoutineInterface
{
    const NAME = 'AbaninjaRoutine';

    /**
     * @var AbaninjaCashierProcessor
     */
    private $cashierProcessor;

    /**
     * @var AbaninjaBackofficerProcessor
     */
    private $backofficerProcessor;

    /**
     * AbaninjaRoutine constructor.
     * @param AbaninjaCashierProcessor $cashierProcessor
     * @param AbaninjaBackofficerProcessor $backofficerProcessor
     */
    public function __construct(
        AbaninjaCashierProcessor $cashierProcessor,
        AbaninjaBackofficerProcessor $backofficerProcessor
    ) {
        $this->cashierProcessor = $cashierProcessor;
        $this->backofficerProcessor = $backofficerProcessor;
    }

    public function getName()
    {
        return self::NAME;
    }

    public function getParamTemplate()
    {
        return [];
    }

    public function getAccountingPositionsTemplate()
    {
        return [
            'AccountingPositions' =>
                [
                    0 =>
                        [
                            'key' => 'salesCategory',
                            'flexParameter' =>
                                [
                                    0 =>
                                        [
                                            'key' => 'name',
                                            'type' => 'text',
                                            'value' => 'input.name',
                                            'view' => 'backoffice',
                                            'sequence' => 1,
                                        ],
                                    1 =>
                                        [
                                            'key' => 'value',
                                            'type' => 'currency',
                                            'view' => 'frontoffice',
                                            'sequence' => 1,
                                        ],
                                ],
                        ],
                    1 =>
                        [
                            'key' => 'creditCard',
                            'flexParameter' =>
                                [
                                    0 =>
                                        [
                                            'key' => 'name',
                                            'type' => 'text',
                                            'value' => 'input.name',
                                            'view' => 'backoffice',
                                            'sequence' => 1,
                                        ],
                                    1 =>
                                        [
                                            'key' => 'value',
                                            'type' => 'currency',
                                            'view' => 'frontoffice',
                                            'sequence' => 1,
                                        ],
                                ],
                        ],
                    2 =>
                        [
                            'key' => 'acceptedVoucher',
                            'flexParameter' =>
                                [
                                    0 =>
                                        [
                                            'key' => 'name',
                                            'type' => 'label',
                                            'value' => 'Accepted Voucher',
                                            'view' => 'backoffice',
                                            'sequence' => 1,
                                        ],
                                    1 =>
                                        [
                                            'key' => 'number',
                                            'type' => 'text',
                                            'view' => 'frontoffice',
                                            'sequence' => 1,
                                        ],
                                    2 =>
                                        [
                                            'key' => 'amount',
                                            'type' => 'currency',
                                            'view' => 'frontoffice',
                                            'sequence' => 2,
                                        ],
                                ],
                        ],
                    3 =>
                        [
                            'key' => 'issuedVoucher',
                            'flexParameter' =>
                                [
                                    0 =>
                                        [
                                            'key' => 'name',
                                            'type' => 'label',
                                            'value' => 'Issued Voucher',
                                            'view' => 'backoffice',
                                            'sequence' => 1,
                                        ],
                                    1 =>
                                        [
                                            'key' => 'addToTotalSalesAmount',
                                            'type' => 'checkbox',
                                            'value' => 'input.checkbox',
                                            'view' => 'backoffice',
                                            'sequence' => 2,
                                        ],
                                    2 =>
                                        [
                                            'key' => 'number',
                                            'type' => 'text',
                                            'view' => 'frontoffice',
                                            'sequence' => 1,
                                        ],
                                    3 =>
                                        [
                                            'key' => 'amount',
                                            'type' => 'currency',
                                            'view' => 'frontoffice',
                                            'sequence' => 2,
                                        ],
                                ],
                        ],
                    4 =>
                        [
                            'key' => 'tip',
                            'flexParameter' =>
                                [
                                    0 =>
                                        [
                                            'key' => 'name',
                                            'type' => 'text',
                                            'value' => 'input.name',
                                            'view' => 'backoffice',
                                            'sequence' => 1,
                                        ],
                                    1 =>
                                        [
                                            'key' => 'tipInPercentage',
                                            'type' => 'percentage',
                                            'value' => 'input.tipInPercentage',
                                            'view' => 'backoffice',
                                            'sequence' => 2,
                                        ],
                                    2 =>
                                        [
                                            'key' => 'value',
                                            'type' => 'currency',
                                            'view' => 'frontoffice',
                                            'sequence' => 1,
                                        ],
                                ],
                        ],
                    5 =>
                        [
                            'key' => 'bill',
                            'flexParameter' =>
                                [
                                    0 =>
                                        [
                                            'key' => 'name',
                                            'type' => 'label',
                                            'value' => 'Default Bill',
                                            'view' => 'backoffice',
                                            'sequence' => 1,
                                        ],
                                    1 =>
                                        [
                                            'key' => 'receiver',
                                            'type' => 'text',
                                            'view' => 'frontoffice',
                                            'sequence' => 1,
                                        ],
                                    2 =>
                                        [
                                            'key' => 'amount',
                                            'type' => 'currency',
                                            'view' => 'frontoffice',
                                            'sequence' => 2,
                                        ],
                                    3 =>
                                        [
                                            'key' => 'tip',
                                            'type' => 'currency',
                                            'view' => 'frontoffice',
                                            'sequence' => 3,
                                        ],
                                    4 =>
                                        [
                                            'key' => 'billSelection',
                                            'type' => 'select',
                                            'defaultValue' => 'Default Bill',
                                            'view' => 'frontoffice',
                                            'sequence' => 4,
                                        ],
                                ],
                        ],
//                    6 =>
//                        [
//                            'key' => 'bill',
//                            'flexParameter' =>
//                                [
//                                    0 =>
//                                        [
//                                            'key' => 'name',
//                                            'type' => 'text',
//                                            'value' => 'input.name',
//                                            'view' => 'backoffice',
//                                            'sequence' => 1,
//                                        ],
//                                    1 =>
//                                        [
//                                            'key' => 'receiver',
//                                            'type' => 'text',
//                                            'view' => 'frontoffice',
//                                            'sequence' => 1,
//                                        ],
//                                    2 =>
//                                        [
//                                            'key' => 'amount',
//                                            'type' => 'currency',
//                                            'view' => 'frontoffice',
//                                            'sequence' => 2,
//                                        ],
//                                    3 =>
//                                        [
//                                            'key' => 'tip',
//                                            'type' => 'currency',
//                                            'view' => 'frontoffice',
//                                            'sequence' => 3,
//                                        ],
//                                    4 =>
//                                        [
//                                            'key' => 'billSelection',
//                                            'type' => 'select',
//                                            'defaultValue' => 'Default Bill',
//                                            'view' => 'frontoffice',
//                                            'sequence' => 4,
//                                        ],
//                                ],
//                        ],
                    7 =>
                        [
                            'key' => 'expenses',
                            'flexParameter' =>
                                [
                                    0 =>
                                        [
                                            'key' => 'name',
                                            'type' => 'label',
                                            'value' => 'Expenses',
                                            'view' => 'backoffice',
                                            'sequence' => 1,
                                        ],
                                    1 =>
                                        [
                                            'key' => 'catalogNumber',
                                            'type' => 'text',
                                            'value' => 'Expenses',
                                            'view' => 'frontoffice',
                                            'sequence' => 2,
                                        ],
                                    2 =>
                                        [
                                            'key' => 'name',
                                            'type' => 'text',
                                            'view' => 'frontoffice',
                                            'sequence' => 1,
                                        ],
                                    3 =>
                                        [
                                            'key' => 'amount',
                                            'type' => 'currency',
                                            'view' => 'frontoffice',
                                            'sequence' => 2,
                                        ],
                                ],
                        ],
                    8 =>
                        [
                            'key' => 'cigarettes',
                            'flexParameter' =>
                                [
                                    0 =>
                                        [
                                            'key' => 'name',
                                            'type' => 'label',
                                            'value' => 'Cigarettes',
                                            'view' => 'backoffice',
                                            'sequence' => 1,
                                        ],
                                    1 =>
                                        [
                                            'key' => 'amount',
                                            'type' => 'currency',
                                            'view' => 'frontoffice',
                                            'sequence' => 1,
                                        ],
                                ],
                        ],
                    9 =>
                        [
                            'key' => 'cash',
                            'flexParameter' =>
                                [
                                    0 =>
                                        [
                                            'key' => 'name',
                                            'type' => 'label',
                                            'value' => 'Cash',
                                            'view' => 'backoffice',
                                            'sequence' => 1,
                                        ],
                                    1 =>
                                        [
                                            'key' => 'cashier',
                                            'type' => 'dropdown',
                                            'view' => 'frontoffice',
                                            'sequence' => 1,
                                        ],
                                    2 =>
                                        [
                                            'key' => 'amount',
                                            'type' => 'label',
                                            'view' => 'frontoffice',
                                            'sequence' => 2,
                                        ],
                                ],
                        ],
                    10 =>
                        [
                            'key' => 'questions',
                            'flexParameter' =>
                                [
                                    0 =>
                                        [
                                            'key' => 'questionName',
                                            'type' => 'text',
                                            'value' => 'input.questionName',
                                            'view' => 'backoffice',
                                            'sequence' => 1,
                                        ],
                                    1 =>
                                        [
                                            'key' => 'answer',
                                            'type' => 'text',
                                            'view' => 'frontoffice',
                                            'sequence' => 1,
                                        ],
                                ],
                        ],
                    11 =>
                        [
                            'key' => 'totalSales',
                            'flexParameter' =>
                                [
                                    0 =>
                                        [
                                            'key' => 'value',
                                            'type' => 'currency',
                                            'view' => 'frontoffice',
                                            'sequence' => 1,
                                        ],
                                ],
                        ],
                    12 =>
                        [
                            'key' => 'comment',
                            'flexParameter' =>
                                [
                                    0 =>
                                        [
                                            'key' => 'value',
                                            'type' => 'textfield',
                                            'view' => 'frontoffice',
                                            'sequence' => 1,
                                        ],
                                ],
                        ],
                ],
        ];
    }

    public function getOverlayTemplate()
    {
        return null;
    }

    public function testCallApi(Facility $facility = null, $params): ?Response
    {
        return null;
    }

    /**
     * @return RoutineProcessorInterface|null
     */
    public function getBackofficerProcessor(): ?RoutineProcessorInterface
    {
        return $this->backofficerProcessor;
    }

    /**
     * @return RoutineProcessorInterface|null
     */
    public function getCashierProcessor(): ?RoutineProcessorInterface
    {
        return $this->cashierProcessor;
    }

    public function saveCashier(Report $report, array $requestData)
    {
    }

    public function saveBackofficer(Report $report, array $requestData)
    {
    }
}
