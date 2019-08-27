<?php

namespace App\Service\Routine\DataCollector;

use App\Repository\FlexParamRepository;
use App\Service\ReportService;
use Doctrine\ORM\EntityManagerInterface;

abstract class RmaAbstractCollector
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var ReportService
     */
    protected $reportSevice;

    /**
     * RmaDataCollector constructor.
     * @param EntityManagerInterface $em
     * @param ReportService $reportService
     */
    public function __construct(EntityManagerInterface $em, ReportService $reportService)
    {
        $this->em = $em;
        $this->reportSevice = $reportService;
    }

    /**
     * @param array $data
     * @param int $id
     * @param FlexParamRepository $flexParamRepository
     * @param $value
     * @return void
     */
    protected function summationCreditCards(array &$data, int $id, FlexParamRepository $flexParamRepository, $value): void
    {
        $findParam = ['view' => 'backoffice', 'accountingPosition' => $id];

        $param = $flexParamRepository->findOneBy(array_merge($findParam, ['key' => 'name']));
        $accountNo = $flexParamRepository->findOneBy(array_merge($findParam, ['key' => 'accountNo']));

        if ($param) {
            if (isset($data['credit_cards'][$param->getValue()]['sum'])) {
                $data['credit_cards'][$param->getValue()]['sum'] += $value;
            } else {
                $data['credit_cards'][$param->getValue()]['sum'] = $value;
            }

            if ($accountNo) {
                $data['credit_cards'][$param->getValue()]['accountNo'] = $accountNo->getValue();
            }
        }
    }

    /**
     * @param array $data
     * @param FlexParamRepository $flexParamRepository
     * @param int $id
     * @param $amount
     * @return void
     */
    protected function transformSales(array &$data, FlexParamRepository $flexParamRepository, int $id, $amount)
    {
        $findParam = ['view' => 'backoffice', 'accountingPosition' => $id];

        $param = $flexParamRepository->findOneBy(array_merge($findParam, ['key' => 'name']));
        $accountNo = $flexParamRepository->findOneBy(array_merge($findParam, ['key' => 'accountNo']));

        if ($param) {
            $data['sales'][$param->getValue()]['amount'] = $amount;

            if ($accountNo) {
                $data['sales'][$param->getValue()]['accountNo'] = $accountNo->getValue();
            }
        }
    }
}
