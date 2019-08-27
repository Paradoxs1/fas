<?php

namespace App\Service\Routine;

use App\Component\Api\Api;
use App\Component\Api\Response;
use App\Entity\Report;
use App\Event\ApiResponseEvent;
use App\Repository\ReportRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Translation\TranslatorInterface;


abstract class RmaProcessor
{
    /**
     * @var ParameterBagInterface
     */
    protected $params;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var array
     */
    protected $errorMessages = [];

    /**
     * @var RoutineRegistry
     */
    protected $routineRegistry;

    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * RmaProcessor constructor.
     * @param EntityManagerInterface $em
     * @param ParameterBagInterface $params
     * @param EventDispatcherInterface $dispatcher
     * @param Api $api
     * @param RoutineRegistry $routineRegistry
     * @param SessionInterface $session
     * @param TranslatorInterface $translator
     */
    public function __construct(
        EntityManagerInterface $em,
        ParameterBagInterface $params,
        EventDispatcherInterface $dispatcher,
        Api $api,
        RoutineRegistry $routineRegistry,
        SessionInterface $session,
        TranslatorInterface $translator
    ) {
        $this->em = $em;
        $this->params = $params;
        $this->api = $api;
        $this->dispatcher = $dispatcher;
        $this->routineRegistry = $routineRegistry;
        $this->session = $session;
        $this->translator = $translator;
    }

    /**
     * @param Report $report
     * @param array $requestData
     */
    protected function createInvoice(Report $report, array $requestData)
    {
        $facility = $report->getFacilityLayout()->getFacility();
        $params = json_decode($facility->getRoutine()->getParams());
        $invoiceNumberPrefix = $this->params->get('invoiceNumberPrefix');

        if (empty($params->api_key)) {
            throw new \InvalidArgumentException("Required parameter 'api_key' is missing");
        }

        if (empty($params->api_url)) {
            throw new \InvalidArgumentException("Required parameter 'api_url' is missing");
        }

        if (empty($params->custom->transfere_account_no)) {
            throw new \InvalidArgumentException("Required parameter 'transfere_account_no' is missing");
        }

        if (empty($params->tenant_identifier)) {
            throw new \InvalidArgumentException("Required parameter 'tenant_identifier' is missing");
        }

        if (empty($params->client_identifier)) {
            throw new \InvalidArgumentException("Required parameter 'client_identifier' is missing");
        }

        if (empty($params->custom->debitor_account_no)) {
            throw new \InvalidArgumentException("Required parameter 'debitor_account_no' is missing");
        }

        $parts = $this->getParts($report);

        $data = [
            'invnumber' => sprintf("%s-%s-%s", $invoiceNumberPrefix, $facility->getId(), $report->getNumber()),
            'ordnumber' => '',
            'status' => 'OPEN',
            'currency' => $report->getFacilityLayout()->getCurrency()->getIsoCode(),
            'ar_accno' => $params->custom->transfere_account_no,
            'transdate' => $report->getStatementDate()->format('Y-m-d'),
            'duedate' => $report->getStatementDate()->format('Y-m-d'),
            'description' => '',
            'notes' => '',
            'intnotes' => '',
            'taxincluded' => 'true',
            'dcn' => '',
            'department' => $params->department ?? '',
            'customernumber' => $params->tenant_identifier,
        ];

        if ($parts) {
            foreach ($parts as $part) {
                $data['parts']['part'][] = $part;
            }
        }

        $path = sprintf('%s/latest/clients/%s/invoices?api_key=%s', $params->api_url, $params->client_identifier, $params->api_key);
        $response = $this->api->request->POST(
            $path,
            json_encode($data)
        );

        $this->handleResponse($path, 'POST', $data, $response, true);
    }

    /**
     * @param string $path
     * @param string $method
     * @param array $data
     * @param Response $response
     * @param bool $triggerToThrow
     */
    private function handleResponse(string $path, string $method, array $data = [], Response $response, bool $triggerToThrow = false)
    {
        $this->dispatcher->dispatch('api.response', new ApiResponseEvent($path, $method, $data, $response));

        if (!in_array($response->getStatus(), [200, 201, 204])) {
            $content = $response->getContent();
            if (isset($content->error)) {
                $message = sprintf("Response status - %s, error - '%s'", $response->getStatus(), $content->error);
                if ($triggerToThrow) {
                    throw new \InvalidArgumentException($message);
                }

                $this->errorMessages[$data['memo']] = $message;
            }
        }
    }

