<?php

namespace App\Command;

use App\Entity\Account;
use App\Entity\AccountFacilityRole;
use App\Entity\AccountingCategory;
use App\Entity\AccountingPosition;
use App\Entity\Facility;
use App\Entity\FacilityLayout;
use App\Entity\FlexParam;
use App\Entity\Person;
use App\Entity\Report;
use App\Entity\ReportPosition;
use App\Entity\ReportPositionValue;
use App\Entity\Role;
use App\Entity\Tenant;
use App\Service\FlexParamService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Id\AssignedGenerator;
use Doctrine\ORM\Id\IdentityGenerator;
use Doctrine\ORM\Id\SequenceGenerator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class MigrateReportsByFacilityCommand
 * @package App\Command
 */
class MigrateReportsByFacilityCommand extends RASMigrateAbstractCommand
{
    const MAX_ACCOUNT_ID = 300;

    /**
     * @var int
     */
    private $facilityId;

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('app:ras-reports-migrate')
            ->setDescription('Migrate reports by facility ID')
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
            $reports = $this->connection->fetchAll("SELECT id, date, sale_total, invoice_number FROM reports WHERE type = 'total' AND f_approved = 1 AND res_id = " . $this->facilityId);
            $this->populate($reports, $output);

            $this->em->getConnection()->commit();
            $output->writeln(['Finished successfully!']);
        } catch (\Exception $e) {
            $this->em->getConnection()->rollBack();
            $output->writeln([$e->getMessage()]);
        }
    }

    /**
     * @param array $reports
     * @param OutputInterface $output
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function populate(array $reports = [], OutputInterface $output): void
    {
        if ($reports) {
            $facilityRepository = $this->em->getRepository(Facility::class);
            $facilityLayoutRepository = $this->em->getRepository(FacilityLayout::class);
            $accountRepository = $this->em->getRepository(Account::class);
            $categoryRepository = $this->em->getRepository(AccountingCategory::class);
            $roleRepository = $this->em->getRepository(Role::class);
            /** @var Facility $facility */
            $facility = $facilityRepository->find($this->facilityId);
            /** @var FacilityLayout $facilityLayout */
            $facilityLayout = $facilityLayoutRepository->findOneBy(['facility' => $facility]);
            $tenant = $this->em->getRepository(Tenant::class)->findOneByName('Bruderer Business Consulting GmbH');

            if ($facility) {
                $category = $categoryRepository->findOneBy(['key' => FlexParamService::ACCOUNTING_CATEGORY_TOTAL_SALES_KEY]);
                $account = $accountRepository->findBy(['login' => $facility->getName()]);
                if (!$account) {
                    $account = $accountRepository->getMaxId();
                    $maxId = self::MAX_ACCOUNT_ID;

                    if ($account && $account->getId() >= self::MAX_ACCOUNT_ID) {
                        $maxId = $account->getId() + 1;
                    }

                    $accountingPosition = new AccountingPosition();
                    $accountingPosition->setAccountingCategory($category);
                    $accountingPosition->setCurrency($facilityLayout->getCurrency());
                    $accountingPosition->setSequence(1);
                    $accountingPosition->setFacilityLayout($facilityLayout);
                    $this->em->persist($accountingPosition);

                    $param = new FlexParam();
                    $param->setKey('value');
                    $param->setType('currency');
                    $param->setView('frontoffice');
                    $param->setSequence(1);
                    $param->setAccountingPosition($accountingPosition);
                    //$facilityLayout->addAccountingPosition($accountingPosition);
                    $this->em->persist($param);
                    //$this->em->persist($facilityLayout);

                    $account = new Account();
                    $account->setId($maxId);
                    $account->setLogin(sprintf('%s_migration', $facility->getName()));
                    $password = $this->passwordEncoder->encodePassword($account, 'Z]C$h8FQ9dPPaC8>W');
                    $account->setPasswordHash($password);
                    $account->setTenant($tenant);

                    $person = new Person();
                    $person->setFirstName($facility->getName());
                    $person->setLastName('Migration');
                    $account->setPerson($person);

                    $role = $roleRepository->findOneBy(['internalName' => 'ROLE_TENANT_USER']);
                    $accountFacilityRole = new AccountFacilityRole();
                    $accountFacilityRole->setRole($role);
                    $accountFacilityRole->setAccount($account);
                    $accountFacilityRole->setFacility($facility);
                    $account->addAccountFacilityRole($accountFacilityRole);

                    $role = $roleRepository->findOneBy(['internalName' => 'ROLE_TENANT_MANAGER']);
                    $accountFacilityRole = new AccountFacilityRole();
                    $accountFacilityRole->setRole($role);
                    $accountFacilityRole->setAccount($account);
                    $accountFacilityRole->setFacility($facility);
                    $account->addAccountFacilityRole($accountFacilityRole);

                    $metadata = $this->em->getClassMetaData(get_class($account));
                    $metadata->setIdGenerator(new AssignedGenerator());

                    $this->em->persist($account);
                    $this->em->flush();

                    /** @var Connection $connection */
                    $connection = $this->em->getConnection();
                    $connection->exec('ALTER SEQUENCE account_id_seq RESTART WITH ' . intval($account->getId() + 1));
                }

                foreach ($reports as $oldReport) {
                    $report = new Report();
                    $report->setId($oldReport['id']);
                    $report->setFacilityLayout($facilityLayout);
                    $report->setApproved(true);
                    $report->setNumber($oldReport['invoice_number']);
                    $report->setShifts(null);
                    $report->setCreatedBy($account);
                    $report->setType(Report::REPORT_TYPE_MIGRATION);
                    $report->setStatementDate(new \DateTimeImmutable($oldReport['date']));

                    $reportPosition = new ReportPosition();
                    $reportPosition->setAccountingPosition($accountingPosition);
                    $reportPosition->setCreatedBy($account);
                    $reportPosition->setReport($report);
                    $reportPosition->setCreatedAt(new \DateTimeImmutable($oldReport['date']));

                    $reportPositionValue = new ReportPositionValue();
                    $reportPositionValue->setReportPosition($reportPosition);
                    $reportPositionValue->setValue($oldReport['sale_total']);
                    $reportPositionValue->setParameter($param);
                    $reportPositionValue->setSequence(1);
                    $reportPositionValue->setCreatedAt(new \DateTimeImmutable($oldReport['date']));

                    $reportPosition->addReportPositionValue($reportPositionValue);
                    $report->addReportPosition($reportPosition);

                    $this->em->persist($reportPosition);
                    $this->em->persist($report);
                }

                $this->em->flush();
            }

        } else {
            $output->writeln(['No reports to migrate']);
        }
    }
}
