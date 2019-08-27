<?php

namespace App\Service;

use App\Entity\Account;
use App\Entity\AccountingCategory;
use App\Entity\AccountingPosition;
use App\Entity\FlexParam;
use App\Entity\Report;
use App\Entity\Facility;
use App\Entity\FacilityLayout;
use App\Entity\ReportPosition;
use App\Entity\ReportPositionValue;
use App\Entity\Role;
use App\Service\Report\CategoryReportPositionHandlerComposite;
use App\Service\Routine\RoutineRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class ReportService
 * @package App\Service
 */
class ReportService
{

    /**
     * @var ObjectManager
     */
    private $manager;

    /**
     * @var ParameterBagInterface
     */
    private $params;

    /**
     * @var AccountingPositionService
     */
    private $accountingPositionService;

    /**
     * @var FlexParamService
     */
    private $flexParamService;

    /**
     * @var ReportPositionService
     */
    private $reportPositionService;

    /**
     * @var ReportPositionValueService
     */
    private $reportPositionValueService;

    /**
     * @var FacilityService
     */
    private $facilityService;

    /**
     * @var MoneyService
     */
    private $moneyService;

    /**
     * @var MoneyService
     */
    private $reportPositionGroupService;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var CategoryReportPositionHandlerComposite
     */
    private $reportPositionHandlerComposite;

    /**
     * @var RoutineRegistry
     */
    private $routineRegistry;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * ReportService constructor.
     * @param ObjectManager $manager
     * @param ParameterBagInterface $params
     * @param AccountingPositionService $accountingPositionService
     * @param FlexParamService $flexParamService
     * @param ReportPositionService $reportPositionService
     * @param ReportPositionValueService $reportPositionValueService
     * @param FacilityService $facilityService
     * @param MoneyService $moneyService
     * @param ReportPositionGroupService $reportPositionGroupService
     * @param TranslatorInterface $translator
     * @param CategoryReportPositionHandlerComposite $reportPositionHandlerComposite
     * @param RoutineRegistry $routineRegistry
     * @param SessionInterface $session
     */
    public function __construct(
        ObjectManager $manager,
        ParameterBagInterface $params,
        AccountingPositionService $accountingPositionService,
        FlexParamService $flexParamService,
        ReportPositionService $reportPositionService,
        ReportPositionValueService $reportPositionValueService,
        FacilityService $facilityService,
        MoneyService $moneyService,
        ReportPositionGroupService $reportPositionGroupService,
        TranslatorInterface $translator,
        CategoryReportPositionHandlerComposite $reportPositionHandlerComposite,
        RoutineRegistry $routineRegistry,
        SessionInterface $session
    ) {
        $this->manager                    = $manager;
        $this->params                     = $params;
        $this->accountingPositionService  = $accountingPositionService;
        $this->flexParamService           = $flexParamService;
        $this->reportPositionService      = $reportPositionService;
        $this->reportPositionValueService = $reportPositionValueService;
        $this->facilityService            = $facilityService;
        $this->moneyService               = $moneyService;
        $this->reportPositionGroupService = $reportPositionGroupService;
        $this->translator                 = $translator;
        $this->reportPositionHandlerComposite = $reportPositionHandlerComposite;
        $this->routineRegistry = $routineRegistry;
        $this->session = $session;
    }

    /**
     * @param string $date
     * @return bool
     */
    public function dateSelectedValid($date = '')
    {
        if (!$date) {
            return false;
        }

        if (preg_match("/^[0-9]{1,2}\.[0-9]{1,2}\.[0-9]{4}$/", $date) !== 0) {
            return true;
        }

        return false;
    }

    /**
     * @param FacilityLayout $facilityLayout
     * @return int
     */
    public function getDaysInPast(FacilityLayout $facilityLayout): int
    {
        $daysInPast = $facilityLayout->getDaysInPast() ?: $this->params->get('cashier_edit_allowed_days');

        --$daysInPast;

        return $daysInPast >= 0 ? $daysInPast : 0;
    }

    /**
     * @param FacilityLayout $facilityLayout
     * @param array $requestData
     * @param Account|null $user
     * @return bool
     * @throws \Exception
     */
    public function dataValid(FacilityLayout $facilityLayout, array $requestData = [], Account $user = null): bool
    {
        if (!$requestData || !isset($requestData['date'])) {
            return false;
        }

        if (
            //Validating Shifts

            (
                isset($requestData['shifts']) &&
                !$this->shiftsValid($facilityLayout, (int) $requestData['shifts'])
            )

            ||

            //Validating Date

            !$this->dateValid($facilityLayout, $requestData['date'], $user)

            //We do not validate Questions answers...

            //We do not validate Comment...

            ||

            //Validate Sales (Cashier)

            (
                isset($requestData['total-sales']) &&
                !$this->salesValid($requestData['total-sales'])
            )

            ||

            //Validate Sales (Backofficer)

            (
                isset($requestData['sales']) &&
                !$this->salesValid($requestData['sales'])
            )

            ||

            //Validate Expenses

            (
                isset($requestData['expenses']) &&
                !$this->expensesValid($requestData['expenses'])
            )

            ||

            //Validate Cigarettes

            (
                isset($requestData['cigarettes']) &&
                !$this->cigarettesValid($requestData['cigarettes'])
            )

            ||

            //Validate Cigarettes

            (
                isset($requestData['credit-cards']) &&
                !$this->creditCardsValid($requestData['credit-cards'])
            )

            ||

            //Validate Bills

            (
                isset($requestData['bills']) &&
                !$this->billsValid($requestData['bills'])
            )

            ||

            //Validate Accepted Vouchers

            (
                isset($requestData['accepted-vouchers']) &&
                !$this->acceptedVouchersValid($requestData['accepted-vouchers'])
            )

            ||

            //Validate Issued Vouchers

            (
                isset($requestData['issued-vouchers']) &&
                !$this->issuedVouchersValid($requestData['issued-vouchers'])
            )

            ||

            //Validate Tips(from Dues)

            (
                isset($requestData['tips']) &&
                !$this->tipsValid($requestData['tips'])
            )

            ||

            //Validate Cash income(from Dues (Backofficer))

            (
                isset($requestData['cash-income']) &&
                !$this->cashValid($requestData['cash-income'])
            )

        ) {
            return false;
        }

        return true;
    }

    /**
     * @param FacilityLayout $facilityLayout
     * @param $date
     * @param Account|null $user
     * @return bool
     * @throws \Exception
     */
    private function dateValid(FacilityLayout $facilityLayout, $date, Account $user = null): bool
    {
        if (isset($user) && $user->hasFacilityRole($facilityLayout->getFacility()->getId(), 'ROLE_TENANT_USER')) {
            return true;
        }

        $daysInPast           = $this->getDaysInPast($facilityLayout);
        $numberOfHoursToShift = $this->params->get('number_of_hours_to_shift');

        $todaysDate = new \DateTime();
        $todaysDate->modify('- ' . $numberOfHoursToShift . ' hour');

        $reportDate  = new \DateTime($date);

        $interval = $todaysDate->diff($reportDate);

        $intervalInSeconds =
            ($interval->format('%d') * 86400) +
            ($interval->format('%h') * 3600) +
             $interval->format('%s');

        if ($intervalInSeconds <= $daysInPast * 86400 + 86400) {
            return true;
        }

        return false;
    }

