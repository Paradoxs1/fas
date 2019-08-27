<?php

namespace App\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class RASMigrateFacilityCommand
 * @package App\Command
 */
class RASMigrateFacilityCommand extends RASMigrateAbstractCommand
{
    /**
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('app:ras-facility-migrate')
            ->setDescription('Migrates RAS facility')
            ->setHelp('Migrates RAS facility user to FAS')
            ->addArgument('facilityId', InputArgument::REQUIRED, 'The RAS facilityId of Restaurant.')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws \Doctrine\DBAL\ConnectionException
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $output->writeln(['Migrating RAS Restaurants to FAS Facilities with RAS facilityId: '.$input->getArgument('facilityId')]);

        $this->em->getConnection()->beginTransaction();
        try {
            $restaurant = $this->connection->fetchAssoc('SELECT * FROM restaurants WHERE id = '. (int) $input->getArgument('facilityId'));
            $this->RASMigrationService->migrateFacility($restaurant);

            $this->em->getConnection()->commit();
            $output->writeln(['Finished successfully!']);
        } catch (\Exception $e) {
            $this->em->getConnection()->rollBack();
            $output->writeln([$e->getMessage()]);
        }
    }
}
