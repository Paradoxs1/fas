<?php

namespace App\Tests\Service\Report\Handler;

use App\Entity\Report;
use App\Service\Report\Handler\CigaretteHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CigaretteHandlerTest extends KernelTestCase
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var CigaretteHandler
     */
    private $cigaretteHandler;

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
        $this->cigaretteHandler = $kernel->getContainer()->get(CigaretteHandler::class);
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
        $result = $this->cigaretteHandler->checkChanges($this->report, $data);
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
                    'cigarettes' => 15.00,
                ],
                false
            ],
            'changed amount' => [
                [
                    'cigarettes' => 10.00,
                ],
                true
            ]
        ];
    }
}
