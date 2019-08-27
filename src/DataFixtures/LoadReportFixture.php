<?php

namespace App\DataFixtures;

use App\Entity\Facility;
use App\Entity\Report;
use App\Service\FacilityService;
use App\Service\Report\Handler\AcceptedVoucherHandler;
use App\Service\Report\Handler\BillHandler;
use App\Service\Report\Handler\CreditCardHandler;
use App\Service\Report\Handler\ExpenseHandler;
use App\Service\Report\Handler\IssuedVoucherHandler;
use App\Service\ReportService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class LoadReportFixture
 * @package App\DataFixtures
 */
class LoadReportFixture extends Fixture implements FixtureInterface, OrderedFixtureInterface
{
    /**
     * @var AcceptedVoucherHandler
     */
    private $acceptedVoucherHandler;

    /**
     * @var IssuedVoucherHandler
     */
    private $issuedVoucherHandler;

    /**
     * @var CreditCardHandler
     */
    private $creditCardHandler;

    /**
     * @var BillHandler
     */
    private $billHandler;

    /**
     * @var ExpenseHandler
     */
    private $expensHandler;

    /**
     * @var FacilityService
     */
    private $facilityService;

    /**
     * @var ReportService
     */
    private $reportService;

    /**
     * @var array
     */
    public static $data = [
        'total-sales' => 3900.00,
        'credit-cards' => [
            0 => [
                3 => [
                    1 => 100.00
                ],
                4 => [
                    1 => 200.00
                ]
            ]
        ],
        'accepted-vouchers' => [
            1 => [
                'number' => 'v2',
                'amount' => 20.00
            ]
        ],
        'issued-vouchers' => [
            1 => [
                'number' => 'v4',
                'amount' => 40.00
            ]
        ],
        'bills' => [
            1 => [
                'receiver' => 'b1',
                'amount' => 50.00,
                'tip' => 5.00,
                'name' => 12
            ]
        ],
        'expenses' => [
            1 => [
                'name' => 'e2',
                'amount' => 70.00
            ]
        ],

        'cigarettes' => 15.00,
        "cash-income" => "3460",
        'tips' => [
            5 => 39.00,
            6 => 19.50
        ],
        'question-answer' => [
            14 => 'werwer',
            15 => 'werwe'
        ]
    ];

    /**
     * LoadReportFixture constructor.
     * @param AcceptedVoucherHandler $acceptedVoucherHandler
     * @param IssuedVoucherHandler $issuedVoucherHandler
     * @param CreditCardHandler $creditCardHandler
     * @param BillHandler $billHandler
     * @param ExpenseHandler $expenseHandler
     * @param ReportService $reportService
     * @param FacilityService $facilityService
     */
    public function __construct(
        AcceptedVoucherHandler $acceptedVoucherHandler,
        IssuedVoucherHandler $issuedVoucherHandler,
        CreditCardHandler $creditCardHandler,
        BillHandler $billHandler,
        ExpenseHandler $expenseHandler,
        ReportService $reportService,
        FacilityService $facilityService
    )
    {
        $this->acceptedVoucherHandler = $acceptedVoucherHandler;
        $this->issuedVoucherHandler = $issuedVoucherHandler;
        $this->creditCardHandler = $creditCardHandler;
        $this->billHandler = $billHandler;
        $this->expensHandler = $expenseHandler;
        $this->reportService = $reportService;
        $this->facilityService = $facilityService;
    }

    /**
     * @param ObjectManager $manager
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function load(ObjectManager $manager)
    {
        $report = new Report();
        /** @var Facility $facility */
        $facility = $this->getReference('Tim_facility');
        $account = $this->getReference('ts+facilitymanager');

        $report->setFacilityLayout($facility->getFacilityLayouts()->last());
        $report->setCreatedBy($account);
        $report->setStatementDate(new \DateTimeImmutable());
        $report->setShifts(1);
        $report->setType(Report::REPORT_TYPE_CASHIER);
        $report->setApproved(false);
        $report->setDeletedAt(new \DateTimeImmutable());

        $this->acceptedVoucherHandler->addPositions(self::$data['accepted-vouchers'], $report, $account);
        $this->issuedVoucherHandler->addPositions(self::$data['issued-vouchers'], $report, $account);
        $this->reportService->addCreditCards($account, $report, self::$data['credit-cards']);
        $this->billHandler->addPositions(self::$data['bills'], $report, $account);
        $this->expensHandler->addPositions(self::$data['expenses'], $report, $account);
        $this->reportService->addCigarettes($report->getFacilityLayout(), $account, $report, self::$data['cigarettes']);
        $this->reportService->addQuestionAnswers($account, $report, self::$data['question-answer']);
        $this->reportService->addTips($account, $report, self::$data['tips']);
        $this->reportService->addTotalSales($report->getFacilityLayout(), $account, $report, self::$data['total-sales']);

        $manager->persist($report);
        $manager->flush();
    }

    /**
     * @return int
     */
    public function getOrder()
    {
        return FixtureOrder::REPORT;
    }
}
