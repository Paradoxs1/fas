<?php

namespace App\Service\Routine\DataCollector;

use App\Entity\FlexParam;
use App\Entity\Report;
use App\Entity\ReportPosition;
use App\Service\Report\CategoryReportPositionHandlerComposite;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;


class RmaDataCollectorDecorator implements RmaDataCollectorDecoratorIntrface
{
    /**
     * @var RmaDataCollector
     */
    private $rmaDataCollector;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * RmaDataCollectorDecorator constructor.
     * @param RmaDataCollector $rmaDataCollector
     * @param EntityManagerInterface $em
     * @param TranslatorInterface $translator
     */
    public function __construct(RmaDataCollector $rmaDataCollector, EntityManagerInterface $em, TranslatorInterface $translator)
    {
        $this->rmaDataCollector = $rmaDataCollector;
        $this->em = $em;
        $this->translator = $translator;
    }

    /**
     * @param Report $report
     * @param array $reports
     * @param array $data
     * @param array $parts
     */
    public function getSales(Report $report, array $reports, array &$data, array &$parts): void
    {
        $this->rmaDataCollector->getSales($report, $reports, $data);
        if (isset($data['sales'])) {
            foreach ($data['sales'] as $saleName => $saleData) {
                $parts[] = [
                    'partnumber' => $saleData['accountNo'],
                    'quantity' => '1.0',
                    'sellprice' => $saleData['amount'],
                    'description' => $saleName,
                ];
            }
        }
    }

    /**
     * @param array $reports
     * @param array $data
     * @param array $parts
     */
    public function getIssuedVouchers(array $reports, array &$data, array &$parts): void
    {
        $this->rmaDataCollector->getIssuedVouchers($reports,$data);
        if (isset($data['issued_vouchers'])) {
            foreach ($data['issued_vouchers'] as $voucherData) {
                $parts[] = [
                    'partnumber' => $voucherData['accountNo'],
                    'quantity' => '1.0',
                    'sellprice' => $voucherData['amount'],
                    'description' => sprintf($this->translator->trans('report_api.voucher_no') . " `%s`", $voucherData['number']),
                ];
            }
        }
    }

    /**
     * @param array $reports
     * @param array $data
     * @param array $parts
     */
    public function getBills(array $reports, array &$data, array &$parts): void
    {
        $this->rmaDataCollector->getBills($reports, $data);
        if (isset($data['bills'])) {
            foreach ($data['bills'] as $id => $billData) {
                $parts[] = [
                    'partnumber' => $billData['accountNo'],
                    'quantity' => '1.0',
                    'sellprice' => $billData['amount'] * -1,
                    'description' => sprintf($this->translator->trans('report_api.bill') . " `%s`", $billData['receiver']),
                ];

                if ($billData['tip']) {
                    $parts[] = [
                        'partnumber' => $billData['tipAccountNo'],
                        'quantity' => '1.0',
                        'sellprice' => $billData['tip'] * -1,
                        'description' => sprintf($this->translator->trans('report_api.bill_tip') . " `%s`", $billData['receiver']),
                    ];
                }
            }
        }
    }

    /**
     * @param array $reports
     * @param array $data
     * @param array $requestData
     */
    public function getExpenses(array $reports, array &$data, array $requestData): void
    {
        $this->rmaDataCollector->getExpenses($reports, $data);
        if (isset($data['expenses'])) {
            $reportPositionRepository = $this->em->getRepository(ReportPosition::class);
            $flexParamRepository = $this->em->getRepository(FlexParam::class);
            foreach ($data['expenses'] as $reportPositionId => $expense) {
                /** @var ReportPosition $reportPosition */
                $reportPosition = $reportPositionRepository->find($reportPositionId);
                if ($reportPosition) {
                    $accountingPosition = $reportPosition->getAccountingPosition();
                    $name = $flexParamRepository->findOneByAccountingPositionAndType($accountingPosition, 'name', 'backoffice');
                    if ($name) {
                        $data['expenses'][$reportPositionId]['name'] = $name->getValue();
                    }
                    $data['expenses'][$reportPositionId]['catalogNumber'] = CategoryReportPositionHandlerComposite::formatAmount($requestData['catalog-name'][$reportPositionId]);
                }
            }
        }
    }
}
