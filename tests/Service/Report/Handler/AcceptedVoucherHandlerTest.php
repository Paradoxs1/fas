<?php

namespace App\Tests\Service\Report\Handler;

use App\Entity\Account;
use App\Entity\Report;
use App\Service\Report\Handler\AcceptedVoucherHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AcceptedVoucherHandlerTest extends KernelTestCase
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var AcceptedVoucherHandler
     */
    private $acceptedVoucherHandler;

    /**
     * @var Report
     */
    private $report;

    /**
     * @var Account
     */
    private $account;

    protected function setUp()
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->acceptedVoucherHandler = $kernel->getContainer()->get(AcceptedVoucherHandler::class);
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
        $result = $this->acceptedVoucherHandler->checkChanges($this->report, $data);
        $this->assertSame($expected, $result);
    }

    /**
     * @dataProvider addDataProvider
     */
    public function testAddNewPositions($data)
    {
        $reportPositionBeforeCnt = count($this->report->getReportPositions());
        $this->acceptedVoucherHandler->addPositions($data, $this->report, $this->account);
        $reportPositionAfterCnt = count($this->report->getReportPositions());

        $this->assertSame($reportPositionBeforeCnt + 1, $reportPositionAfterCnt);
    }

    public function checkDataProvider()
    {
        return [
                'new item' => [
                    [
                        'accepted-vouchers' => [
                            1 => [
                                'number' => 'v21',
                                'amount' => 21.00
                            ]
                        ]
                    ],
                    true
                ],
                'correct item' => [
                    [
                        'accepted-vouchers' => [
                            2 => [
                                'number' => 'v2',
                                'amount' => 20.00
                            ]
                        ]
                    ],
                    false
                ],
                'correct position changed number' => [
                    [
                        'accepted-vouchers' => [
                            2 => [
                                'number' => 'v1',
                                'amount' => 20.00
                            ]
                        ]
                    ],
                    true
                ],
                'correct position changed amount' => [
                    [
                        'accepted-vouchers' => [
                            2 => [
                                'number' => 'v2',
                                'amount' => 2000.00
                            ]
                        ]
                    ],
                    true
                ],
                'more one posittion' => [
                    [
                        'accepted-vouchers' => [
                            2 => [
                                'number' => 'v2',
                                'amount' => 2000.00
                            ],
                            3 => [
                                'number' => 'v21',
                                'amount' => 12.00
                            ]
                        ]
                    ],
                    true
                ],
                'no positions' => [
                    [
                        'accepted-vouchers' => []
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
                        'number' => 'v21',
                        'amount' => 21.00
                    ]
                ]
            ]
        ];
    }
}
