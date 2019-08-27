<?php

namespace App\Command;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class MigrateAllDataByFacilityCommand
 * @package App\Command
 */
class MigrateAllDataByFacilityCommand extends RASMigrateAbstractCommand
{
    /**
     * @var int
     */
    private $facilityId;

    private $commands = ['app:ras-facility-migrate', 'app:ras-user-migrate', 'app:ras-reports-migrate'];

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('app:ras-data-migrate')
            ->setDescription('Migrate facility, users, reports by facility ID')
            ->addArgument(
                'facilityId',
                InputArgument::REQUIRED,
                'RAS facility ID'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws \Doctrine\DBAL\ConnectionException
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->facilityId = $input->getArgument('facilityId');

        $this->em->getConnection()->beginTransaction();
        try {

            foreach ($this->commands as $command) {
                $command = $this->getApplication()->find($command);

                $arguments = [
                    'command' => $command,
                    'facilityId' => $this->facilityId
                ];

                $input = new ArrayInput($arguments);
                $command->run($input, $output);
            }

            $this->em->getConnection()->commit();
            $output->writeln(['Finished successfully!']);
        } catch (\Exception $e) {
            $this->em->getConnection()->rollBack();
            $output->writeln([$e->getMessage()]);
        }
    }
}
