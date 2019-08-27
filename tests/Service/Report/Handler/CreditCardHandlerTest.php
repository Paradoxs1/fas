<?php

namespace App\Tests\Service\Report\Handler;

use App\Entity\Account;
use App\Entity\Report;
use App\Service\Report\Handler\CreditCardHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CreditCardHandlerTest extends KernelTestCase
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var CreditCardHandler
     */
    private $creditCardHandler;

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
        $this->creditCardHandler = $kernel->getContainer()->get(CreditCardHandler::class);
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
        $result = $this->creditCardHandler->checkChanges($this->report, $data);
        $this->assertSame($expected, $result);
    }

    /**
     * @dataProvider addDataProvider
     */
    public function testAddNewPositions($data)
    {
        $reportPositionBeforeCnt = count($this->report->getReportPositions());
        $this->creditCardHandler->addPositions($data, $this->report, $this->account);
        $reportPositionAfterCnt = count($this->report->getReportPositions());

        $this->assertSame($reportPositionBeforeCnt + 1, $reportPositionAfterCnt);
    }

    public function checkDataProvider()
    {
        return [
            'new terminal' => [
                [
                    'credit-cards' => [
                        10 => [
                            3 => [
                                6 => 100.00
                            ]
                        ]
                    ]
                ],
                true
            ],
            'correct item' => [
                [
                    'credit-cards' => [
                       1 => [
                            3 => [
                                6 => 100.00
                            ],
                            4 => [
                                7 => 200.00
                            ]
                        ]
                    ]
                ],
                false
            ],
            'changed amount' => [
                [
                    'credit-cards' => [
                        1 => [
                            3 => [
                                6 => 101.00
                            ],
                            4 => [
                                7 => 200.00
                            ]
                        ]
                    ]
                ],
                true
            ],
            'one field removed' => [
                [
                    'credit-cards' => [
                        1 => [
                            3 => [
                                6 => 100.00
                            ]
                        ]
                    ]
                ],
                true
            ],
            'added new terminal' => [
                [
                    'credit-cards' => [
                        1 => [
                            3 => [
                                6 => 100.00
                            ],
                            4 => [
                                7 => 200.00
                            ]
                        ],
                        2 => [
                            3 => [
                                6 => 100.00
                            ],
                            4 => [
                                7 => 200.00
                            ]
                        ]
                    ]
                ],
                true
            ],
            'one field remove one added' => [
                [
                    'credit-cards' => [
                        1 => [
                            100 => [
                                100 => 100.00
                            ],
                            4 => [
                                7 => 200.00
                            ]
                        ]
                    ]
                ],
                true
            ],
            'no positions' => [
                [
                    'credit-cards' => []
                ],
                true
            ]
        ];
    }

    public function addDataProvider()
    {
        return [
            'correct item' => [
                [
                    1 => [
                        5 => [
                            6 => 200.00
                        ]
                    ]
                ]
            ],
        ];
    }
}