    /**
     * @param Report $report
     * @param array $requestData
     */
    protected function makePayments(Report $report, array $requestData)
    {
        $data = [];
        $facility = $report->getFacilityLayout()->getFacility();
        $params = json_decode($facility->getRoutine()->getParams());
        $invoiceNumberPrefix = $this->params->get('invoiceNumberPrefix');
        $invoiceNumber = sprintf("%s-%s-%s", $invoiceNumberPrefix, $facility->getId(), $report->getNumber());
        /** @var ReportRepository $reportRepository */
        $reportRepository = $this->em->getRepository(Report::class);
        $date = $report->getStatementDate()->format('Y-m-d'). ' 00:00:00';
        $reports = $reportRepository->getReportsByFacilityAndDate($report->getFacilityLayout()->getFacility(), $date, Report::REPORT_TYPE_CASHIER, false);

        $this->makeCreditCardsPayment($report, $reports, $data, $invoiceNumber, $params, $requestData);
        $this->makeAcceptedVouchersPayment($report, $reports, $data, $invoiceNumber, $params, $requestData);
        $this->makeExpensesPayment($report, $reports, $data, $invoiceNumber, $params, $requestData);
        $this->makeCashPayment($report, $reports, $data, $invoiceNumber, $params, $requestData);
        $this->makeOtherPayment($report, $invoiceNumber, $params, $requestData);
    }

    /**
     * @param Report $report
     * @param array $reports
     * @param array $data
     * @param string $invoiceNumber
     * @param $params
     * @param array $requestData
     */
    protected function makeCreditCardsPayment(Report $report, array $reports, array &$data, string $invoiceNumber, $params, array $requestData)
    {
        $this->getCreditCards($report, $reports, $data, $requestData);

        if (isset($data['credit_cards']) && !empty($data['credit_cards'])) {
            foreach ($data['credit_cards'] as $paymentName => $payment) {
                if ($payment['sum'] != 0.00) {
                    $payment = [
                        'datepaid' => $report->getStatementDate()->format('Y-m-d'),
                        'amount_paid' => $payment['sum'],
                        'memo' => $paymentName,
                        'payment_accno' => $payment['accountNo']
                    ];

                    $path = sprintf('%s/latest/clients/%s/invoices/%s/payments?api_key=%s', $params->api_url, $params->client_identifier, $invoiceNumber, $params->api_key);
                    $response = $this->api->request->POST(
                        $path,
                        json_encode($payment)
                    );

                    $this->handleResponse($path, 'POST', $payment, $response);
                }
            }
        }
    }

    /**
     * @param Report $report
     * @param array $reports
     * @param array $data
     * @param string $invoiceNumber
     * @param $params
     * @param array $requestData
     */
    protected function makeAcceptedVouchersPayment(Report $report, array $reports, array &$data, string $invoiceNumber, $params, array $requestData)
    {
        $this->getAcceptedVouchers($report, $reports, $data, $requestData);

        if (isset($data['accepted_vouchers']) && !empty($data['accepted_vouchers'])) {
            foreach ($data['accepted_vouchers'] as $voucher) {
                if ($voucher['amount'] != 0.00) {
                    $payment = [
                        'datepaid' => $report->getStatementDate()->format('Y-m-d'),
                        'amount_paid' => $voucher['amount'],
                        'memo' => sprintf('Voucher No. %s', $voucher['number']),
                        'payment_accno' => $voucher['accountNo']
                    ];

                    $path = sprintf('%s/latest/clients/%s/invoices/%s/payments?api_key=%s', $params->api_url, $params->client_identifier, $invoiceNumber, $params->api_key);
                    $response = $this->api->request->POST(
                        $path,
                        json_encode($payment)
                    );

                    $this->handleResponse($path, 'POST', $payment, $response);
                }
            }
        }
    }

