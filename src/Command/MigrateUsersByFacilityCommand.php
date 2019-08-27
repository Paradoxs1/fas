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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class MigrateUsersByFacilityCommand
 * @package App\Command
 */
class MigrateUsersByFacilityCommand extends RASMigrateAbstractCommand
{
    /**
     * @var int
     */
    private $facilityId;

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
            ->setName('app:ras-user-migrate')
            ->setDescription('Migrate users by facility ID')
            ->addArgument(
                'facilityId',
                InputArgument::REQUIRED,
                'RAS facility ID'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws \Doctrine\DBAL\ConnectionException
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->facilityId = $input->getArgument('facilityId');

        $this->em->getConnection()->beginTransaction();
        try {
            $tenantUsers = $this->connection->fetchAll("SELECT users.id, login, password, last_login, firstname, lastname, phone, email, res_id, f_admin 
                FROM users 
                INNER JOIN backofficer_res ON users.id = backofficer_res.b_id 
                INNER JOIN backofficer ON backofficer.id = backofficer_res.b_id 
                WHERE f_deleted = 0 AND login != 'admin' AND backofficer_res.res_id = " . $this->facilityId);
            $this->populate($tenantUsers, self::$migrationData['tenant'], $output);

            $facilityUsers = $this->connection->fetchAll("SELECT users.id, login, password, last_login, firstname, lastname, phone, email, res_id, f_chef 
                FROM users 
                INNER JOIN cashier ON users.id = cashier.id 
                WHERE f_deleted = 0 AND login != 'admin' AND cashier.res_id = " . $this->facilityId);
            $this->populate($facilityUsers, self::$migrationData['facility'], $output);

            $stakeholderUsers = $this->connection->fetchAll("SELECT users.id, login, password, last_login, firstname, lastname, phone, email, res_id, f_subscribed 
                FROM users 
                INNER JOIN listener ON users.id = listener.user_id
                WHERE f_deleted = 0 AND login != 'admin' AND listener.res_id = " . $this->facilityId);
            $this->populate($stakeholderUsers, self::$migrationData['stakeholder'], $output);

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
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function populate(array $users = [], array $data, OutputInterface $output): void
    {
        if ($users) {
            $tenant = $this->em->getRepository(Tenant::class)->findOneByName('Bruderer Business Consulting GmbH');
            $roleRepository = $this->em->getRepository(Role::class);
            $accountRepository = $this->em->getRepository(Account::class);
            $facilityRepository = $this->em->getRepository(Facility::class);

            foreach ($users as $user) {
                $account = $accountRepository->findOneBy(['login' => $user['login']]);
                if (!$account) {
                    $account = new Account();
                    $account->setId($user['id']);
                    $account->setLogin($user['login']);
                    $account->setPasswordHash($user['password']);
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

                $accountFacility = new AccountFacilityRole();
                $facility = $facilityRepository->find($this->facilityId);

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
