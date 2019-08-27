<?php

namespace App\DataFixtures;

use App\Entity\Facility;
use App\Entity\Routine;
use App\Entity\RoutineTemplate;
use App\Entity\Tenant;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;

/**
 * Class LoadFacilityFixture
 * @package App\DataFixtures
 */
class LoadFacilityFixture extends Fixture implements FixtureInterface, OrderedFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    private $manager;

    /**
     * @param RoutineTemplate $routineTemplate
     */
    private $routineTemplate;

    /**
     * @param ObjectManager $manager
     * @throws \Exception
     */
    public function load(ObjectManager $manager)
    {
        $this->manager = $manager;
        $this->routineTemplate = $manager->getRepository(RoutineTemplate::class)->findOneBy(['name' => 'DefaultRoutine']);

        $tenantDigio = $manager->getRepository(Tenant::class)->findOneBy(['name' => 'Digio GmbH']);
        $tenantBBC = $manager->getRepository(Tenant::class)->findOneBy(['name' => 'Bruderer Business Consulting GmbH']);

        $address1 = $this->getReference('Address_1');
        $address2 = $this->getReference('Address_2');
        $address3 = $this->getReference('Address_3');
        $address4 = $this->getReference('Address_4');

        $TimsBar = new Facility();
        $TimsBar->setName("Tim's Bar");
        $TimsBar->setType('gastronomy');
        $TimsBar->setTenant($tenantDigio);
        $TimsBar->setCreatedAt(new \DateTimeImmutable());
        $TimsBar->setModifiedAt(new \DateTimeImmutable());
        $TimsBar->setAddress($address1);
        $TimsBar->setRoutine($this->addRoutine());

        $DamiensRestaurant = new Facility();
        $DamiensRestaurant->setName("Damien's Restaurant");
        $DamiensRestaurant->setType('gastronomy');
        $DamiensRestaurant->setTenant($tenantBBC);
        $DamiensRestaurant->setCreatedAt(new \DateTimeImmutable());
        $DamiensRestaurant->setModifiedAt(new \DateTimeImmutable());
        $DamiensRestaurant->setAddress($address2);
        $DamiensRestaurant->setRoutine($this->addRoutine());

        $ArtemsBar = new Facility();
        $ArtemsBar->setName("Artem's Bar");
        $ArtemsBar->setType('gastronomy');
        $ArtemsBar->setTenant($tenantDigio);
        $ArtemsBar->setCreatedAt(new \DateTimeImmutable());
        $ArtemsBar->setModifiedAt(new \DateTimeImmutable());
        $ArtemsBar->setAddress($address3);
        $ArtemsBar->setRoutine($this->addRoutine());

        $NikolaysRestaurant = new Facility();
        $NikolaysRestaurant->setName("Nikolay's Restaurant");
        $NikolaysRestaurant->setType('gastronomy');
        $NikolaysRestaurant->setTenant($tenantBBC);
        $NikolaysRestaurant->setCreatedAt(new \DateTimeImmutable());
        $NikolaysRestaurant->setModifiedAt(new \DateTimeImmutable());
        $NikolaysRestaurant->setAddress($address4);
        $NikolaysRestaurant->setRoutine($this->addRoutine());

        $this->setReference('Tim_facility', $TimsBar);
        $this->setReference('Damien_facility', $DamiensRestaurant);
        $this->setReference('Artem_facility', $ArtemsBar);
        $this->setReference('Nikolay_facility', $NikolaysRestaurant);

        $manager->persist($TimsBar);
        $manager->persist($DamiensRestaurant);
        $manager->persist($ArtemsBar);
        $manager->persist($NikolaysRestaurant);

        $manager->flush();

        /** @var Connection $connection */
        $connection = $manager->getConnection();
        $connection->exec('ALTER SEQUENCE facility_id_seq RESTART WITH ' . intval($NikolaysRestaurant->getId() + 1));
    }

    /**
     * @return Routine
     */
    private function addRoutine(): Routine
    {
        $routine = new Routine();
        $routine->setRoutineTemplate($this->routineTemplate);
        $routine->setName($this->routineTemplate->getName());
        $routine->setParams($this->routineTemplate->getParamTemplate());
        $this->manager->persist($routine);

        return $routine;
    }

    /**
     * @return int
     */
    public function getOrder()
    {
        return FixtureOrder::FACILITY;
    }
}
