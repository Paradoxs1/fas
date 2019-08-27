<?php

namespace App\DataFixtures;

use App\Entity\CostForecastWeekDay;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * Class LoadEstimatedCostFixture
 * @package App\DataFixtures
 */
class LoadEstimatedCostFixture extends Fixture implements FixtureInterface, OrderedFixtureInterface
{
    /**
     * @var UserPasswordEncoderInterface
     */
    private $encoder;

    /**
     * LoadEstimatedCostFixture constructor.
     * @param UserPasswordEncoderInterface $encoder
     */
    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
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

        $facilities = [
            $this->getReference('Tim_facility'),
            $this->getReference('Damien_facility'),
            $this->getReference('Artem_facility'),
            $this->getReference('Nikolay_facility'),
        ];

        foreach ($facilities as $facility) {
            foreach ($days as $day) {
                foreach ($costTypes as $costType) {
                    $costForecastWeekDay = new CostForecastWeekDay();
                    $costForecastWeekDay->setFacility($facility);
                    $costForecastWeekDay->setType('fix');
                    $costForecastWeekDay->setValue(15);
                    $costForecastWeekDay->setDayOfWeek($day);
                    $costForecastWeekDay->setCategory($costType);

                    $manager->persist($costForecastWeekDay);
                }
            }
        }

        $manager->flush();
    }

    /**
     * @return int
     */
    public function getOrder()
    {
        return FixtureOrder::ESTIMATED_COSTS;
    }
}