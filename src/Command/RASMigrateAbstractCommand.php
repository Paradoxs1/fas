<?php

namespace App\Command;

use App\Service\RASMigrationService;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * Abstract class RASMigrateAbstractCommand
 * @package App\Command
 */
abstract class RASMigrateAbstractCommand extends Command
{
    /**
     * @var ContainerBagInterface
     */
    protected $params;

    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var RASMigrationService
     */
    protected $RASMigrationService;

    /**
     * @var UserPasswordEncoderInterface
     */
    protected $passwordEncoder;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * RASMigrateAbstractCommand constructor.
     * @param ContainerBagInterface $params
     * @param EntityManagerInterface $em
     * @param RASMigrationService $RASMigrationService
     * @param UserPasswordEncoderInterface $passwordEncoder
     */
    public function __construct(
        ContainerBagInterface $params,
        EntityManagerInterface $em,
        RASMigrationService $RASMigrationService,
        UserPasswordEncoderInterface $passwordEncoder
    ) {
        $this->params = $params;
        $this->em = $em;
        $this->RASMigrationService = $RASMigrationService;
        $this->passwordEncoder = $passwordEncoder;

        parent::__construct();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \Doctrine\DBAL\DBALException
     * @return void
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->connection = DriverManager::getConnection(
            ['url' => $this->params->get('RAS')['url']],
            new Configuration()
        );
    }
}
