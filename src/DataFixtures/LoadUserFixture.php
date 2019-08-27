<?php

namespace App\DataFixtures;

use App\Entity\Account;
use App\Entity\Person;
use App\Entity\AccountEmail;
use App\Entity\AccountFacilityRole;
use App\Entity\Role;
use App\Entity\Tenant;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * Class LoadUserFixture
 * @package App\DataFixtures
 */
class LoadUserFixture extends Fixture implements FixtureInterface, OrderedFixtureInterface
{
    /**
     * @var UserPasswordEncoderInterface
     */
    private $encoder;

    /**
     * LoadUserFixture constructor.
     * @param UserPasswordEncoderInterface $encoder
     */
    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    /**
     * @param ObjectManager $manager
     * @throws \Exception
     */
    public function load(ObjectManager $manager)
    {
        $people = [
            'ts' => [
                'name' => 'Tim',
                'tenant' => 'Digio GmbH'
            ],
            'dv' => [
                'name' => 'Damien',
                'tenant' => 'Bruderer Business Consulting GmbH'
            ],
            'at' => [
                'name' => 'Artem',
                'tenant' => 'Digio GmbH'
            ],
            'ns' => [
                'name' => 'Nikolay',
                'tenant' => 'Bruderer Business Consulting GmbH'
            ],
        ];

        $roles = [
            'TenantManager',
            'TenantUser',
            'FacilityStakeholder',
            'FacilityManager',
            'FacilityUser'
        ];

        foreach ($people as $personKey => $data) {
            foreach ($roles as $roleName) {

                $account = new Account();
                $account->setLogin(strtolower($personKey.'+'.$roleName));
                $password = $this->encoder->encodePassword($account, 'asdfasdf');
                $account->setPasswordHash($password);

                $account->setPasswordResetToken('372cbd2684d5f9fd4661afc0da3d8bc5');
                $account->setPasswordRequestedAt(new \DateTimeImmutable());

                $email = new AccountEmail();
                $email->setEmail($personKey.'+'.$roleName.'@digio.ch');

                $account->setAccountEmail($email);

                if ('Admin' != $roleName) {
                    $account->setTenant($manager->getRepository(Tenant::class)->findOneBy(['name' => $data['tenant']]));
                }

                $person = new Person();
                $person->setFirstName($data['name']);
                $person->setLastName($roleName);
                $person->setTelephone('1234567890');

                $account->setPerson($person);

                $accountFacilityRole = new AccountFacilityRole();
                $accountFacilityRole->setAccount($account);
                $accountFacilityRole->setRole($manager->getRepository(Role::class)->findOneBy(['administrativeName' => $roleName]));

                if ('Admin' != $roleName) {
                    $accountFacilityRole->setFacility($this->getReference($data['name'] . '_facility'));
                }

                $this->setReference(strtolower($personKey.'+'.$roleName), $account);

                $manager->persist($accountFacilityRole);
                $manager->persist($account);
                $manager->persist($email);
                $manager->persist($person);

                /** @var Connection $connection */
                $connection = $manager->getConnection();
                $connection->exec('ALTER SEQUENCE account_id_seq RESTART WITH ' . intval($account->getId() + 1));
            }
        }

        $manager->flush();
    }

    /**
     * @return int
     */
    public function getOrder()
    {
        return FixtureOrder::USER;
    }
}
