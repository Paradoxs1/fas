<?php

namespace App\Service\Routine;


interface RoutineProcessorInterface
{
    /**
     * @return string
     */
    public function getSuccessMessage(): string;

    /**
     * @return array
     */
    public function getErrorMessages();
}
