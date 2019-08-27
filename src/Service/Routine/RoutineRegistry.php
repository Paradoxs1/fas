<?php

namespace App\Service\Routine;

class RoutineRegistry
{
    /**
     * @var array
     */
    private $routines = [];

    /**
     * @param RoutineInterface $routine
     */
    public function add(RoutineInterface $routine)
    {
        $this->routines[$routine->getName()] = $routine;
    }

    /**
     * @return array
     */
    public function getRoutines()
    {
        return $this->routines;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasRoutine(string $name): bool
    {
        return isset($this->routines[$name]);
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getRoutine(string $name): RoutineInterface
    {
        if (!$this->hasRoutine($name)) {
            throw new \InvalidArgumentException(sprintf('Routine "%s" does not exist.', $name));
        }

        return $this->routines[$name];
    }
}
