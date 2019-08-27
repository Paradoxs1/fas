<?php

namespace App\Tests\Service\Report\Handler;

use App\Entity\Report;
use App\Service\Report\Handler\TotalSalesHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TotalSalesHandlerTest extends KernelTestCase
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var TotalSalesHandler
     */
    private $totalSalesHandler;

    /**
     * @var Report
     */
    private $report;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->totalSalesHandler = $kernel->getContainer()->get(TotalSalesHandler::class);
        $this->report = $this->entityManager->getRepository(Report::class)->find(1);
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
        $result = $this->totalSalesHandler->checkChanges($this->report, $data);
        $this->assertSame($expected, $result);
    }

    /**
     * @return array
     */
    public function checkDataProvider()
    {
        return [
            'correct item' => [
                [
                    'total-sales' => 3900.00,
                ],
                false
            ],
            'changed' => [
                [
                    'total-sales' => 3901.00,
                ],
                true
            ]
        ];
    }
}
