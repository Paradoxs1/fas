<?php

namespace App\Command;

use App\Entity\Account;
use App\Entity\AccountEmail;
use App\Entity\AccountFacilityRole;
use App\Entity\Facility;
use App\Entity\Person;
use App\Entity\Role;
use App\Entity\Tenant;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Id\AssignedGenerator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class UsersMigrateCommand
 * @package App\Command
 */
class UsersMigrateCommand extends RASMigrateAbstractCommand
{
    /**
     * @var array
     */
    private static $migrationData = [
        'tenant' => [
            'userIdField'        => 'id',
            'facilityIdField'    => 'res_id',
            'roleIndicatorField' => 'f_admin',
            'managerRole'        => Role::ROLE_TENANT_MANAGER,
            'userRole'           => Role::ROLE_TENANT_USER
        ],
        'facility' => [
            'userIdField'        => 'id',
            'facilityIdField'    => 'res_id',
            'roleIndicatorField' => 'f_chef',
            'managerRole'        => Role::ROLE_FACILITY_MANAGER,
            'userRole'           => Role::ROLE_FACILITY_USER
        ],
        'stakeholder' => [
            'userIdField'        => 'user_id',
            'facilityIdField'    => 'res_id',
            'roleIndicatorField' => 'f_subscribed',
            'managerRole'        => Role::ROLE_FACILITY_STAKEHOLDER,
            'userRole'           => Role::ROLE_FACILITY_STAKEHOLDER
        ],
    ];

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('app:ras-users-migrate')
            ->setDescription('Migrate users, users-facilities');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws \Doctrine\DBAL\ConnectionException
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $output->writeln(['Migrating RAS users with facility-roles']);
        $this->em->getConnection()->beginTransaction();
        try {
            $users = $this->connection->fetchAll("SELECT id, login, password, last_login, firstname, lastname, phone, email FROM users WHERE f_deleted = 0 AND login != 'admin'");
            $tenant = $this->em->getRepository(Tenant::class)->findOneByName('Bruderer Business Consulting GmbH');
            $userIds = [];

            if ($users && $tenant) {
                foreach ($users as $user) {
                    $userIds[] = $user['id'];

                    $account = new Account();
                    $account->setId($user['id']);
                    $account->setLogin($user['login']);
                    $password = $this->passwordEncoder->encodePassword($account, 'asdfasdf');
                    $account->setPasswordHash($password);
                    $account->setLatestLoginAt($user['last_login']);
                    $account->setTenant($tenant);

                    $person = new Person();
                    $person->setFirstName($user['firstname']);
                    $person->setLastName($user['lastname']);
                    $person->setTelephone($user['phone']);

                    $accountEmail = new AccountEmail();
                    $accountEmail->setEmail($user['email']);

                    $account->setPerson($person);
                    $account->setAccountEmail($accountEmail);

                    $metadata = $this->em->getClassMetaData(get_class($account));
                    $metadata->setIdGenerator(new AssignedGenerator());

                    $this->em->persist($account);
                    $this->em->flush();

                    /** @var Connection $connection */
                    $connection = $this->em->getConnection();
                    $connection->exec('ALTER SEQUENCE account_id_seq RESTART WITH ' . intval($account->getId() + 1));
                }
            } else {
                $output->writeln(['No users to migrate']);
            }

            if ($userIds) {
                $tenantUsers = $this->connection->fetchAll('SELECT id, res_id, f_admin FROM backofficer_res INNER JOIN backofficer ON backofficer_res.b_id = backofficer.id WHERE backofficer.id IN (' . implode(',', $userIds) . ')');
                $this->populateAccountFacilityRoles($tenantUsers, self::$migrationData['tenant'], $output);

                $facilityUsers = $this->connection->fetchAll('SELECT * FROM cashier WHERE cashier.id IN (' . implode(',', $userIds) . ')');
                $this->populateAccountFacilityRoles($facilityUsers, self::$migrationData['facility'], $output);

                $stakeholderUsers = $this->connection->fetchAll('SELECT * FROM listener WHERE listener.user_id IN (' . implode(',', $userIds) . ')');
                $this->populateAccountFacilityRoles($stakeholderUsers, self::$migrationData['stakeholder'], $output);
            }
            $this->em->getConnection()->commit();
            $output->writeln(['Finished successfully!']);
        } catch (\Exception $e) {
            $this->em->getConnection()->rollBack();
            $output->writeln([$e->getMessage()]);
        }
    }

    /**
     * @param array $users
     * @param array $data
     * @param OutputInterface $output
     * @return void
     */
    private function populateAccountFacilityRoles(array $users = [], array $data, OutputInterface $output): void
    {
        if ($users) {
            $roleRepository = $this->em->getRepository(Role::class);
            $accountRepository = $this->em->getRepository(Account::class);
            $facilityRepository = $this->em->getRepository(Facility::class);

            foreach ($users as $user) {
                $accountFacility = new AccountFacilityRole();
                $account = $accountRepository->find($user[$data['userIdField']]);
                $facility = $facilityRepository->find($user[$data['facilityIdField']]);

                if ($account && $facility) {
                    $accountFacility->setAccount($account);
                    $accountFacility->setFacility($facility);
                    if ($user[$data['roleIndicatorField']]) {
                        $role = $roleRepository->findOneBy(['internalName' => $data['managerRole']]);
                    } else {
                        $role = $roleRepository->findOneBy(['internalName' => $data['userRole']]);
                    }
                    $accountFacility->setRole($role);

                    $this->em->persist($accountFacility);
                    $this->em->flush();
                } else {
                    $output->writeln(['Account or facility not found']);
                }
            }
        } else {
            $output->writeln(['No users with roles (' . $data['managerRole'] . ' ' . $data['userRole'] . ') to migrate']);
        }
    }
}
