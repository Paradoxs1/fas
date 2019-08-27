<?php

namespace App\Tests\Service\Report\Handler;

use App\Entity\Report;
use App\Service\Report\Handler\ShiftHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ShiftHandlerTest extends KernelTestCase
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ShiftHandler
     */
    private $shiftHandler;

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
        $this->shiftHandler = $kernel->getContainer()->get(ShiftHandler::class);
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
        $result = $this->shiftHandler->checkChanges($this->report, $data);
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
                    'shifts' => 1,
                ],
                false
            ],
            'changed' => [
                [
                    'shifts' => 2,
                ],
                true
            ]
        ];
    }
}