    /**
     * @param Report $report
     * @param array $reports
     * @param array $data
     * @param string $invoiceNumber
     * @param $params
     * @param array $requestData
     */
    protected function makeExpensesPayment(Report $report, array $reports, array &$data, string $invoiceNumber, $params, array $requestData)
    {
        $this->getExpenses($report, $reports, $data, $requestData);

        if (isset($data['expenses']) && !empty($data['expenses'])) {
            foreach ($data['expenses'] as $expense) {
                if ($expense['amount'] != 0.00) {
                    $memo = isset($expense['catalogNumber']) ? sprintf('%s #%s', $expense['name'], $expense['catalogNumber']) : $expense['name'];
                    $payment = [
                        'datepaid' => $report->getStatementDate()->format('Y-m-d'),
                        'amount_paid' => $expense['amount'],
                        'memo' => $memo,
                        'payment_accno' => $expense['accountNo']
                    ];

                    $path = sprintf('%s/latest/clients/%s/invoices/%s/payments?api_key=%s', $params->api_url, $params->client_identifier, $invoiceNumber, $params->api_key);
                    $response = $this->api->request->POST(
                        $path,
                        json_encode($payment)
                    );

                    $this->handleResponse($path, 'POST', $payment, $response);
                }
            }
        }
    }

    /**
     * @param Report $report
     * @param array $reports
     * @param array $data
     * @param string $invoiceNumber
     * @param $params
     * @param array $requestData
     */
    protected function makeCashPayment(Report $report, array $reports, array &$data, string $invoiceNumber, $params, array $requestData)
    {
        $this->getCash($report, $reports, $data, $requestData);

        if (isset($data['cash']) && !empty($data['cash']) && $data['cash']['sum'] != 0.00) {
            $payment = [
                'datepaid' => $report->getStatementDate()->format('Y-m-d'),
                'amount_paid' => $data['cash']['sum'],
                'memo' => $data['cash']['name'],
                'payment_accno' => $data['cash']['accountNo']
            ];

            $path = sprintf('%s/latest/clients/%s/invoices/%s/payments?api_key=%s', $params->api_url, $params->client_identifier, $invoiceNumber, $params->api_key);
            $response = $this->api->request->POST(
                $path,
                json_encode($payment)
            );

            $this->handleResponse($path, 'POST', $payment, $response);
        }
    }

    /**
     * @param Report $report
     * @param string $invoiceNumber
     * @param $params
     * @param array $requestData
     */
    protected function makeOtherPayment(Report $report, string $invoiceNumber, $params, array $requestData)
    {
        if (!empty($requestData['missing_income']) && $requestData['missing_income'] != 0.00) {
            $payment = [
                'datepaid' => $report->getStatementDate()->format('Y-m-d'),
                'amount_paid' => $requestData['missing_income'],
                'memo' => $requestData['debitor'],
                'payment_accno' => $params->custom->debitor_account_no
            ];

            $path = sprintf('%s/latest/clients/%s/invoices/%s/payments?api_key=%s', $params->api_url, $params->client_identifier, $invoiceNumber, $params->api_key);
            $response = $this->api->request->POST(
                $path,
                json_encode($payment)
            );

            $this->handleResponse($path, 'POST', $payment, $response);
        }
    }

    /**
     * @return array
     */
    public function getErrorMessages()
    {
        return $this->errorMessages;
    }

    /**
     * @param Report $report
     * @param array $requestData
     */
    public function save(Report $report, array $requestData)
    {
        $this->createInvoice($report, $requestData);
        $this->makePayments($report, $requestData);
    }

    /**
     * @param Report $report
     * @return array
     */
    abstract public function getParts(Report $report): array;

    /**
     * @param Report $report
     * @param array $reports
     * @param array $data
     * @param array $requestData
     * @return mixed
     */
    abstract public function getCreditCards(Report $report, array $reports = [], array &$data, array $requestData = []);

    /**
     * @param Report $report
     * @param array $reports
     * @param array $data
     * @param array $requestData
     * @return mixed
     */
    abstract public function getAcceptedVouchers(Report $report, array $reports = [], array &$data, array $requestData = []);

    /**
     * @param Report $report
     * @param array $reports
     * @param array $data
     * @param array $requestData
     * @return mixed
     */
    abstract public function getExpenses(Report $report, array $reports = [], array &$data, array $requestData = []);

    /**
     * @param Report $report
     * @param array $reports
     * @param array $data
     * @param array $requestData
     * @return mixed
     */
    abstract public function getCash(Report $report, array $reports = [], array &$data, array $requestData = []);
}
