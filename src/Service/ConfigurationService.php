<?php

namespace App\Service;

use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class FacilityService
 * @package App\Service
 */
class ConfigurationService
{
    /**
     * @var ObjectManager
     */
    private $manager;

    /**
     * ConfigurationService constructor.
       * @param ObjectManager $manager
     */
    public function __construct(
        ObjectManager $manager
    ){
        $this->manager = $manager;
    }

    /**
     * @return array
     */
    public function getDaysOfWeekMapping(): array
    {
        return [
            0 => 'monday',
            1 => 'tuesday',
            2 => 'wednesday',
            3 => 'thursday',
            4 => 'friday',
            5 => 'saturday',
            6 => 'sunday',
        ];
    }
}