<?php

namespace App\Service\Routine;

use App\Component\Api\Api;
use App\Component\Api\Response;
use App\Entity\Facility;
use App\Entity\Report;
use App\Service\ReportService;
use App\Service\Routine\DataCollector\RmaDataBackofficerCollector;
use App\Service\Routine\DataCollector\RmaDataCollector;
use App\Service\Routine\DataCollector\RmaDataCollectorDecorator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class BBCRMA implements RoutineInterface
{
    const NAME = 'BBC-RMA';

    /**
     * @var ParameterBagInterface
     */
    private $params;

    /**
     * @var Api
     */
    private $api;

    /**
     * @var RmaCashierProcessor
     */
    private $cashierProcessor;

    /**
     * @var RmaBackofficerProcessor
     */
    private $backofficerProcessor;

    /**
     * BBCRMA constructor.
     * @param EntityManagerInterface $em
     * @param ReportService $reportService
     * @param ParameterBagInterface $params
     * @param Api $api
     * @param RmaDataCollector $rmaDataCollector
     * @param RmaDataBackofficerCollector $rmaBackofficerDataCollector
     * @param RmaDataCollectorDecorator $rmaDataCollectorDecorator
     * @param EventDispatcherInterface $dispatcher
     * @param RmaCashierProcessor $cashierProcessor
     * @param RmaBackofficerProcessor $backofficerProcessor
     */
    public function __construct(
        EntityManagerInterface $em,
        ReportService $reportService,
        ParameterBagInterface $params,
        Api $api,
        RmaDataCollector $rmaDataCollector,
        RmaDataBackofficerCollector $rmaBackofficerDataCollector,
        RmaDataCollectorDecorator $rmaDataCollectorDecorator,
        EventDispatcherInterface $dispatcher,
        RmaCashierProcessor $cashierProcessor,
        RmaBackofficerProcessor $backofficerProcessor
    ) {
        $this->em = $em;
        $this->reportSevice = $reportService;
        $this->params = $params;
        $this->api = $api;
        $this->rmaDataCollector = $rmaDataCollector;
        $this->rmaBackofficerDataCollector = $rmaBackofficerDataCollector;
        $this->rmaDataCollectorDecorator = $rmaDataCollectorDecorator;
        $this->dispatcher = $dispatcher;
        $this->cashierProcessor = $cashierProcessor;
        $this->backofficerProcessor = $backofficerProcessor;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @return array
     */
    public function getParamTemplate()
    {
        return [
            'api_key' => '',
            'api_url' => '',
            'tenant_identifier' => '',
            'client_identifier' => '',
            'department' => '',
            'custom' =>
                [
                    'transfere_account_no' => '',
                    'voucher_cash_account_no' => '',
                    'debitor_account_no' => '',
                ],
        ];
    }

    /**
     * @return array
     */
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
                                            'key' => 'accountNo',
                                            'type' => 'text',
                                            'value' => 'input.accountNo',
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
                                            'key' => 'accountNo',
                                            'type' => 'text',
                                            'value' => 'input.accountNo',
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
                                            'key' => 'accountNo',
                                            'type' => 'text',
                                            'value' => 'input.accountNo',
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
                                            'key' => 'accountNo',
                                            'type' => 'text',
                                            'value' => 'input.accountNo',
                                            'view' => 'backoffice',
                                            'sequence' => 2,
                                        ],
                                    2 =>
                                        [
                                            'key' => 'addToTotalSalesAmount',
                                            'type' => 'checkbox',
                                            'value' => 'input.checkbox',
                                            'view' => 'backoffice',
                                            'sequence' => 3,
                                        ],
                                    3 =>
                                        [
                                            'key' => 'number',
                                            'type' => 'text',
                                            'view' => 'frontoffice',
                                            'sequence' => 1,
                                        ],
                                    4 =>
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
                                            'type' => 'text',
                                            'value' => 'input.name',
                                            'view' => 'backoffice',
                                            'sequence' => 1,
                                        ],
                                    1 =>
                                        [
                                            'key' => 'accountNo',
                                            'type' => 'text',
                                            'value' => 'input.accountNo',
                                            'view' => 'backoffice',
                                            'sequence' => 2,
                                        ],
                                    2 =>
                                        [
                                            'key' => 'tipAccountNo',
                                            'type' => 'text',
                                            'value' => 'input.tipAccountNo',
                                            'view' => 'backoffice',
                                            'sequence' => 3,
                                        ],
                                    3 =>
                                        [
                                            'key' => 'receiver',
                                            'type' => 'text',
                                            'view' => 'frontoffice',
                                            'sequence' => 1,
                                        ],
                                    4 =>
                                        [
                                            'key' => 'amount',
                                            'type' => 'currency',
                                            'view' => 'frontoffice',
                                            'sequence' => 2,
                                        ],
                                    5 =>
                                        [
                                            'key' => 'tip',
                                            'type' => 'currency',
                                            'view' => 'frontoffice',
                                            'sequence' => 3,
                                        ],
                                    6 =>
                                        [
                                            'key' => 'billSelection',
                                            'type' => 'select',
                                            'defaultValue' => 'Default Bill',
                                            'view' => 'frontoffice',
                                            'sequence' => 4,
                                        ],
                                ],
                        ],
                    6 =>
                        [
                            'key' => 'expenses',
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
                                            'key' => 'accountNo',
                                            'type' => 'text',
                                            'value' => 'input.accountNo',
                                            'view' => 'backoffice',
                                            'sequence' => 2,
                                        ],
                                    2 =>
                                        [
                                            'key' => 'catalogNumber',
                                            'type' => 'text',
                                            'value' => 'Expenses',
                                            'view' => 'frontoffice',
                                            'sequence' => 2,
                                        ],
                                    3 =>
                                        [
                                            'key' => 'name',
                                            'type' => 'text',
                                            'view' => 'frontoffice',
                                            'sequence' => 1,
                                        ],
                                    4 =>
                                        [
                                            'key' => 'amount',
                                            'type' => 'currency',
                                            'view' => 'frontoffice',
                                            'sequence' => 2,
                                        ],
                                ],
                        ],
                    7 =>
                        [
                            'key' => 'cigarettes',
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
                                            'key' => 'amount',
                                            'type' => 'currency',
                                            'view' => 'frontoffice',
                                            'sequence' => 1,
                                        ],
                                ],
                        ],
                    8 =>
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
                                            'key' => 'accountNo',
                                            'type' => 'text',
                                            'value' => 'input.accountNo',
                                            'view' => 'backoffice',
                                            'sequence' => 2,
                                        ],
                                    2 =>
                                        [
                                            'key' => 'cashier',
                                            'type' => 'dropdown',
                                            'view' => 'frontoffice',
                                            'sequence' => 1,
                                        ],
                                    3 =>
                                        [
                                            'key' => 'amount',
                                            'type' => 'label',
                                            'view' => 'frontoffice',
                                            'sequence' => 2,
                                        ],
                                ],
                        ],
                    9 =>
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
                ],
        ];
    }

    /**
     * @return string
     */
    public function getOverlayTemplate()
    {
        return 'report/includes/overlay/rma.html.twig';
    }

    /**
     * @param Facility|null $facility
     * @param $params
     * @return Response|null
     */
    public function testCallApi(Facility $facility = null, $params): ?Response
    {
        if ($facility) {
            $params = json_decode($params);
            $path = sprintf('%s/latest/clients/%s/?api_key=%s', $params->api_url, $params->client_identifier, $params->api_key);

            return $this->api->request->GET($path);
        }
    }

    /**
     * @param Report $report
     * @param array $requestData
     * @param bool $backofficerTrigger
     */
    public function saveCashier(Report $report, array $requestData)
    {
        $this->cashierProcessor->save($report, $requestData);
    }

    /**
     * @param Report $report
     * @param array $requestData
     * @param bool $backofficerTrigger
     */
    public function saveBackofficer(Report $report, array $requestData)
    {
        $this->backofficerProcessor->save($report, $requestData);
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

}
