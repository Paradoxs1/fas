<?php

namespace App\Tests\Service\Report\Handler;

use App\Entity\Account;
use App\Entity\Report;
use App\Service\Report\Handler\QuestionsHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class QuestionsHandlerTest extends KernelTestCase
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var QuestionsHandler
     */
    private $questionsHandler;

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
        $this->questionsHandler = $kernel->getContainer()->get(QuestionsHandler::class);
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
        $result = $this->questionsHandler->checkChanges($this->report, $data);
        $this->assertSame($expected, $result);
    }

    public function checkDataProvider()
    {
        return [
            'correct item' => [
                [
                    'question-answer' => [
                        14 => 'werwer',
                        15 => 'werwe'
                    ]
                ],
                false
            ],
            'changed value' => [
                [
                    'question-answer' => [
                        14 => 'werwer',
                        15 => 'eqweqwe'
                    ]
                ],
                true
            ]
        ];
    }
}
