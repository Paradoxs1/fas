<?php

namespace App\Tests\Service\Report\Handler;

use App\Entity\Account;
use App\Entity\Report;
use App\Service\Report\Handler\BillHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class BillHandlerTest extends KernelTestCase
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var BillHandler
     */
    private $billHandler;

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
        $this->billHandler = $kernel->getContainer()->get(BillHandler::class);
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
        $result = $this->billHandler->checkChanges($this->report, $data);
        $this->assertSame($expected, $result);
    }

    /**
     * @dataProvider addDataProvider
     */
    public function testAddNewPositions($data)
    {
        $reportPositionBeforeCnt = count($this->report->getReportPositions());
        $this->billHandler->addPositions($data, $this->report, $this->account);
        $reportPositionAfterCnt = count($this->report->getReportPositions());

        $this->assertSame($reportPositionBeforeCnt + 1, $reportPositionAfterCnt);
    }

    public function checkDataProvider()
    {
        return [
                'correct item' => [
                    [
                        'bills' => [
                            6 => [
                                'receiver' => 'b1',
                                'amount' => 50.00,
                                'tip' => 5.00,
                                'name' => 12
                            ]
                        ]
                    ],
                    false
                ],
                'correct position changed receiver' => [
                    [
                        'bills' => [
                            1 => [
                                'receiver' => 'b3',
                                'amount' => 50.00,
                                'tip' => 5.00,
                                'name' => 12
                            ]
                        ]
                    ],
                    true
                ],
                'correct position changed amount' => [
                    [
                        'bills' => [
                            1 => [
                                'receiver' => 'b1',
                                'amount' => 51.00,
                                'tip' => 5.00,
                                'name' => 12
                            ]
                        ]
                    ],
                    true
                ],
                'correct position changed tip' => [
                    [
                        'bills' => [
                            1 => [
                                'receiver' => 'b1',
                                'amount' => 50.00,
                                'tip' => 6.00,
                                'name' => 12
                            ]
                        ]
                    ],
                    true
                ],
                'correct position changed name' => [
                    [
                        'bills' => [
                            1 => [
                                'receiver' => 'b1',
                                'amount' => 50.00,
                                'tip' => 5.00,
                                'name' => 13
                            ]
                        ]
                    ],
                    true
                ],
                'more one posittion' => [
                    [
                        'bills' => [
                            1 => [
                                'receiver' => 'b1',
                                'amount' => 50.00,
                                'tip' => 5.00,
                                'name' => 12
                            ],
                            2 => [
                                'receiver' => 'b5',
                                'amount' => 50.00,
                                'tip' => 5.00,
                                'name' => 12
                            ]
                        ]
                    ],
                    true
                ],
                'no positions' => [
                    [
                        'bills' => []
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
                        'receiver' => 'b5',
                        'amount' => 50.00,
                        'tip' => 5.00,
                        'name' => 12
                    ]
                ]
            ]
        ];
    }
}
