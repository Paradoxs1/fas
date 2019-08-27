<?php

namespace App\Service;

use App\Entity\CostForecastWeekDay;
use App\Entity\Facility;

use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class CostForecastService
 * @package App\Service
 */
class CostForecastService
{
    /**
     * @var ObjectManager
     */
    private $manager;

    /**
     * CostForecastService constructor.
     *
     * @param ObjectManager $manager
     */
    public function __construct(
        ObjectManager $manager
    ) {
        $this->manager = $manager;
    }

    /**
     * @param Facility $facility
     */
    public function addCostForecastForNewFacility(Facility $facility)
    {
        $costTypes = [
            'staffcosts',
            'operatingcosts',
            'costofgoods',
        ];

        $days = [
            0,
            1,
            2,
            3,
            4,
            5,
            6
        ];

        foreach ($days as $day) {
            foreach ($costTypes as $costType) {
                $costForecastWeekDay = new CostForecastWeekDay();
                $costForecastWeekDay->setFacility($facility);
                $costForecastWeekDay->setType('fix');
                $costForecastWeekDay->setValue(0);
                $costForecastWeekDay->setDayOfWeek($day);
                $costForecastWeekDay->setCategory($costType);

                $this->manager->persist($costForecastWeekDay);
            }
        }
        $this->manager->persist($facility);

        $this->manager->flush();
    }
}
