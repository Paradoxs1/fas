<?php

namespace App\Command;

use App\Entity\Account;
use App\Entity\AccountEmail;
use App\Entity\AccountFacilityRole;
use App\Entity\AccountingCategory;
use App\Entity\Country;
use App\Entity\Currency;
use App\Entity\Person;
use App\Entity\Role;
use App\Entity\Tenant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * Class BaseDataInsertionCommand
 * @package App\Command
 */
class BaseDataInsertionCommand extends Command
{
    /**
     * @var ContainerBagInterface
     */
    private $params;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var UserPasswordEncoderInterface
     */
    protected $passwordEncoder;

    public function __construct(ContainerBagInterface $params, EntityManagerInterface $em, UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->params = $params;
        $this->em = $em;
        $this->passwordEncoder = $passwordEncoder;

        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('app:base-data-insert')
            ->setDescription('Insert base data (currencies, countries, roles, tenants, etc.)');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws \Doctrine\DBAL\ConnectionException
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->em->getConnection()->beginTransaction();
        try {
            $this->insertRoles($output);
            $this->insertCurrencies($output);
            $this->insertCountries($output);
            $this->insertAccountingCategory($output);
            $this->insertTenants($output);
            $this->insertAdminAccount($output);

            $this->em->getConnection()->commit();
            $output->writeln(['Finished successfully!']);
        } catch (\Exception $e) {
            $this->em->getConnection()->rollBack();
            $output->writeln([$e->getMessage()]);
        }
    }

    /**
     * @param OutputInterface $output
     * @return void
     */
    private function insertRoles(OutputInterface $output): void
    {
        $output->writeln(['Inserting roles']);

        $roles = [
            'Admin'               => ['internalName' => Role::ROLE_ADMIN, 'display_type' => 'checkbox'],
            'TenantManager'       => ['internalName' => Role::ROLE_TENANT_MANAGER, 'display_type' => 'checkbox'],
            'TenantUser'          => ['internalName' => Role::ROLE_TENANT_USER, 'display_type' => 'checkbox'],
            'FacilityStakeholder' => ['internalName' => Role::ROLE_FACILITY_STAKEHOLDER, 'display_type' => 'checkbox'],
            'FacilityManager'     => ['internalName' => Role::ROLE_FACILITY_MANAGER, 'display_type' => 'radio'],
            'FacilityUser'        => ['internalName' => Role::ROLE_FACILITY_USER, 'display_type' => 'radio']
        ];

        foreach ($roles as $administrativeName => $data) {
            $role = new Role();
            $role->setAdministrativeName($administrativeName);
            $role->setInternalName($data['internalName']);
            $role->setDisplayType($data['display_type']);
            $this->em->persist($role);
        }
        $this->em->flush();
    }

    /**
     * @param OutputInterface $output
     * @return void
     */
    private function insertCurrencies(OutputInterface $output): void
    {
        $output->writeln(['Inserting currencies']);

        $currency1 = new Currency();
        $currency1->setIsoCode('CHF');
        $currency1->setAdministrativeName('CHF');

        $currency2 = new Currency();
        $currency2->setIsoCode('EUR');
        $currency2->setAdministrativeName('€');

        $this->em->persist($currency1);
        $this->em->persist($currency2);

        $this->em->flush();
    }

    /**
     * @param OutputInterface $output
     * @return void
     */
    private function insertCountries(OutputInterface $output): void
    {
        $output->writeln(['Inserting countries']);

        $country1 = new Country();
        $country2 = new Country();

        $country1->translate('en')->setAdministrativeName('Switzerland');
        $country1->translate('de')->setAdministrativeName('Schweiz');
        $country2->translate('en')->setAdministrativeName('Germany');
        $country2->translate('de')->setAdministrativeName('Deutschland');

        $country1->setIsoCode('CHE');
        $country2->setIsoCode('DEU');

        $this->em->persist($country1);
        $this->em->persist($country2);

        $country1->mergeNewTranslations();
        $country2->mergeNewTranslations();

        $this->em->flush();
    }

