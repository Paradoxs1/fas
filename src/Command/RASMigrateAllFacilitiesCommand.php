<?php

namespace App\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class RASMigrateAllFacilitiesCommand
 * @package App\Command
 */
class RASMigrateAllFacilitiesCommand extends RASMigrateAbstractCommand
{
    /**
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('app:ras-facility-migrate-all')
            ->setDescription('Migrates RAS facility')
            ->setHelp('Migrates RAS facility user to FAS')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $output->writeln(['Migrating All RAS Restaurants to FAS Facilities']);

        $this->em->getConnection()->beginTransaction();
        try {
            //Skipping the first one ("template_1")
            $restaurants = $this->connection->fetchAll('SELECT * FROM restaurants WHERE id != 1');
            $this->RASMigrationService->migrateAllFacilities($restaurants);

            $this->em->getConnection()->commit();
            $output->writeln(['Finished successfully!']);
        } catch (\Exception $e) {
            $this->em->getConnection()->rollBack();
            $output->writeln([$e->getMessage()]);
        }
    }
}
