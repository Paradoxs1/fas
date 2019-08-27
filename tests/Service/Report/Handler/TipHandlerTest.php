<?php

namespace App\Tests\Service\Report\Handler;

use App\Entity\Account;
use App\Entity\Report;
use App\Service\Report\Handler\TipHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TipHandlerTest extends KernelTestCase
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var TipHandler
     */
    private $tipHandler;

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
        $this->tipHandler = $kernel->getContainer()->get(TipHandler::class);
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
        $result = $this->tipHandler->checkChanges($this->report, $data);
        $this->assertSame($expected, $result);
    }

    public function checkDataProvider()
    {
        return [
            'correct item' => [
                [
                    'tips' => [
                        5 => 39.00,
                        6 => 19.50
                    ]
                ],
                false
            ],
            'changed value' => [
                [
                    'tips' => [
                        1 => 10.00,
                        2 => 11.00
                    ]
                ],
                true
            ]

        ];
    }

}
