<?php

namespace App\DataFixtures;

use App\Entity\AccountingCategory;
use App\Entity\AccountingPosition;
use App\Entity\Currency;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class LoadAccountingPositionFixture
 * @package App\DataFixtures
 */
class LoadAccountingPositionFixture extends Fixture implements FixtureInterface, OrderedFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $EUR_Currency = $manager->getRepository(Currency::class)->findOneBy(['isoCode' => 'EUR']);

        $facilityLayouts = [
            'TimFacilityLayout'     => $this->getReference('TimFacilityLayout'),
            'DamienFacilityLayout'  => $this->getReference('DamienFacilityLayout'),
            'ArtemFacilityLayout'   => $this->getReference('ArtemFacilityLayout'),
            'NikolayFacilityLayout' => $this->getReference('NikolayFacilityLayout'),
        ];

        foreach ($facilityLayouts as $key => $facilityLayout) {

            //Sales category

            $sequence = 1;

            foreach ([0, 1] as $i) {
                $salesAccountingPosition = new AccountingPosition();
                $salesAccountingPosition->setAccountingCategory($manager->getRepository(AccountingCategory::class)->findOneBy(['key' => 'salesCategory']));
                $salesAccountingPosition->setCurrency($EUR_Currency);
                $salesAccountingPosition->setSequence($sequence);
                $salesAccountingPosition->setFacilityLayout($facilityLayout);

                $this->setReference($key . '_salesAccountingPosition_' . $i, $salesAccountingPosition);

                $manager->persist($salesAccountingPosition);

                ++$sequence;
            }

            //Payment Methods - CreditCard

            $sequence = 1;

            foreach ([0, 1] as $i) {
                $creditCardAccountingPosition = new AccountingPosition();
                $creditCardAccountingPosition->setAccountingCategory($manager->getRepository(AccountingCategory::class)->findOneBy(['key' => 'creditCard']));
                $creditCardAccountingPosition->setCurrency($EUR_Currency);
                $creditCardAccountingPosition->setSequence($sequence);
                $creditCardAccountingPosition->setFacilityLayout($facilityLayout);

                $this->setReference($key . '_creditCardAccountingPosition_' . $i, $creditCardAccountingPosition);

                $manager->persist($creditCardAccountingPosition);

                ++$sequence;
            }

            //Payment Methods - Tip

            $sequence = 1;

            foreach ([0, 1] as $i) {
                $tipAccountingPosition = new AccountingPosition();
                $tipAccountingPosition->setAccountingCategory($manager->getRepository(AccountingCategory::class)->findOneBy(['key' => 'tip']));
                $tipAccountingPosition->setCurrency($EUR_Currency);
                $tipAccountingPosition->setSequence($sequence);
                $tipAccountingPosition->setFacilityLayout($facilityLayout);

                $this->setReference($key . '_tipAccountingPosition_' . $i, $tipAccountingPosition);

                $manager->persist($tipAccountingPosition);

                ++$sequence;
            }

            //Payment Methods - Cash - Only 1 Accounting Position needed

            $sequence = 1;

            $cashAccountingPosition = new AccountingPosition();
            $cashAccountingPosition->setAccountingCategory($manager->getRepository(AccountingCategory::class)->findOneBy(['key' => 'cash']));
            $cashAccountingPosition->setCurrency($EUR_Currency);
            $cashAccountingPosition->setSequence($sequence);
            $cashAccountingPosition->setFacilityLayout($facilityLayout);

            $this->setReference($key . '_cashAccountingPosition_' . 0, $cashAccountingPosition);

            $manager->persist($cashAccountingPosition);

            //Payment Methods - Cigarettes - Only 1 Accounting Position needed

            $sequence = 1;

            $cigarettesAccountingPosition = new AccountingPosition();
            $cigarettesAccountingPosition->setAccountingCategory($manager->getRepository(AccountingCategory::class)->findOneBy(['key' => 'cigarettes']));
            $cigarettesAccountingPosition->setCurrency($EUR_Currency);
            $cigarettesAccountingPosition->setSequence($sequence);
            $cigarettesAccountingPosition->setFacilityLayout($facilityLayout);

            $this->setReference($key . '_cigarettesAccountingPosition_' . 0, $cigarettesAccountingPosition);

            $manager->persist($cigarettesAccountingPosition);

            //Payment Methods - Expenses - Only 1 Accounting Position needed

            $sequence = 1;

            $expensesAccountingPosition = new AccountingPosition();
            $expensesAccountingPosition->setAccountingCategory($manager->getRepository(AccountingCategory::class)->findOneBy(['key' => 'expenses']));
            $expensesAccountingPosition->setCurrency($EUR_Currency);
            $expensesAccountingPosition->setSequence($sequence);
            $expensesAccountingPosition->setFacilityLayout($facilityLayout);

            $this->setReference($key . '_expensesAccountingPosition_' . 0, $expensesAccountingPosition);

            $manager->persist($expensesAccountingPosition);

            //Payment Methods - Accepted Voucher

            $sequence = 1;

            $acceptedVoucherAccountingPosition = new AccountingPosition();
            $acceptedVoucherAccountingPosition->setAccountingCategory($manager->getRepository(AccountingCategory::class)->findOneBy(['key' => 'acceptedVoucher']));
            $acceptedVoucherAccountingPosition->setCurrency($EUR_Currency);
            $acceptedVoucherAccountingPosition->setSequence($sequence);
            $acceptedVoucherAccountingPosition->setFacilityLayout($facilityLayout);

            $this->setReference($key . '_acceptedVoucherAccountingPosition_' . 0, $acceptedVoucherAccountingPosition);

            $manager->persist($acceptedVoucherAccountingPosition);

            //Payment Methods - Issued Voucher

            $issuedVoucherAccountingPosition = new AccountingPosition();
            $issuedVoucherAccountingPosition->setAccountingCategory($manager->getRepository(AccountingCategory::class)->findOneBy(['key' => 'issuedVoucher']));
            $issuedVoucherAccountingPosition->setCurrency($EUR_Currency);
            $issuedVoucherAccountingPosition->setSequence(++$sequence);
            $issuedVoucherAccountingPosition->setFacilityLayout($facilityLayout);

            $this->setReference($key . '_issuedVoucherAccountingPosition_' . 0, $issuedVoucherAccountingPosition);

            $manager->persist($issuedVoucherAccountingPosition);

            //Payment Methods - Bill

            //Bill (Default)

            $sequence = 1;

            $defaultBillAccountingPosition = new AccountingPosition();
            $defaultBillAccountingPosition->setAccountingCategory($manager->getRepository(AccountingCategory::class)->findOneBy(['key' => 'bill']));
            $defaultBillAccountingPosition->setCurrency($EUR_Currency);
            $defaultBillAccountingPosition->setSequence($sequence);
            $defaultBillAccountingPosition->setFacilityLayout($facilityLayout);
            $defaultBillAccountingPosition->setPredefined(1);

            $this->setReference($key . '_billAccountingPosition_' . 0, $defaultBillAccountingPosition);

            $manager->persist($defaultBillAccountingPosition);

            //Bill (Added)

            $addedBillAccountingPosition = new AccountingPosition();
            $addedBillAccountingPosition->setAccountingCategory($manager->getRepository(AccountingCategory::class)->findOneBy(['key' => 'bill']));
            $addedBillAccountingPosition->setCurrency($EUR_Currency);
            $addedBillAccountingPosition->setSequence(++$sequence);
            $addedBillAccountingPosition->setFacilityLayout($facilityLayout);

            $this->setReference($key . '_billAccountingPosition_' . 1, $addedBillAccountingPosition);

            $manager->persist($addedBillAccountingPosition);

            //Questions

            $sequence = 1;

            foreach ([0, 1] as $i) {
                $questionsAccountingPosition = new AccountingPosition();
                $questionsAccountingPosition->setAccountingCategory($manager->getRepository(AccountingCategory::class)->findOneBy(['key' => 'questions']));
                $questionsAccountingPosition->setCurrency($EUR_Currency);
                $questionsAccountingPosition->setSequence($sequence);
                $questionsAccountingPosition->setFacilityLayout($facilityLayout);

                $this->setReference($key . '_questionsAccountingPosition_' . $i, $questionsAccountingPosition);

                $manager->persist($questionsAccountingPosition);

                ++$sequence;
            }

            //Comment (Report Only Category)

            $categoryAccountingPosition = new AccountingPosition();
            $categoryAccountingPosition->setAccountingCategory($manager->getRepository(AccountingCategory::class)->findOneBy(['key' => 'comment']));
            $categoryAccountingPosition->setCurrency($EUR_Currency);
            $categoryAccountingPosition->setSequence(0);
            $categoryAccountingPosition->setFacilityLayout($facilityLayout);

            $this->setReference($key . '_commentAccountingPosition_' . 0, $categoryAccountingPosition);

            $manager->persist($categoryAccountingPosition);

            //TotalSales (Report Only Category)

            $totalSalesAccountingPosition = new AccountingPosition();
            $totalSalesAccountingPosition->setAccountingCategory($manager->getRepository(AccountingCategory::class)->findOneBy(['key' => 'totalSales']));
            $totalSalesAccountingPosition->setCurrency($EUR_Currency);
            $totalSalesAccountingPosition->setSequence(0);
            $totalSalesAccountingPosition->setFacilityLayout($facilityLayout);

            $this->setReference($key . '_totalSalesAccountingPosition_' . 1, $totalSalesAccountingPosition);

            $manager->persist($totalSalesAccountingPosition);
        }

        $manager->flush();
    }

    /**
     * @return int
     */
    public function getOrder()
    {
        return FixtureOrder::ACCOUNTING_POSITION;
    }
}