    /**
     * @param OutputInterface $output
     * @return void
     */
    private function insertAccountingCategory(OutputInterface $output): void
    {
        $output->writeln(['Inserting accounting categories']);

        //Sales category
        $salesAccountingCategory = new AccountingCategory();
        $salesAccountingCategory->setKey('salesCategory');
        $salesAccountingCategory->setSequence(0);

        //Credit Card category
        $creditCardAccountingCategory = new AccountingCategory();
        $creditCardAccountingCategory->setKey('creditCard');
        $creditCardAccountingCategory->setSequence(1);

        //Voucher (Accepted) category
        $acceptedVoucherAccountingCategory = new AccountingCategory();
        $acceptedVoucherAccountingCategory->setKey('acceptedVoucher');
        $acceptedVoucherAccountingCategory->setSequence(2);

        //Voucher (Issued) category
        $issuedVoucherAccountingCategory = new AccountingCategory();
        $issuedVoucherAccountingCategory->setKey('issuedVoucher');
        $issuedVoucherAccountingCategory->setSequence(3);

        //Tip category
        $tipAccountingCategory = new AccountingCategory();
        $tipAccountingCategory->setKey('tip');
        $tipAccountingCategory->setSequence(4);

        //Bill category
        $billAccountingCategory = new AccountingCategory();
        $billAccountingCategory->setKey('bill');
        $billAccountingCategory->setSequence(5);

        //Expenses category
        $expensesAccountingCategory = new AccountingCategory();
        $expensesAccountingCategory->setKey('expenses');
        $expensesAccountingCategory->setSequence(6);

        //Cigarettes category
        $cigarettesAccountingCategory = new AccountingCategory();
        $cigarettesAccountingCategory->setKey('cigarettes');
        $cigarettesAccountingCategory->setSequence(7);

        //Cash category
        $cashAccountingCategory = new AccountingCategory();
        $cashAccountingCategory->setKey('cash');
        $cashAccountingCategory->setSequence(8);

        //Questions category
        $questionsAccountingCategory = new AccountingCategory();
        $questionsAccountingCategory->setKey('questions');
        $questionsAccountingCategory->setSequence(9);

        //Report related Accounting Categories

        //Comment category
        $commentAccountingCategory = new AccountingCategory();
        $commentAccountingCategory->setKey('comment');
        $commentAccountingCategory->setSequence(10);

        //TotalSales category
        $totalSalesAccountingCategory = new AccountingCategory();
        $totalSalesAccountingCategory->setKey('totalSales');
        $totalSalesAccountingCategory->setSequence(11);

        $this->em->persist($salesAccountingCategory);
        $this->em->persist($creditCardAccountingCategory);
        $this->em->persist($acceptedVoucherAccountingCategory);
        $this->em->persist($issuedVoucherAccountingCategory);
        $this->em->persist($tipAccountingCategory);
        $this->em->persist($billAccountingCategory);
        $this->em->persist($expensesAccountingCategory);
        $this->em->persist($cigarettesAccountingCategory);
        $this->em->persist($cashAccountingCategory);
        $this->em->persist($questionsAccountingCategory);

        //Report related
        $this->em->persist($commentAccountingCategory);
        $this->em->persist($totalSalesAccountingCategory);

        $this->em->flush();
    }

    /**
     * @param OutputInterface $output
     * @return void
     */
    private function insertTenants(OutputInterface $output): void
    {
        $output->writeln(['Inserting tenants']);

        $tenant1 = new Tenant();
        $tenant2 = new Tenant();

        $tenant1->setName('Digio GmbH');
        $tenant2->setName('Bruderer Business Consulting GmbH');

        $this->em->persist($tenant1);
        $this->em->persist($tenant2);
        $this->em->flush();
    }

    /**
     * @param OutputInterface $output
     * @return void
     * @throws \Exception
     */
    private function insertAdminAccount(OutputInterface $output): void
    {
        $output->writeln(['Inserting admin account']);

        $account = new Account();
        $account->setLogin('admin');
        $password = $this->passwordEncoder->encodePassword($account, 'asdfasdf');
        $account->setPasswordHash($password);
        $account->setCreatedAt();

        $person = new Person();
        $person->setLastName('Digio');
        $person->setFirstName('Admin');

        $accountEmail = new AccountEmail();
        $accountEmail->setEmail('ts@digio.ch');

        $account->setPerson($person);
        $account->setAccountEmail($accountEmail);

        $accountFacilityRole = new AccountFacilityRole();
        $accountFacilityRole->setAccount($account);
        $accountFacilityRole->setRole($this->em->getRepository(Role::class)->findOneBy(['internalName' => Role::ROLE_ADMIN]));

        $this->em->persist($accountFacilityRole);
        $this->em->persist($account);
        $this->em->flush();
    }
}
