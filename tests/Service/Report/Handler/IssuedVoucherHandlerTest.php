<?php

namespace App\Tests\Service\Report\Handler;

use App\Entity\Account;
use App\Entity\Report;
use App\Service\Report\Handler\IssuedVoucherHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class IssuedVoucherHandlerTest extends KernelTestCase
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var IssuedVoucherHandler
     */
    private $issuedVoucherHandler;

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
        $this->issuedVoucherHandler = $kernel->getContainer()->get(IssuedVoucherHandler::class);
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
        $result = $this->issuedVoucherHandler->checkChanges($this->report, $data);
        $this->assertSame($expected, $result);
    }

    /**
     * @dataProvider addDataProvider
     */
    public function testAddNewPositions($data)
    {
        $reportPositionBeforeCnt = count($this->report->getReportPositions());
        $this->issuedVoucherHandler->addPositions($data, $this->report, $this->account);
        $reportPositionAfterCnt = count($this->report->getReportPositions());

        $this->assertSame($reportPositionBeforeCnt + 1, $reportPositionAfterCnt);
    }

    public function checkDataProvider()
    {
        return [
                'new item' => [
                    [
                        'issued-vouchers' => [
                            1 => [
                                'number' => 'v4',
                                'amount' => 21.00
                            ]
                        ]
                    ],
                    true
                ],
                'correct item' => [
                    [
                        'issued-vouchers' => [
                            3 => [
                                'number' => 'v4',
                                'amount' => 40.00
                            ]
                        ]
                    ],
                    false
                ],
                'correct position changed number' => [
                    [
                        'issued-vouchers' => [
                            3 => [
                                'number' => 'v10',
                                'amount' => 40.00
                            ]
                        ]
                    ],
                    true
                ],
                'correct position changed amount' => [
                    [
                        'accepted-vouchers' => [
                            3 => [
                                'number' => 'v4',
                                'amount' => 2000.00
                            ]
                        ]
                    ],
                    true
                ],
                'more one posittion' => [
                    [
                        'issued-vouchers' => [
                            2 => [
                                'number' => 'v2',
                                'amount' => 2000.00
                            ],
                            3 => [
                                'number' => 'v4',
                                'amount' => 40.00
                            ]
                        ]
                    ],
                    true
                ],
                'no positions' => [
                    [
                        'issued-vouchers' => []
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
