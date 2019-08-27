<?php

namespace App\Service\Facility;

use App\Entity\FacilityLayout;
use App\Entity\RoutineTemplate;
use App\Service\Routine\DefaultRoutine;
use App\Service\Routine\RoutineInterface;
use App\Service\Routine\RoutineRegistry;
use Doctrine\ORM\EntityManagerInterface;


abstract class ConfigurationParamsHandler
{
    use AccountingPositionHelper;

    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var RoutineRegistry
     */
    protected $routineRegistry;

    /**
     * ConfigurationParamsHandler constructor.
     * @param EntityManagerInterface $em
     * @param RoutineRegistry $routineRegistry
     */
    public function __construct(EntityManagerInterface $em, RoutineRegistry $routineRegistry)
    {
        $this->em = $em;
        $this->routineRegistry = $routineRegistry;
    }

    /**
     * @param FacilityLayout $facilityLayout
     * @return RoutineInterface
     */
    public function getRoutine(FacilityLayout $facilityLayout): RoutineInterface
    {
        $facility = $facilityLayout->getFacility();
        if (!$facility) {
            $routine = new DefaultRoutine();
        } else {
            $routine = $facilityLayout->getFacility()->getRoutine();
            /** @var RoutineTemplate $routineTemplate */
            $routine = $this->routineRegistry->getRoutine($routine->getName());
        }

        return $routine;
    }

    /**
     * @param array $data
     * @param FacilityLayout $facilityLayout
     * @return bool
     */
    abstract public function checkChanges(array $data, FacilityLayout $facilityLayout): bool;

    /**
     * @param array $data
     * @param FacilityLayout $facilityLayout
     * @param bool $update
     * @return mixed
     */
    abstract public function addPositions(array $data, FacilityLayout $facilityLayout, $update = false);

    /**
     * @param array $data
     * @param FacilityLayout $facilityLayout
     * @return mixed
     */
    abstract public function getPositions(array &$data, FacilityLayout $facilityLayout);

    /**
     * @return string
     */
    abstract public function getName(): string;
}