    /**
     * @param $expenses
     * @return bool
     */
    private function expensesValid($expenses): bool
    {
        if (!$expenses) {
            return true;
        }

        foreach ($expenses as $item) {
            $amount = $this->moneyService->valueToValidNumber($item['amount']);

            if ($amount == 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param $cigarettes
     * @return bool
     */
    private function cigarettesValid($cigarettes): bool
    {
        $cigarettes = $this->moneyService->valueToValidNumber($cigarettes);

        if ($cigarettes < 0) {
            return false;
        }

        return true;
    }

    /**
     * @param $creditCards
     * @return bool
     */
    private function creditCardsValid($creditCards): bool
    {
        if (!$creditCards) {
            return true;
        }

        foreach ($creditCards as $data) {
            foreach ($data as $values) {
                foreach ($values as $value) {

                    if (!$value) {
                        continue;
                    }

                    $value = $this->moneyService->valueToValidNumber($value);

                    if ($value < 0) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * @param $bills
     * @return bool
     */
    private function billsValid($bills): bool
    {
        if (!$bills) {
            return true;
        }

        foreach ($bills as $item) {

            $amount = $item['amount'];
            $tip = $item['tip'];

            if (!$amount || !$tip) {
                continue;
            }

            $amount = $this->moneyService->valueToValidNumber($amount);
            $tip = $this->moneyService->valueToValidNumber($tip);

            if ($amount < 0 || $tip < 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param $acceptedVouchers
     * @return bool
     */
    private function acceptedVouchersValid($acceptedVouchers): bool
    {
        if (!$acceptedVouchers) {
            return true;
        }

        foreach ($acceptedVouchers as $item) {

            $name = $item['number'];
            $amount = $item['amount'];

            if (!$amount || !$name) {
                continue;
            }

            $amount = $this->moneyService->valueToValidNumber($amount);

            if ($amount < 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param $issuedVouchers
     * @return bool
     */
    private function issuedVouchersValid($issuedVouchers): bool
    {
        if (!$issuedVouchers) {
            return true;
        }

        foreach ($issuedVouchers as $item) {

            $name = $item['number'];
            $amount = $item['amount'];

            if (!$amount || !$name) {
                continue;
            }

            $amount = $this->moneyService->valueToValidNumber($amount);
            if ($amount < 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param $totalSales
     * @return bool
     */
    private function salesValid($totalSales): bool
    {
        $totalSales = $this->moneyService->valueToValidNumber($totalSales);

        if ($totalSales > 0) {
            return true;
        }

        return false;
    }

    /**
     * @param $tips
     * @return bool
     */
    private function tipsValid($tips): bool
    {
        if (!$tips) {
            return true;
        }

        foreach ($tips as $tip) {
            $tip = $this->moneyService->valueToValidNumber($tip);

            if ($tip < 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param $cash
     * @return bool
     */
    private function cashValid($cash): bool
    {
        if (!$cash) {
            return true;
        }

        $tip = $this->moneyService->valueToValidNumber($cash);

        return true;
    }

    /**
     * @param FacilityLayout $facilityLayout
     * @param int $shifts
     * @return bool
     */
    private function shiftsValid(FacilityLayout $facilityLayout, int $shifts): bool
    {
        return $shifts <= $facilityLayout->getShifts() ? true : false;
    }

    /**
     * @param Facility $facility
     * @param Account $user
     * @param bool $json
     * @return array
     */
    public function getDisabledDatesForCashier(Facility $facility, Account $user, $json = false)
    {
        $result  = [];
        $reports = $this->manager->getRepository(Report::class)
            ->findAllFacilityReports($facility);

        if (!$reports) {
            return $json ? json_encode($result) : $result;
        }

        foreach ($reports as $report) {
            $reportCreator = $report->getCreatedBy();

            if (
                //If there is a Report from current user
                $reportCreator->getId() == $user->getId()  ||

                //If there is an Approved Report (by Tenant)
                $report->getApproved() ||

                //If there is a Report from Tenant
                $this->facilityService->hasRoleInFacility($reportCreator, $facility, 'ROLE_TENANT_USER')

            ) {
                $result[] = $report->getStatementDate()->format('d.m.Y');
            }
        }

        $result = array_unique($result);
        return $json ? json_encode($result) : $result;
    }

    /**
     * @param Facility $facility
     * @return array
     */
    public function getDisabledDates(Facility $facility): array
    {
        $dates = [];
        $reports = $this->manager->getRepository(Report::class)
            ->findAllFacilityReports($facility);

        /** @var Report $report */
        foreach ($reports as $report) {
            if (!$report->getDeletedAt()) {
                $dates[] = $report->getStatementDate()->format('Y-m-d');
            }
        }

        return array_unique($dates);
    }

    /**
     * @param array $datesDisabled
     * @param string $format
     * @return array
     * @throws \Exception
     */
    public function transformFormatDates(array $datesDisabled, string $format): array
    {
        foreach ($datesDisabled as $key => $date) {
            $datesDisabled[$key] = (new \DateTimeImmutable($date))->format($format);
        }

        return $datesDisabled;
    }

    /**
     * @param array $dates
     * @param string $format
     * @param string $date
     * @param int $daysInPast
     * @param bool|null $cashier
     * @return void
     * @throws \Exception
     */
    public function getFreeReportDate(array $dates, string $format, string &$date, int $daysInPast, bool $cashier = null): void
    {
        if ($dates) {
            $trigger = true;
            $dates = $this->transformFormatDates($dates, $format);
            $date = $this->getCurrentDate();

            if (count($dates) > 1) {
                $dateRange = new \DatePeriod((new \DateTime($date))->modify("-$daysInPast day"), new \DateInterval('P1D'), (new \DateTime($date))->modify("+1 day"));
                foreach ($dateRange as $item) {
                    if (!in_array($item->format($format), $dates)) {
                        $date = $item->format($format);
                        $trigger = false;
                    }
                }

                if ($trigger) {
                    $date = (new \DateTimeImmutable(array_pop($dates)))->modify('-1 day')->format($format);
                }

                if ($trigger && $cashier) {
                    $date = $this->getCurrentDate();
                }
            }

            if (count($dates) === 1 && $dates[0] === $date) {
                $date = (new \DateTimeImmutable($date))->modify('-1 day')->format($format);
            }
        }
    }

    /**
     * @param FacilityLayout $facility
     * @param Account $user
     * @param $statementDate
     * @param null $shifts
     * @param null $number
     * @param int $approved
     * @param string $type
     * @param null $modifiedAt
     * @param null $parentReport
     * @return Report
     */
    public function addReport(
        FacilityLayout $facility,
        Account $user,
        $statementDate,
        $shifts = null,
        $number = null,
        $approved = 0,
        $type = '',
        $modifiedAt = null,
        $parentReport = null
    ): Report {
        $report = new Report();

        $report->setFacilityLayout($facility);
        $report->setCreatedBy($user);
        $report->setStatementDate($statementDate);
        $report->setShifts($shifts);
        $report->setNumber($number);
        $report->setApproved($approved);
        $report->setType($type);

        if ($modifiedAt) {
            $report->setModifiedAt();
        }

        if ($parentReport) {
            $report->setParentReport($parentReport);
        }

        $this->manager->persist($report);
        $this->manager->flush();

        return $report;
    }

    /**
     * @param Account $user
     * @param Report $report
     * @param array $questionsAnswers
     * @return bool
     */
    public function addQuestionAnswers(Account $user, Report $report, array $questionsAnswers = [])
    {
        if (!$questionsAnswers) {
            return false;
        }

        foreach ($questionsAnswers as $accountingPositionId => $answer) {
            $answer = trim($answer);

            if (!$answer) {
                continue;
            }

            $accountingPosition = $this->manager->getRepository(AccountingPosition::class)
                ->find($accountingPositionId);

            if (!$accountingPosition) {
                continue;
            }

            try {
                $flexParam = $this->manager->getRepository(FlexParam::class)
                    ->findOneByAccountingPositionAndType($accountingPosition, 'answer');
            } catch (NonUniqueResultException $e) {
                continue;
            }

            $reportPosition = $this->reportPositionService->addReportPosition(
                $report,
                $accountingPosition,
                $user
            );

            $this->reportPositionValueService->addReportPositionValue(
                $reportPosition,
                $flexParam,
                $answer,
                1
            );
        }

        return true;
    }

    /**
     * @param FacilityLayout $facilityLayout
     * @param Account $user
     * @param Report $report
     * @param string $comment
     * @return bool
     * @throws NonUniqueResultException
     */
    public function addComment(FacilityLayout $facilityLayout, Account $user, Report $report, $comment = ''): bool
    {
        if (!$comment) {
            return false;
        }

        //Flex params related actions

        $accountingCategory = $this->manager->getRepository(AccountingCategory::class)->findOneByKey('comment');

        if (!$accountingCategory) {
            return false;
        }

        $accountingPosition = $this->reportPositionService
            ->findAccountingPositionByCategoryAndLayout($accountingCategory, $facilityLayout);

        if (!$accountingPosition) {
            return false;
        }

        try {
            $flexParam = $this->manager->getRepository(FlexParam::class)
                ->findOneByAccountingPositionAndType($accountingPosition, 'value');
        } catch (NonUniqueResultException $e) {
            return false;
        }

        //Report related actions

        $reportPosition = $this->reportPositionService->addReportPosition(
            $report,
            $accountingPosition,
            $user
        );

        $this->reportPositionValueService->addReportPositionValue(
            $reportPosition,
            $flexParam,
            $comment,
            1
        );

        return true;
    }

    /**
     * @param FacilityLayout $facilityLayout
     * @param Account $user
     * @param Report $report
     * @param int $cigarettes
     * @return bool
     * @throws NonUniqueResultException
     */
    public function addCigarettes(FacilityLayout $facilityLayout, Account $user, Report $report, $cigarettes = 0)
    {
        $cigarettes = $this->moneyService->valueToValidNumber($cigarettes);

        if ($cigarettes < 0) {
            return false;
        }

        //Flex params related actions

        $accountingCategory = $this->manager->getRepository(AccountingCategory::class)->findOneByKey('cigarettes');

        if (!$accountingCategory) {
            return false;
        }

        $accountingPosition = $this->reportPositionService
            ->findAccountingPositionByCategoryAndLayout($accountingCategory, $facilityLayout);

        if (!$accountingPosition) {
            return false;
        }

        try {
            $flexParam = $this->manager->getRepository(FlexParam::class)
                ->findOneByAccountingPositionAndType(
                    $accountingPosition,
                    'amount',
                    'frontoffice'
                );
        } catch (NonUniqueResultException $e) {
            return false;
        }

        //Report related actions

        $reportPosition = $this->reportPositionService->addReportPosition(
            $report,
            $accountingPosition,
            $user
        );

        $this->reportPositionValueService->addReportPositionValue(
            $reportPosition,
            $flexParam,
            $cigarettes,
            1
        );

        return true;
    }

    /**
     * @param FacilityLayout $facilityLayout
     * @param Account $user
     * @param Report $report
     * @param string $comment
     * @return bool
     */
    public function addTotalSales(FacilityLayout $facilityLayout, Account $user, Report $report, $totalSales = 0)
    {
        if (!$totalSales) {
            return false;
        }

        $totalSales = $this->moneyService->valueToValidNumber($totalSales);

        //Flex params related actions

        $accountingCategory = $this->manager->getRepository(AccountingCategory::class)->findOneByKey('totalSales');

        if (!$accountingCategory) {
            return false;
        }

        $accountingPosition = $this->reportPositionService
            ->findAccountingPositionByCategoryAndLayout($accountingCategory, $facilityLayout);

        if (!$accountingPosition) {
            return false;
        }

        try {
            $flexParam = $this->manager->getRepository(FlexParam::class)
                ->findOneByAccountingPositionAndType($accountingPosition, 'value');
        } catch (NonUniqueResultException $e) {
            return false;
        }

        //Report related actions

        $reportPosition = $this->reportPositionService->addReportPosition(
            $report,
            $accountingPosition,
            $user
        );

        $this->reportPositionValueService->addReportPositionValue(
            $reportPosition,
            $flexParam,
            $totalSales,
            1
        );

        return true;
    }

    /**
     * @param Account $user
     * @param Report $report
     * @param array $totalSales
     * @return bool
     */
    private function addSales(Account $user, Report $report, array $totalSales = [])
    {
        if (!$totalSales) {
            return false;
        }

        foreach ($totalSales as $accountingPositionId => $value) {
            $value = $this->moneyService->valueToValidNumber($value);

            $accountingPosition = $this->manager->getRepository(AccountingPosition::class)
                ->find($accountingPositionId);

            if (!$accountingPosition) {
                continue;
            }

            try {
                $flexParam = $this->manager->getRepository(FlexParam::class)
                    ->findOneByAccountingPositionAndType($accountingPosition, 'value');
            } catch (NonUniqueResultException $e) {
                return false;
            }

            //Report related actions

            $reportPosition = $this->reportPositionService->addReportPosition(
                $report,
                $accountingPosition,
                $user
            );

            $this->reportPositionValueService->addReportPositionValue(
                $reportPosition,
                $flexParam,
                $value,
                1
            );
        }

        return true;
    }

    private function addCash(Account $user, Report $report, array $data)
    {
        foreach ($data['cashier'] as $accountingPositionId => $cashierData) {

            $accountingPosition = $this->manager->getRepository(AccountingPosition::class)
                ->find($accountingPositionId);


            foreach ($cashierData as $i => $accountId) {
                $amount = $this->moneyService->valueToValidNumber($data['cash-amount'][$accountingPositionId][$i]);
                $flexParam = $this->manager->getRepository(FlexParam::class)
                    ->findOneByAccountingPositionAndType(
                        $accountingPosition,
                        'cashier',
                        'frontoffice'
                    );

                $reportPosition = $this->reportPositionService->addReportPosition(
                    $report,
                    $accountingPosition,
                    $user
                );

                $this->reportPositionValueService->addReportPositionValue(
                    $reportPosition,
                    $flexParam,
                    $accountId,
                    1
                );

                $flexParam = $this->manager->getRepository(FlexParam::class)
                    ->findOneByAccountingPositionAndType(
                        $accountingPosition,
                        'amount',
                        'frontoffice'
                    );

                $this->reportPositionValueService->addReportPositionValue(
                    $reportPosition,
                    $flexParam,
                    $amount,
                    2
                );
            }
        }

        return true;
    }

    /**
     * @param FacilityLayout $facilityLayout
     * @param Account $user
     * @param Report $report
     * @param array $expenses
     * @return bool
     * @throws NonUniqueResultException
     */
    private function addExpenses(FacilityLayout $facilityLayout, Account $user, Report $report, array $expenses = [])
    {
        if (!$expenses) {
            return false;
        }

        $accountingCategory = $this->manager->getRepository(AccountingCategory::class)->findOneByKey('expenses');

        if (!$accountingCategory) {
            return false;
        }

        $accountingPosition = $this->reportPositionService
            ->findAccountingPositionByCategoryAndLayout($accountingCategory, $facilityLayout);

        if (!$accountingPosition) {
            return false;
        }

        $expensesNameFlexParam = $this->manager->getRepository(FlexParam::class)->findOneByAccountingPositionAndType(
            $accountingPosition,
            'name',
            'frontoffice'
        );

        $expensesAmountFlexParam = $this->manager->getRepository(FlexParam::class)->findOneByAccountingPositionAndType(
            $accountingPosition,
            'amount',
            'frontoffice'
        );

        if (!$expensesNameFlexParam || !$expensesAmountFlexParam) {
            return false;
        }

        foreach ($expenses as $key => $item) {
            $amount = $this->moneyService->valueToValidNumber($item['amount']);
            $name = trim($item['name']);

            $reportPosition = $this->reportPositionService->addReportPosition(
                $report,
                $accountingPosition,
                $user
            );

            $this->reportPositionValueService->addReportPositionValue(
                $reportPosition,
                $expensesNameFlexParam,
                $name,
                1
            );

            $this->reportPositionValueService->addReportPositionValue(
                $reportPosition,
                $expensesAmountFlexParam,
                $amount,
                2
            );
        }

        return true;
    }

    /**
     * @param Account $user
     * @param Report $report
     * @param array $creditCards
     * @return bool
     * @throws NonUniqueResultException
     */
    public function addCreditCards(Account $user, Report $report, array $creditCards = [])
    {
        if (!$creditCards) {
            return false;
        }

        foreach ($creditCards as $terminalId => $data) {

            $terminalName = $this->translator->trans('report.terminal');
            $reportPositionGroup = $this->reportPositionGroupService->addReportPositionGroup($terminalName);

            foreach ($data as $accountingPositionId => $values) {

                $accountingPosition = $this->manager->getRepository(AccountingPosition::class)->find($accountingPositionId);

                if (!$accountingPosition) {
                    continue;
                }

                $creditCardValueFlexParam = $this->manager->getRepository(FlexParam::class)->findOneByAccountingPositionAndType(
                    $accountingPosition,
                    'value',
                    'frontoffice'
                );

                if (!$creditCardValueFlexParam) {
                    continue;
                }

                $sequence = 0;

                foreach ($values as $value) {
                    ++$sequence;

                    $amount = $this->moneyService->valueToValidNumber($value);

                    $reportPosition = $this->reportPositionService->addReportPosition(
                        $report,
                        $accountingPosition,
                        $user
                    );

                    $this->reportPositionValueService->addReportPositionValue(
                        $reportPosition,
                        $creditCardValueFlexParam,
                        $amount,
                        $sequence,
                        $reportPositionGroup
                    );
                }
            }
        }

        return true;
    }

    /**
     * @param Account $user
     * @param Report $report
     * @param array $bills
     * @return bool
     * @throws NonUniqueResultException
     */
    private function addBills(Account $user, Report $report, array $bills = [])
    {
        if (!$bills) {
            return false;
        }

        foreach ($bills as $bill) {
            if (!isset($bill['name'])) {
                continue;
            }

            $accountingPosition = $this->manager->getRepository(AccountingPosition::class)->find($bill['name']);

            if (!$accountingPosition) {
                continue;
            }

            $billReceiverFlexParam = $this->manager->getRepository(FlexParam::class)->findOneByAccountingPositionAndType(
                $accountingPosition,
                'receiver',
                'frontoffice'
            );

            $billAmountFlexParam = $this->manager->getRepository(FlexParam::class)->findOneByAccountingPositionAndType(
                $accountingPosition,
                'amount',
                'frontoffice'
            );

            $billTipFlexParam = $this->manager->getRepository(FlexParam::class)->findOneByAccountingPositionAndType(
                $accountingPosition,
                'tip',
                'frontoffice'
            );

            if (!$billReceiverFlexParam || !$billAmountFlexParam || !$billTipFlexParam) {
                continue;
            }

            $amount   = $this->moneyService->valueToValidNumber($bill['amount']);
            $tip      = $this->moneyService->valueToValidNumber($bill['tip']);
            $receiver = trim($bill['receiver']);

            $reportPosition = $this->reportPositionService->addReportPosition(
                $report,
                $accountingPosition,
                $user
            );

            $this->reportPositionValueService->addReportPositionValue(
                $reportPosition,
                $billAmountFlexParam,
                $amount,
                2
            );

            $this->reportPositionValueService->addReportPositionValue(
                $reportPosition,
                $billTipFlexParam,
                $tip,
                3
            );

            $this->reportPositionValueService->addReportPositionValue(
                $reportPosition,
                $billReceiverFlexParam,
                $receiver,
                1
            );
        }

        return true;
    }

    /**
     * @param FacilityLayout $facilityLayout
     * @param Account $user
     * @param Report $report
     * @param array $acceptedVouchers
     * @return bool
     * @throws NonUniqueResultException
     */
    private function addAcceptedVouchers(FacilityLayout $facilityLayout, Account $user, Report $report, array $acceptedVouchers = [])
    {
        if (!$acceptedVouchers) {
            return false;
        }

        $acceptedVoucherCategory = $this->manager->getRepository(AccountingCategory::class)->findOneByKey('acceptedVoucher');

        if (!$acceptedVoucherCategory) {
            return false;
        }

        $accountingPosition = $this->reportPositionService
            ->findAccountingPositionByCategoryAndLayout($acceptedVoucherCategory, $facilityLayout);

        if (!$accountingPosition) {
            return false;
        }

        $acceptedVoucherNumberFlexParam = $this->manager->getRepository(FlexParam::class)->findOneByAccountingPositionAndType(
            $accountingPosition,
            'number',
            'frontoffice'
        );

        $acceptedVoucherAmountFlexParam = $this->manager->getRepository(FlexParam::class)->findOneByAccountingPositionAndType(
            $accountingPosition,
            'amount',
            'frontoffice'
        );

        if (!$acceptedVoucherNumberFlexParam || !$acceptedVoucherAmountFlexParam) {
            return false;
        }

        foreach ($acceptedVouchers as $key => $item) {
            $amount = $this->moneyService->valueToValidNumber($item['amount']);
            $name = trim($item['number']);

            $reportPosition = $this->reportPositionService->addReportPosition(
                $report,
                $accountingPosition,
                $user
            );

            $this->reportPositionValueService->addReportPositionValue(
                $reportPosition,
                $acceptedVoucherNumberFlexParam,
                $name,
                1
            );

            $this->reportPositionValueService->addReportPositionValue(
                $reportPosition,
                $acceptedVoucherAmountFlexParam,
                $amount,
                2
            );
        }

        return true;
    }

    /**
     * @param FacilityLayout $facilityLayout
     * @param Account $user
     * @param Report $report
     * @param array $acceptedVouchers
     * @return bool
     * @throws NonUniqueResultException
     */
    private function addIssuedVouchers(FacilityLayout $facilityLayout, Account $user, Report $report, array $issuedVouchers = [])
    {
        if (!$issuedVouchers) {
            return false;
        }

        $issuedVoucherCategory = $this->manager->getRepository(AccountingCategory::class)->findOneByKey('issuedVoucher');

        if (!$issuedVoucherCategory) {
            return false;
        }

        $accountingPosition = $this->reportPositionService
            ->findAccountingPositionByCategoryAndLayout($issuedVoucherCategory, $facilityLayout);

        if (!$accountingPosition) {
            return false;
        }

        $acceptedVoucherNumberFlexParam = $this->manager->getRepository(FlexParam::class)->findOneByAccountingPositionAndType(
            $accountingPosition,
            'number',
            'frontoffice'
        );

        $acceptedVoucherAmountFlexParam = $this->manager->getRepository(FlexParam::class)->findOneByAccountingPositionAndType(
            $accountingPosition,
            'amount',
            'frontoffice'
        );

        if (!$acceptedVoucherNumberFlexParam || !$acceptedVoucherAmountFlexParam) {
            return false;
        }

        foreach ($issuedVouchers as $key => $item) {
            $amount = $this->moneyService->valueToValidNumber($item['amount']);
            $name = trim($item['number']);

            $reportPosition = $this->reportPositionService->addReportPosition(
                $report,
                $accountingPosition,
                $user
            );

            $this->reportPositionValueService->addReportPositionValue(
                $reportPosition,
                $acceptedVoucherNumberFlexParam,
                $name,
                1
            );

            $this->reportPositionValueService->addReportPositionValue(
                $reportPosition,
                $acceptedVoucherAmountFlexParam,
                $amount,
                2
            );
        }

        return true;
    }

    /**
     * @param Account $user
     * @param Report $report
     * @param array $tips
     * @return bool
     */
    public function addTips(Account $user, Report $report, array $tips = [])
    {
        if (!$tips) {
            return false;
        }

        foreach ($tips as $accountingPositionId => $tip) {
            $tip = $this->moneyService->valueToValidNumber($tip);

            $accountingPosition = $this->manager->getRepository(AccountingPosition::class)
                ->find($accountingPositionId);

            if (!$accountingPosition) {
                continue;
            }

            try {
                $flexParam = $this->manager->getRepository(FlexParam::class)
                    ->findOneByAccountingPositionAndType(
                        $accountingPosition,
                        'value',
                        'frontoffice'
                    );
            } catch (NonUniqueResultException $e) {
                continue;
            }

            $reportPosition = $this->reportPositionService->addReportPosition(
                $report,
                $accountingPosition,
                $user
            );

            $this->reportPositionValueService->addReportPositionValue(
                $reportPosition,
                $flexParam,
                $tip,
                1
            );
        }

        return true;
    }

    /**
     * @param FacilityLayout $facilityLayout
     * @param Account $user
     * @param Report $report
     * @param int $cash
     * @return bool
     * @throws NonUniqueResultException
     */
    private function addCashIncome(FacilityLayout $facilityLayout, Account $user, Report $report, $cash = 0)
    {
        if (!$cash) {
            return false;
        }

        //Flex params related actions

        $cashCategory = $this->manager->getRepository(AccountingCategory::class)->findOneByKey('cash');

        if (!$cashCategory) {
            return false;
        }

        $accountingPosition = $this->reportPositionService
            ->findAccountingPositionByCategoryAndLayout($cashCategory, $facilityLayout);

        if (!$accountingPosition) {
            return false;
        }

        try {
            $flexParam = $this->manager->getRepository(FlexParam::class)
                ->findOneByAccountingPositionAndType(
                    $accountingPosition,
                    'amount',
                    'frontoffice'
                );
        } catch (NonUniqueResultException $e) {
            return false;
        }

        //Report related actions

        $reportPosition = $this->reportPositionService->addReportPosition(
            $report,
            $accountingPosition,
            $user
        );

        $this->reportPositionValueService->addReportPositionValue(
            $reportPosition,
            $flexParam,
            $this->moneyService->valueToValidNumber($cash),
            1
        );

        return true;
    }

    /**
     * @param FacilityLayout $facilityLayout
     * @param Account $user
     * @param Report $report
     * @param int $cash
     * @return bool
     */
    private function addTotalDues(FacilityLayout $facilityLayout, Account $user, Report $report, $cash = 0)
    {
        if (!$cash) {
            return false;
        }

        $cashCategory = $this->manager->getRepository(AccountingCategory::class)->findOneByKey('cash');

        if (!$cashCategory) {
            return false;
        }

        $accountingPosition = $this->reportPositionService
            ->findAccountingPositionByCategoryAndLayout($cashCategory, $facilityLayout);

        if (!$accountingPosition) {
            return false;
        }

        try {
            $flexParam = $this->manager->getRepository(FlexParam::class)
                ->findOneByAccountingPositionAndType(
                    $accountingPosition,
                    'amount',
                    'frontoffice'
                );
        } catch (NonUniqueResultException $e) {
            return false;
        }

        //Report related actions

        $reportPosition = $this->reportPositionService->addReportPosition(
            $report,
            $accountingPosition,
            $user
        );

        $this->reportPositionValueService->addReportPositionValue(
            $reportPosition,
            $flexParam,
            CategoryReportPositionHandlerComposite::formatAmount($cash),
            1
        );

        return true;
    }

    /**
     * @param FacilityLayout $facilityLayout
     * @param Account $user
     * @param array $requestData
     * @param bool $approved
     * @param int|null $number
     * @return Report|null
     * @throws \Exception
     */
    public function saveReport(FacilityLayout $facilityLayout, Account $user, array $requestData = [], bool $approved = false, ?int $number = null): ?Report
    {
        if (!$requestData) {
            return null;
        }

        if (
            $this->facilityService->hasRoleInFacility($user, $facilityLayout->getFacility(), 'ROLE_FACILITY_MANAGER') ||
            $this->facilityService->hasRoleInFacility($user, $facilityLayout->getFacility(), 'ROLE_FACILITY_USER')
        ) {
            $type = Report::REPORT_TYPE_CASHIER;
        } elseif ($this->facilityService->hasRoleInFacility($user, $facilityLayout->getFacility(), 'ROLE_TENANT_USER')) {
            $type = Report::REPORT_TYPE_BACKOFFICER;
        }

        $report = $this->addReport(
            $facilityLayout,
            $user,
            new \DateTime($requestData['date']),
            isset($requestData['shifts']) ? $requestData['shifts'] : null,
            $number,
            $approved,
            $type
        );

        $this->saveReportParams($facilityLayout, $user, $requestData, $report);

        return $report;
    }

    public function saveReportParams(FacilityLayout $facilityLayout, Account $user, array $requestData = [], Report $report)
    {
        //Saving Question answers

        if (isset($requestData['question-answer'])) {
            $this->addQuestionAnswers($user, $report, $requestData['question-answer']);
        }

        //Saving Comment

        if (isset($requestData['comment'])) {
            $this->addComment($facilityLayout, $user, $report, $requestData['comment']);
        }

        //Saving Total Sales (Cashier)

        if (isset($requestData['total-sales'])) {
            $this->addTotalSales($facilityLayout, $user, $report, $requestData['total-sales']);
        }

        //Saving Sales (Backofficer)

        if (isset($requestData['sales'])) {
            $this->addSales($user, $report, $requestData['sales']);
        }

        //Saving Expenses

        if (isset($requestData['expenses'])) {
            $this->addExpenses($facilityLayout, $user, $report, $requestData['expenses']);
        }

        //Saving Cigarettes

        if (isset($requestData['cigarettes'])) {
            $this->addCigarettes($facilityLayout, $user, $report, $requestData['cigarettes']);
        }

        //Saving Credit Cards

        if (isset($requestData['credit-cards'])) {
            $this->addCreditCards($user, $report, $requestData['credit-cards']);
        }

        //Saving Credit Cards

        if (isset($requestData['bills'])) {
            $this->addBills($user, $report, $requestData['bills']);
        }

        if (isset($requestData['cashier']) && isset($requestData['cash-amount'])) {
            $this->addCash($user, $report, $requestData);
        }

        //Saving Accepted Vouchers

        if (isset($requestData['accepted-vouchers'])) {
            $this->addAcceptedVouchers($facilityLayout, $user, $report, $requestData['accepted-vouchers']);
        }

        //Saving Issued Vouchers

        if (isset($requestData['issued-vouchers'])) {
            $this->addIssuedVouchers($facilityLayout, $user, $report, $requestData['issued-vouchers']);
        }

        //Saving Tip (Dues)
        if (isset($requestData['tips'])) {
            $this->addTips($user, $report, $requestData['tips']);
        }

        //Saving Cash Income (Dues (Backofficer))
        if (isset($requestData['cash-income'])) {
            $this->addCashIncome($facilityLayout, $user, $report, $requestData['cash-income']);
        }

        return $report;
    }

    /**
     * @param Facility $facility
     * @param Account $user
     * @param $date
     * @param array $datesDisabled
     * @return Report|null
     * @throws NonUniqueResultException
     */
    public function getReportByFacilityUserStatementDate(Facility $facility, Account $user, $date, $datesDisabled = []): ?Report
    {
        $format = 'Y-m-d';
        $date = (new \DateTimeImmutable($date))->format($format);
        $datesDisabled = $this->transformFormatDates($datesDisabled, $format);

        if (in_array($date, $datesDisabled)) {
            return $this->manager->getRepository(Report::class)
                ->findOneByFacilityUserStatementDate($facility, $user, $date);
        }

        return null;
    }

    /**
     * @param Facility $facility
     * @param $date
     * @param array $datesDisabled
     * @return Report|null
     * @throws NonUniqueResultException
     */
    public function getReportByFacilityStatementDate(Facility $facility, $date, $datesDisabled = [])
    {
        $date = new \DateTimeImmutable($date);
        $date = $date->format('Y-m-d'). ' 00:00:00';

        if (in_array($date, $datesDisabled)) {
            return null;
        }

        $report = $this->manager->getRepository(Report::class)
            ->findOneByFacilityStatementDate($facility, $date);

        return $report;
    }

    /**
     * @param FacilityLayout $facilityLayout
     * @param Report $report
     * @return ReportPositionValue|null
     * @throws NonUniqueResultException
     */
    public function getComment(FacilityLayout $facilityLayout, Report $report) : ?ReportPositionValue
    {
        $accountingCategory = $this->manager->getRepository(AccountingCategory::class)->findOneByKey('comment');

        if (!$accountingCategory) {
            return null;
        }

        $commentFlexParam = $this->manager->getRepository(FlexParam::class)->findByCategoryAndFacilityLayout(
            $facilityLayout,
            $accountingCategory,
            'frontoffice'
        );

        if (!$commentFlexParam) {
            return null;
        }

        $comment = $this->reportPositionValueService->getReportPositionValueByAccountingPositionAndReport(
            reset($commentFlexParam)->getAccountingPosition(),
            $report
        );

        return $comment ?: null ;
    }

    /**
     * @param FacilityLayout $facilityLayout
     * @param Report $report
     * @return ReportPositionValue|null
     * @throws NonUniqueResultException
     */
    public function getTotalSales(FacilityLayout $facilityLayout, Report $report) : ?ReportPositionValue
    {
        $accountingCategory = $this->manager->getRepository(AccountingCategory::class)->findOneByKey('totalSales');

        if (!$accountingCategory) {
            return null;
        }

        $totalSalesFlexParam = $this->manager->getRepository(FlexParam::class)->findByCategoryAndFacilityLayout(
            $facilityLayout,
            $accountingCategory,
            'frontoffice'
        );

        if (!$totalSalesFlexParam) {
            return null;
        }

        $totalSales = $this->reportPositionValueService->getReportPositionValueByAccountingPositionAndReport(
            reset($totalSalesFlexParam)->getAccountingPosition(),
            $report
        );

        if (!$totalSales) {
            return null;
        }

        return $totalSales;
    }

    /**
     * @param string $format
     * @return string
     * @throws \Exception
     */
    public function getCurrentDate($format = 'd.m.Y')
    {
        return (new \DateTimeImmutable())->format($format);
    }

    /**
     * @param Facility $facility
     * @param Report|null $report
     * @return FacilityLayout|mixed|null
     */
    public function getFacilityLayoutForReport(Facility $facility, ?Report $report = null)
    {
        //Used Or Last(Actual) configuration

        return $report ? $report->getFacilityLayout() : $facility->getFacilityLayouts()->last();
    }

    /**
     * @param FacilityLayout $facilityLayout
     * @param array $datesToDisabale
     * @return null|string
     * @throws \Exception
     */
    public function getAvailableDateToRedirect(FacilityLayout $facilityLayout, $datesToDisabale = [])
    {
        $daysInPast = $this->getDaysInPast($facilityLayout);

        $todaysDate = new \DateTime();
        $todaysDate->modify('- ' . $this->params->get('number_of_hours_to_shift') . ' hour');

        for ($i = $daysInPast; $i > 0; $i--) {
            $todaysDate->modify('- 1 day');

            $date = $todaysDate->format('d.m.Y');

            if (!in_array($date, $datesToDisabale)) {
                return $date;
            }
        }

        return null;
    }

    /**
     * @param FacilityLayout $facilityLayout
     * @param Report|null $report
     * @param bool $getReportPositions
     * @return array
     */
    public function getSales(FacilityLayout $facilityLayout, Report $report = null, bool $getReportPositions = false)
    {
        $result = [];

        $salesCategory = $this->manager->getRepository(AccountingCategory::class)->findOneBy(
            [
                'key' => $this->flexParamService::ACCOUNTING_CATEGORY_SALES_CATEGORY_KEY
            ]
        );

        if (!$salesCategory) {
            return $result;
        }

        $sales = $this->manager->getRepository(FlexParam::class)->findByCategoryAndFacilityLayout(
            $facilityLayout,
            $salesCategory,
            'backoffice'
        );

        if (!$sales) {
            return $result;
        }

        foreach ($sales as $item) {
            $accountingPosition = $item->getAccountingPosition();
            $accountingPositionId = $accountingPosition->getId();
            $key = $item->getKey();

            if ('name' == $key) {
                if (!$getReportPositions) {
                    $result[$accountingPositionId]['name'] = $item->getValue();
                }

                if ($report) {
                    $reportPositionValue = $this->reportPositionValueService
                        ->getReportPositionValueByAccountingPositionAndReport($accountingPosition, $report);

                    if ($reportPositionValue && $getReportPositions) {
                        $result[$accountingPositionId] = $reportPositionValue;
                    }

                    if ($reportPositionValue && !$getReportPositions) {
                        $result[$accountingPositionId]['value'] = sprintf("%.2f", $reportPositionValue->getValue());
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param FacilityLayout $facilityLayout
     * @param array $reports
     * @return array
     */
    public function getQuestionsAnswers(FacilityLayout $facilityLayout, array $reports = [])
    {
        $result = [];

        $questionsCategory = $this->manager->getRepository(AccountingCategory::class)->findOneBy(
            [
                'key' => FlexParamService::ACCOUNTING_CATEGORY_QUESTION_KEY
            ]
        );

        if (!$questionsCategory) {
            return $result;
        }

        $data = $this->manager->getRepository(FlexParam::class)->findByCategoryAndFacilityLayout(
            $facilityLayout,
            $questionsCategory,
            'backoffice'
        );

        if (!$data) {
            return $result;
        }

        if ($reports) {
            /** @var Report $report */
            foreach ($reports as $report) {
                $account = $report->getCreatedBy();

                if (!in_array(Role::ROLE_FACILITY_MANAGER, $account->getRoles())) {
                    continue;
                }

                $arr = [
                    'account' => $account
                ];

                foreach ($data as $item) {
                    $accountingPosition = $item->getAccountingPosition();
                    $key = $item->getKey();

                    if ('questionName' == $key) {
                        $arr['questionName'] = $item->getValue();
                        $reportPositionValue = $this->reportPositionValueService
                            ->getReportPositionValueByAccountingPositionAndReport($accountingPosition, $report);

                        if ($reportPositionValue) {
                            if ($reportPositionValue->getParentReportPositionValue()) {
                                $old_answer = $reportPositionValue->getParentReportPositionValue()->getValue();
                            }

                            $arr['values'][$reportPositionValue->getId()] = [
                                'questionName' => $item->getValue(),
                                'answer' => $reportPositionValue->getValue(),
                            ];

                            if (isset($old_answer)) {
                                $arr['values'][$reportPositionValue->getId()]['old_answer'] = $old_answer;
                            }

                            $parentValue = $reportPositionValue->getParentReportPositionValue();

                            if (!$parentValue && $reportPositionValue->getModifiedBy() && !$reportPositionValue->getDeletedAt()) {
                                $arr['values'][$reportPositionValue->getId()]['new'] = true;
                            }

                            $old_answer = null;
                        }
                    }
                }
                $result[] = $arr;
            }
        }

        return $result;
    }

    /**
     * @param FacilityLayout $facilityLayout
     * @return array
     */
    public function getCashFields(FacilityLayout $facilityLayout)
    {
        $result = [];

        $cashCategory = $this->manager->getRepository(AccountingCategory::class)->findOneBy(
            [
                'key' => FlexParamService::ACCOUNTING_CATEGORY_CASH_KEY
            ]
        );

        if (!$cashCategory) {
            return $result;
        }

        return $this->manager->getRepository(FlexParam::class)->findByCategoryAndFacilityLayout(
            $facilityLayout,
            $cashCategory,
            'frontoffice'
        );
    }

    /**
     * @param FacilityLayout $facilityLayout
     * @param Report $report
     * @return array|null
     */
    public function getCashData(FacilityLayout $facilityLayout, Report $report)
    {
        $result = [];

        $cashFields = $this->getCashFields($facilityLayout);
        $reportPositions = $this->manager->getRepository(ReportPosition::class)->findBy([
            'accountingPosition' => $cashFields[0]->getAccountingPosition(),
            'report' => $report
        ]);

        if (!$reportPositions) {
            return null;
        }

        foreach ($reportPositions as $reportPosition) {
            $reportPositionValues = $this->manager->getRepository(ReportPositionValue::class)->findBy(
                ['reportPosition' => $reportPosition, 'deletedAt' => null],
                ['sequence' => 'ASC']
            );

            if ($reportPositionValues) {
                $result[] = [
                    'account' => $this->manager->getRepository(Account::class)->find($reportPositionValues[0]->getValue()),
                    'amount' => $reportPositionValues[1]->getValue(),
                ];
            }
        }

        return $result;
    }

    /**
     * @param Report|null $report
     * @param array $sales
     * @return string
     */
    public function getSalesSum(?Report $report, array $sales = []) :string
    {
        $result = 0.00;

        if (!$report || !$sales) {
            return $result;
        }

        foreach ($sales as $item) {
            if (!isset($item['value'])) {
                continue;
            }

            $result += $item['value'];
        }

        $result = sprintf("%.2f", $result);

        return $result;
    }

    /**
     * @param Report|null $report
     * @param array $expenses
     * @return string
     */
    public function getExpensesSum(?Report $report, array $expenses = []) :string
    {
        $result = 0.00;

        if (!$report || !isset($expenses['data'])) {
            return $result;
        }

        foreach ($expenses['data'] as $item) {
            if (!isset($item['amount'])) {
                continue;
            }

            $result += $item['amount'];
        }

        $result = sprintf("%.2f", $result);

        return $result;
    }

    /**
     * @param Report|null $report
     * @param array $bills
     * @return array
     */
    public function getBillsSum(?Report $report, array $bills = []) :array
    {
        $result = [
            'amount' => 0.00,
            'tip' => 0.00
        ];

        if (!$bills || !isset($bills['data']) || !$report) {
            return $result;
        }

        foreach ($bills['data'] as $bill) {
            if (!isset($bill['amount']) || !isset($bill['tip'])) {
                continue;
            }

            $result['amount'] += $bill['amount'] ;
            $result['tip']    += $bill['tip'];
        }

        $result['amount'] = sprintf("%.2f", $result['amount']);
        $result['tip'] = sprintf("%.2f", $result['tip']);

        return $result;
    }

    /**
     * @param Report|null $report
     * @param array $vouchers
     * @return string
     */
    public function getVouchersSum(?Report $report, array $vouchers = []) :string
    {
        $result = 0.00;

        if (!$vouchers || !isset($vouchers['data']) || !$report) {
            return $result;
        }

        foreach ($vouchers['data'] as $voucher) {
            if (!isset($voucher['amount'])) {
                continue;
            }

            $result += $voucher['amount'] ;
        }

        $result = sprintf("%.2f", $result);

        return $result;
    }

    /**
     * @param FacilityLayout $facilityLayout
     * @param Report|null $report
     * @return array
     */
    public function getExpenses(FacilityLayout $facilityLayout, Report $report = null)
    {
        $result = [];

        $expensesCategory = $this->manager->getRepository(AccountingCategory::class)->findOneBy(
            [
                'key' => $this->flexParamService::ACCOUNTING_CATEGORY_EXPENSES_KEY
            ]
        );

        if (!$expensesCategory) {
            return $result;
        }

        $expensesFlexParam = $this->manager->getRepository(FlexParam::class)->findByCategoryAndFacilityLayout(
            $facilityLayout,
            $expensesCategory,
           'frontoffice'
        );

        if (!$expensesFlexParam) {
            return $result;
        }

        foreach ($expensesFlexParam as $flexParam) {
            $key = $flexParam->getKey();

            if ($key != 'catalogNumber') {
                $result['settings'][$key] = $key;
            }


            if ($report && $key != 'catalogNumber') {
                $reportPositionValues = $this->reportPositionValueService->getReportPositionValueByFlexParam($flexParam);

                if ($reportPositionValues) {
                    foreach ($reportPositionValues as $reportPositionValue) {
                        $reportPosition = $reportPositionValue->getReportPosition();

                        if ($reportPosition->getReport()->getId() != $report->getId()) {
                            continue;
                        }

                        $value = $reportPositionValue->getValue();
                        $value = ('name' != $key) ? sprintf("%.2f", $value) : $value;
                        $result['data'][$reportPosition->getId()][$key] = $value;
                    }
                }
            }
        }

        if (isset($result['data'])) {
            $result['data'] = array_reverse($result['data'], true);
        }

        $result['sum'] = $this->getExpensesSum($report, $result);

        return $result;
    }

    /**
     * @param FacilityLayout $facilityLayout
     * @param Report|null $report
     * @return array
     */
    public function getCigarettes(FacilityLayout $facilityLayout, Report $report = null)
    {
        $result = [];

        $cigarettesCategory = $this->manager->getRepository(AccountingCategory::class)->findOneBy(
            [
                'key' => $this->flexParamService::ACCOUNTING_CATEGORY_CIGARETTES_KEY
            ]
        );

        if (!$cigarettesCategory) {
            return $result;
        }

        $cigarettesFlexParam = $this->manager->getRepository(FlexParam::class)->findByCategoryAndFacilityLayout(
            $facilityLayout,
            $cigarettesCategory,
            'backoffice'
        );

        if (!$cigarettesFlexParam) {
            return $result;
        }

        $cigarettesFlexParam = reset($cigarettesFlexParam);

        $accountingPosition = $cigarettesFlexParam->getAccountingPosition();

        $result['name'] = $cigarettesFlexParam->getValue();

        if ($report) {
            $reportPositionValue = $this->reportPositionValueService
                ->getReportPositionValueByAccountingPositionAndReport($accountingPosition, $report);

            if ($reportPositionValue) {
                $result['value'] = $reportPositionValue->getValue();
                $result['reportPositionValue'] = $reportPositionValue->getId();
            }
        }

        return $result;
    }

    /**
     * @param FacilityLayout $facilityLayout
     * @param Report|null $report
     * @return array
     * @throws NonUniqueResultException
     */
    public function getCreditCards(FacilityLayout $facilityLayout, Report $report = null)
    {
        $result = [];

        $creditCardCategory = $this->manager->getRepository(AccountingCategory::class)->findOneBy(
            [
                'key' => $this->flexParamService::ACCOUNTING_CATEGORY_CREDIT_CARD_KEY
            ]
        );

        if (!$creditCardCategory) {
            return $result;
        }

        $creditCardFlexParams = $this->manager->getRepository(FlexParam::class)->findByCategoryAndFacilityLayout(
            $facilityLayout,
            $creditCardCategory,
            'backoffice'
        );

        if (!$creditCardFlexParams) {
            return $result;
        }

        foreach ($creditCardFlexParams as $flexParam) {
            $key = $flexParam->getKey();

            $accountingPosition = $flexParam->getAccountingPosition();
            $accountingPositionId = $accountingPosition->getId();

            if ('name' == $key) {
                if ($report) {
                    $values = $this->manager->getRepository(ReportPositionValue::class)
                        ->findReportPositionValuesByAccountingPosition($accountingPosition, $report);

                    foreach ($values as $value) {
                        $terminalId = $value->getReportPositionGroup()->getId();

                        $result[$terminalId][$accountingPositionId]['name'] = $flexParam->getValue();
                    }
                } else {
                    $result[0][$accountingPositionId]['name'] = $flexParam->getValue();
                }
            }

            if ($report) {
                $flexParam = $this->manager->getRepository(FlexParam::class)
                    ->findOneByAccountingPositionAndType($accountingPosition, 'value');

                $reportPositionValues = $this->reportPositionValueService->getReportPositionValueByFlexParam($flexParam);

                if ($reportPositionValues) {
                    foreach ($reportPositionValues as $reportPositionValue) {
                        $reportPosition = $reportPositionValue->getReportPosition();

                        if ($reportPosition->getReport()->getId() != $report->getId()) {
                            continue;
                        }

                        $value = $reportPositionValue->getValue();
                        $value = sprintf("%.2f", $value);

                        $terminalId = $reportPositionValue->getReportPositionGroup()->getId();

                        $result[$terminalId][$accountingPositionId]['data'][$reportPosition->getId()] = $value;

                        if (isset($result[$terminalId][$accountingPositionId]['total'])) {
                            $result[$terminalId][$accountingPositionId]['total'] += $value;
                        } else {
                            $result[$terminalId][$accountingPositionId]['total'] = $value;
                        }
                    }
                }
            }
        }
        ksort($result);

        return $result;
    }

    /**
     * @param FacilityLayout $facilityLayout
     * @param Report|null $report
     * @return array
     */
    public function getBills(FacilityLayout $facilityLayout, Report $report = null)
    {
        $result = [];

        $billCategory = $this->manager->getRepository(AccountingCategory::class)->findOneBy(
            [
                'key' => $this->flexParamService::ACCOUNTING_CATEGORY_BILL_KEY
            ]
        );

        if (!$billCategory) {
            return $result;
        }

        $billFlexParamBackFront = $this->manager->getRepository(FlexParam::class)->findByCategoryAndFacilityLayout(
            $facilityLayout,
            $billCategory,
            'both'
        );

        if (!$billFlexParamBackFront) {
            return $result;
        }

        foreach ($billFlexParamBackFront as $flexParam) {
            $key = $flexParam->getKey();
            $accountingPosition = $flexParam->getAccountingPosition();

            if (in_array($key, ['receiver', 'amount', 'tip'])) {
                $result['settings'][$key] = $key;
            }

            if ('name' == $key) {
                $result['selectNames'][$accountingPosition->getId()] = $flexParam->getValue();
            }

            if ($report) {
                $reportPositionValues = $this->reportPositionValueService->getReportPositionValueByFlexParam($flexParam);

                if ($reportPositionValues) {
                    foreach ($reportPositionValues as $reportPositionValue) {
                        $reportPosition = $reportPositionValue->getReportPosition();

                        if ($reportPosition->getReport()->getId() != $report->getId()) {
                            continue;
                        }

                        $value  = $reportPositionValue->getValue();
                        $value  = in_array($key, ['amount', 'tip']) ? sprintf("%.2f", $value) : $value;

                        $result['data'][$reportPosition->getId()][$key] = $value;
                        $result['data'][$reportPosition->getId()]['name'] = $reportPosition->getAccountingPosition()->getId();
                    }
                }
            }
        }

        $result['sum'] = $this->getBillsSum($report, $result);

        return $result;
    }

    /**
     * @param FacilityLayout $facilityLayout
     * @param Report|null $report
     * @return array
     */
    public function getAcceptedVouchers(FacilityLayout $facilityLayout, Report $report = null)
    {
        $result = [];

        $acceptedVouchersCategory = $this->manager->getRepository(AccountingCategory::class)->findOneBy(
            [
                'key' => $this->flexParamService::ACCOUNTING_CATEGORY_ACCEPTED_VOUCHER_KEY
            ]
        );

        if (!$acceptedVouchersCategory) {
            return $result;
        }

        $acceptedVouchersFlexParam = $this->manager->getRepository(FlexParam::class)->findByCategoryAndFacilityLayout(
            $facilityLayout,
            $acceptedVouchersCategory,
            'frontoffice'
        );

        if (!$acceptedVouchersFlexParam) {
            return $result;
        }

        foreach ($acceptedVouchersFlexParam as $flexParam) {
            $key = $flexParam->getKey();

            $result['settings'][$key] = $key;

            if ($report) {
                $reportPositionValues = $this->reportPositionValueService->getReportPositionValueByFlexParam($flexParam);

                if ($reportPositionValues) {
                    foreach ($reportPositionValues as $reportPositionValue) {
                        $reportPosition = $reportPositionValue->getReportPosition();

                        if ($reportPosition->getReport()->getId() != $report->getId()) {
                            continue;
                        }

                        $value  = $reportPositionValue->getValue();
                        $value  = ('number' != $key) ? sprintf("%.2f", $value) : $value;
                        $result['data'][$reportPosition->getId()][$key] = $value;
                    }
                }
            }
        }

        if (isset($result['data'])) {
            $result['data'] = array_reverse($result['data'], true);
        }

        $result['sum']  = $this->getVouchersSum($report, $result);

        return $result;
    }

    /**
     * @param FacilityLayout $facilityLayout
     * @param Report|null $report
     * @return array
     */
    public function getIssuedVouchers(FacilityLayout $facilityLayout, Report $report = null)
    {
        $result = [];

        $issuedVouchersCategory = $this->manager->getRepository(AccountingCategory::class)->findOneBy(
            [
                'key' => $this->flexParamService::ACCOUNTING_CATEGORY_ISSUED_VOUCHER_KEY
            ]
        );

        if (!$issuedVouchersCategory) {
            return $result;
        }

        $issuedVouchersFlexParamFront = $this->manager->getRepository(FlexParam::class)->findByCategoryAndFacilityLayout(
            $facilityLayout,
            $issuedVouchersCategory,
            'frontoffice'
        );

        $issuedVouchersFlexParamBack = $this->manager->getRepository(FlexParam::class)->findByCategoryAndFacilityLayout(
            $facilityLayout,
            $issuedVouchersCategory,
            'backoffice'
        );

        if (!$issuedVouchersFlexParamFront) {
            return $result;
        }

        foreach ($issuedVouchersFlexParamFront as $flexParam) {
            $key = $flexParam->getKey();

            $result['settings'][$key] = $key;

            if ($report) {
                $reportPositionValues = $this->reportPositionValueService->getReportPositionValueByFlexParam($flexParam);

                if ($reportPositionValues) {
                    foreach ($reportPositionValues as $reportPositionValue) {
                        $reportPosition = $reportPositionValue->getReportPosition();

                        if ($reportPosition->getReport()->getId() != $report->getId()) {
                            continue;
                        }

                        $value  = $reportPositionValue->getValue();
                        $value  = ('number' != $key) ? sprintf("%.2f", $value) : $value;
                        $result['data'][$reportPosition->getId()][$key] = $value;

                        foreach ($issuedVouchersFlexParamBack as $item) {
                            if (
                                $item->getAccountingPosition()->getId() == $reportPosition->getAccountingPosition()->getId() &&
                                'addToTotalSalesAmount' == $item->getKey()
                            ) {
                                $result['data'][$reportPosition->getId()]['addToTotalSalesAmount'] =  $item->getValue() ? true : false;
                            }
                        }
                    }
                }
            }
        }

        if (isset($result['data'])) {
            $result['data'] = array_reverse($result['data'], true);
        }

        $result['sum']  = $this->getVouchersSum($report, $result);
        $result['addToTotalSalesAmount'] = $this->getAddToTotalSalesAmount($issuedVouchersFlexParamBack);

        return $result;
    }

    /**
     * @param FacilityLayout $facilityLayout
     * @param Report|null $report
     * @return mixed
     */
    public function getPaymentMethods(FacilityLayout $facilityLayout, Report $report = null)
    {
        $result = [];

        $categoriesIdsOrder = json_decode($facilityLayout->getPaymentMethodOrder());

        if ($categoriesIdsOrder) {
            foreach ($categoriesIdsOrder as $item) {
                $accountingCategory = $this->manager->getRepository(AccountingCategory::class)->find($item);

                if (!$accountingCategory) {
                    continue;
                }

                $key = $accountingCategory->getKey();

                if ('creditCard' == $key) {
                    $result['creditCards'] = $this->getCreditCards(
                        $facilityLayout,
                        $report ?: null
                    );
                } else if ('acceptedVoucher' == $key) {
                    $result['acceptedVouchers'] = $this->getAcceptedVouchers(
                        $facilityLayout,
                        $report ?: null
                    );
                } else if ('issuedVoucher' == $key) {
                    $result['issuedVouchers'] = $this->getIssuedVouchers(
                        $facilityLayout,
                        $report ?: null
                    );
                } else if ('bill' == $key) {
                    $result['bills'] = $this->getBills(
                        $facilityLayout,
                        $report ?: null
                    );
                } else if ('expenses' == $key) {
                    $result['expenses'] = $this->getExpenses(
                        $facilityLayout,
                        $report ?: null
                    );
                } else if ('cigarettes' == $key) {
                    $result['cigarettes'] = $this->getCigarettes(
                        $facilityLayout,
                        $report ?: null
                    );
                }
            }
        }

        return $result;
    }

    /**
     * @param array $paymentMethods
     * @return string
     */
    public function getAllVouchersSum(array $paymentMethods = [])
    {
        $result = '0.00';

        if (!$paymentMethods) {
            return $result;
        }

        foreach ($paymentMethods as $category => $paymentMethod) {
            if ('acceptedVouchers' == $category || 'issuedVouchers' == $category) {
                if (isset($paymentMethod['data'])) {
                    foreach ($paymentMethod['data'] as $item) {

                        if ('issuedVouchers' == $category && !isset($item['addToTotalSalesAmount'])) {
                            continue;
                        }

                        $result += $item['amount'];
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param FacilityLayout $facilityLayout
     * @param Report|null $report
     * @return array
     * @throws NonUniqueResultException
     */
    public function getTips(FacilityLayout $facilityLayout, Report $report = null)
    {
        $result = [];

        $tipsCategory = $this->manager->getRepository(AccountingCategory::class)->findOneBy(
            [
                'key' => $this->flexParamService::ACCOUNTING_CATEGORY_TIP_KEY
            ]
        );

        if (!$tipsCategory) {
            return $result;
        }

        $tips = $this->manager->getRepository(FlexParam::class)->findByCategoryAndFacilityLayout(
            $facilityLayout,
            $tipsCategory,
            'both'
        );

        if (!$tips) {
            return $result;
        }

        foreach ($tips as $tip) {
            $accountingPosition = $tip->getAccountingPosition();
            $accountingPositionId = $accountingPosition->getId();
            $key = $tip->getKey();

            if ('name' == $key) {
                $result[$accountingPositionId]['name'] = $tip->getValue();
            }

            if ('tipInPercentage' == $key) {
                $result[$accountingPositionId]['percent'] = $tip->getValue();
            }

            if ($report) {
                $reportPositionValue = $this->reportPositionValueService
                    ->getReportPositionValueByAccountingPositionAndReport($accountingPosition, $report);

                if ($reportPositionValue) {
                    $result[$accountingPositionId]['value'] = sprintf("%.2f", $reportPositionValue->getValue());
                    $result[$accountingPositionId]['reportPositionValue'] = $reportPositionValue->getId();
                }
            }
        }

        return $result;
    }



    public function getCash(FacilityLayout $facilityLayout, Report $report = null)
    {
        $result = [];

        $cashCategory = $this->manager->getRepository(AccountingCategory::class)->findOneBy(
            [
                'key' => $this->flexParamService::ACCOUNTING_CATEGORY_CASH_KEY
            ]
        );

        if (!$cashCategory) {
            return $result;
        }

        $cashFlexParams = $this->manager->getRepository(FlexParam::class)->findByCategoryAndFacilityLayout(
            $facilityLayout,
            $cashCategory,
            'backoffice'
        );

        if (!$cashFlexParams) {
            return $result;
        }

        foreach ($cashFlexParams as $cashFlexParam) {
            if ('name' == $cashFlexParam->getKey()) {
                $result['name'] = $cashFlexParam->getValue();
            }
        }

        $accountingPosition = reset($cashFlexParams)->getAccountingPosition();

        if ($report) {
            $reportPositionValue = $this->reportPositionValueService
                ->getReportPositionValueByAccountingPositionAndReport($accountingPosition, $report);

            if ($reportPositionValue) {
                $result['value'] = $reportPositionValue->getValue();
            }
        }

        return $result;
    }

    public function getReceivedCash(Report $report): array
    {
        $result = [];

        $values = $this->manager->getRepository(ReportPositionValue::class)->findReportPositionValuesByReportAndCategory($report, FlexParamService::ACCOUNTING_CATEGORY_CASH_KEY);

        if ($values) {
            /** @var ReportPositionValue $value */
            foreach ($values as $value) {
                $result[$value->getReportPosition()->getId()][$value->getParameter()->getKey()] = $value->getValue();
            }
        }
        return $result;
    }

    /**
     * @param array|null $totalSalesData
     * @return array
     */
    public function transformDataForChart(?array $totalSalesData): array
    {
        $days = $data = [];
        for ($i = 7; $i >= 1; $i--) {
            $days[] = date('d.m.Y', strtotime('-' . $i . 'days'));
        }

        foreach ($totalSalesData as $key => $value) {
            $totalSalesData[$value['date']] = $value;
        }

        foreach ($days as $day) {
            if (array_key_exists($day, $totalSalesData)) {
                $totalSalesData[$day]['date'] = date('l, ', strtotime($totalSalesData[$day]['date'])) . $totalSalesData[$day]['date'];
                $data[$day] = $totalSalesData[$day];
            } else {
                $data[$day] = ['total' => 0.00, 'date' => date('l, ', strtotime($day)) . $day];
            }
        }

        return $data;
    }

    /**
     * @param array $issuedVouchersFlexParamBack
     * @return bool
     */
    private function getAddToTotalSalesAmount(array $issuedVouchersFlexParamBack): bool
    {
        $result = false;
        foreach ($issuedVouchersFlexParamBack as $item) {
            if ($item->getKey() === 'addToTotalSalesAmount' && $item->getValue() != '' ) {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * @param Report $oldReport
     * @param Report $newReport
     */
    public function clonePositions(Report $oldReport, Report $newReport): void
    {
        $reportPositions = $oldReport->getReportPositions();

        if (count($reportPositions)) {
            /** @var ReportPosition $reportPosition */
            foreach ($reportPositions as $reportPosition) {
                $newReportPosition = clone $reportPosition;
                $reportPositionValues = $reportPosition->getReportPositionValues();
                if (count($reportPositionValues)) {
                    foreach ($reportPositionValues as $reportPositionValue) {
                        $newReportPositionValue = clone $reportPositionValue;
                        $newReportPosition->addReportPositionValue($newReportPositionValue);
                    }
                }
                $newReportPosition->setCreatedBy($newReport->getCreatedBy());
                $newReport->addReportPosition($newReportPosition);
            }
        }
    }

    /**
     * @param Report $report
     * @param int|null $number
     * @return void
     */
    public function approveReport(Report $report, ?int $number = null): void
    {
        $report->setApproved(true);
        $report->setNumber($number);
        $this->manager->flush();
    }

    public function isDateAllowed(Facility $facility, Account $user, $date, $datesDisabled = [], $reportType = Report::REPORT_TYPE_BACKOFFICER, $approved = null): bool
    {
        $date = (new \DateTimeImmutable($date))->format('Y-m-d');
        $datesDisabled = $this->transformFormatDates($datesDisabled, 'Y-m-d');

        if (in_array($date, $datesDisabled)) {
            return false;
        }

        /** @var Report $backofficerReport */
        $backofficerReport = $this->manager->getRepository(Report::class)->findOneByFacilityStatementDate($facility, $date, $reportType, $approved);

        if ($backofficerReport) {
            return false;
        }

        return true;
    }
}
