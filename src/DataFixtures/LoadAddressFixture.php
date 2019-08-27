<?php

namespace App\DataFixtures;

use App\Entity\Country;
use App\Entity\Address;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class LoadAddressFixture
 * @package App\DataFixtures
 */
class LoadAddressFixture extends Fixture implements FixtureInterface, OrderedFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $address1 = new Address();
        $address2 = new Address();
        $address3 = new Address();
        $address4 = new Address();

        $address1->setCity('Wallisellen');
        $address2->setCity('Rüschlikon');
        $address3->setCity('Wallisellen');
        $address4->setCity('Rüschlikon');

        $address1->setStreet('Zwickystrasse 7');
        $address2->setStreet('Langhaldenstrasse 15');
        $address3->setStreet('Zwickystrasse 7');
        $address4->setStreet('Langhaldenstrasse 15');

        $address1->setZip('8304');
        $address2->setZip('8803');
        $address3->setZip('8304');
        $address4->setZip('8803');

        $switzerlandReference = $manager->getRepository(Country::class)->findOneBy(['isoCode' => 'CHE']);
        $germanyReference = $manager->getRepository(Country::class)->findOneBy(['isoCode' => 'DEU']);

        $address1->setCountry($switzerlandReference);
        $address2->setCountry($germanyReference);
        $address3->setCountry($switzerlandReference);
        $address4->setCountry($germanyReference);

        $this->setReference('Address_1', $address1);
        $this->setReference('Address_2', $address2);
        $this->setReference('Address_3', $address3);
        $this->setReference('Address_4', $address4);

        $manager->persist($address1);
        $manager->persist($address2);
        $manager->persist($address3);
        $manager->persist($address4);

        $manager->flush();
    }

    /**
     * @return int
     */
    public function getOrder()
    {
        return FixtureOrder::ADDRESS;
    }
}