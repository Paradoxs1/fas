<?php

namespace App\Tests\Service\Report\Handler;

use App\Entity\Account;
use App\Entity\Report;
use App\Service\Report\Handler\ExpenseHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ExpenseHandlerTest extends KernelTestCase
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ExpenseHandler
     */
    private $expenseHandler;

    /**
     * @var Report
     */
    private $report;

    /**
     * @var Account
     */
    private $account;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->expenseHandler = $kernel->getContainer()->get(ExpenseHandler::class);
        $this->report = $this->entityManager->getRepository(Report::class)->find(1);
        $this->account = $this->entityManager->getRepository(Account::class)->find(6);
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        parent::tearDown();

        $this->entityManager->close();
        $this->entityManager = null;
    }

    /**
     * @dataProvider checkDataProvider
     */
    public function testCheckChanges($data, $expected)
    {
        $result = $this->expenseHandler->checkChanges($this->report, $data);
        $this->assertSame($expected, $result);
    }

    /**
     * @dataProvider addDataProvider
     */
    public function testAddNewPositions($data)
    {
        $reportPositionBeforeCnt = count($this->report->getReportPositions());
        $this->expenseHandler->addPositions($data, $this->report, $this->account);
        $reportPositionAfterCnt = count($this->report->getReportPositions());

        $this->assertSame($reportPositionBeforeCnt + 1, $reportPositionAfterCnt);
    }

    public function checkDataProvider()
    {
        return [

                'correct item' => [
                    [
                        'expenses' => [
                            7 => [
                                'name' => 'e2',
                                'amount' => 70.00
                            ]
                        ]
                    ],
                    false
                ],
                'new item' => [
                    [
                        'expenses' => [
                            1 => [
                                'name' => 'e1',
                                'amount' => 10.00
                            ]
                        ]
                    ],
                    true
                ],
                'changed name' => [
                    [
                        'expenses' => [
                            7 => [
                                'name' => 'e12',
                                'amount' => 70.00
                            ]
                        ]
                    ],
                    true
                ],
                'changed amount' => [
                    [
                        'expenses' => [
                            7 => [
                                'name' => 'e2',
                                'amount' => 10.00
                            ]
                        ]
                    ],
                    true
                ],
                'no positions' => [
                    [
                        'expenses' => []
                    ],
                    true
                ]
        ];
    }

    public function addDataProvider()
    {
        return [
            'new item' => [
                [
                    1 => [
                        'name' => 'e1',
                        'amount' => 10.00
                    ]
                ]
            ]
        ];
    }
}
