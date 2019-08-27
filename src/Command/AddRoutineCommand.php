<?php

namespace App\Command;

use App\Entity\RoutineTemplate;
use App\Service\Routine\RoutineRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AddRoutineCommand
 * @package App\Command
 */
class AddRoutineCommand extends Command
{
    /**
     * @var RoutineRegistry
     */
    private $routineRegistry;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * AddRoutineCommand constructor.
     * @param RoutineRegistry $routineRegistry
     * @param EntityManagerInterface $em
     */
    public function __construct(RoutineRegistry $routineRegistry, EntityManagerInterface $em)
    {
        $this->routineRegistry = $routineRegistry;
        $this->em = $em;

        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('app:add-routines')
            ->setDescription('Add routines');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws \Doctrine\DBAL\ConnectionException
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $routines = $this->routineRegistry->getRoutines();

        $this->em->getConnection()->beginTransaction();
        try {
            foreach($routines as $routineClass) {
                $routineName = $this->em->getRepository(RoutineTemplate::class)->findOneBy(['name' => $routineClass->getName()]);

                if (!$routineName) {
                    $routineTemplate = new RoutineTemplate();
                    $routineTemplate->setName($routineClass->getName());
                    $routineTemplate->setClass(get_class($routineClass));
                    $routineTemplate->setAccountingPositionsTemplate(json_encode($routineClass->getAccountingPositionsTemplate()));
                    $routineTemplate->setParamTemplate(json_encode($routineClass->getParamTemplate()));
                    $this->em->persist($routineTemplate);
                }
            }
            $this->em->flush();
            $this->em->getConnection()->commit();
            $output->writeln(['Finished successfully!']);
        } catch (\Exception $e) {
            $this->em->getConnection()->rollBack();
            $output->writeln([$e->getMessage()]);
        }
    }
}
