<?php

namespace App\DataFixtures;

use App\Entity\FlexParam;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class LoadFlexParamFixture
 * @package App\DataFixtures
 */
class LoadFlexParamFixture extends Fixture implements FixtureInterface, OrderedFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        //Facility Layout (configuration)

        //Configuration

        $facilityLayouts = [
            'TimFacilityLayout'     => $this->getReference('TimFacilityLayout'),
            'DamienFacilityLayout'  => $this->getReference('DamienFacilityLayout'),
            'ArtemFacilityLayout'   => $this->getReference('ArtemFacilityLayout'),
            'NikolayFacilityLayout' => $this->getReference('NikolayFacilityLayout'),
        ];

        foreach ($facilityLayouts as $key => $facilityLayout) {
            //Sales category (Flex Params for Sales)

            $paramData = [
                'Bier' => 'Atelier Bier',
                'Wine' => 'Atelier Wine'
            ];

            //Iterator

            $i = 0;

            foreach ($paramData as $name => $accountNo) {

                //Sales category (Accounting Position)

                $salesAccountingPosition = $this->getReference($key . '_salesAccountingPosition_' . $i);

                $salesCategoryFlexParam1 = new FlexParam();
                $salesCategoryFlexParam1->setKey('name');
                $salesCategoryFlexParam1->setType('text');
                $salesCategoryFlexParam1->setValue($name);
                $salesCategoryFlexParam1->setView('backoffice');
                $salesCategoryFlexParam1->setSequence(1);
                $salesCategoryFlexParam1->setAccountingPosition($salesAccountingPosition);

                $salesCategoryFlexParam2 = new FlexParam();
                $salesCategoryFlexParam2->setKey('value');
                $salesCategoryFlexParam2->setType('currency');
                $salesCategoryFlexParam2->setValue('');
                $salesCategoryFlexParam2->setView('frontoffice');
                $salesCategoryFlexParam2->setSequence(1);
                $salesCategoryFlexParam2->setAccountingPosition($salesAccountingPosition);

                //Sales category persist

                $manager->persist($salesCategoryFlexParam1);
                $manager->persist($salesCategoryFlexParam2);

                ++$i;
            }

            //----------------------------------------------------------------------

            //Sales category (Flex Params for Credit Card)

            $paramData = [
                'Mastercard' => '123456789',
                'Visa'       => '987654321'
            ];

            //Iterator

            $i = 0;

            foreach ($paramData as $name => $accountNo) {

                //Credit card (Accounting Position)

                $creditCardAccountingPosition = $this->getReference($key . '_creditCardAccountingPosition_' . $i);

                $creditCardFlexParam1 = new FlexParam();
                $creditCardFlexParam1->setKey('name');
                $creditCardFlexParam1->setType('text');
                $creditCardFlexParam1->setValue($name);
                $creditCardFlexParam1->setView('backoffice');
                $creditCardFlexParam1->setSequence(1);
                $creditCardFlexParam1->setAccountingPosition($creditCardAccountingPosition);

                $creditCardFlexParam2 = new FlexParam();
                $creditCardFlexParam2->setKey('value');
                $creditCardFlexParam2->setType('currency');
                $creditCardFlexParam2->setValue('');
                $creditCardFlexParam2->setView('frontoffice');
                $creditCardFlexParam2->setSequence(1);
                $creditCardFlexParam2->setAccountingPosition($creditCardAccountingPosition);

                //Credit card persist

                $manager->persist($creditCardFlexParam1);
                $manager->persist($creditCardFlexParam2);

                ++$i;
            }

            //----------------------------------------------------------------------

            //Tip (Flex Params for Tip)

            $paramData = [
                'Kitchen Tip' => '1.00',
                'Buffet Tip'  => '0.50'
            ];

            //Iterator

            $i = 0;

            foreach ($paramData as $name => $percentage) {

                //Tip (Accounting Position)

                $tipAccountingPosition = $this->getReference($key . '_tipAccountingPosition_' . $i);

                $tipFlexParam1 = new FlexParam();
                $tipFlexParam1->setKey('name');
                $tipFlexParam1->setType('text');
                $tipFlexParam1->setValue($name);
                $tipFlexParam1->setView('backoffice');
                $tipFlexParam1->setSequence(1);
                $tipFlexParam1->setAccountingPosition($tipAccountingPosition);

                $tipFlexParam2 = new FlexParam();
                $tipFlexParam2->setKey('tipInPercentage');
                $tipFlexParam2->setType('percentage');
                $tipFlexParam2->setValue($percentage);
                $tipFlexParam2->setView('backoffice');
                $tipFlexParam2->setSequence(2);
                $tipFlexParam2->setAccountingPosition($tipAccountingPosition);

                $tipFlexParam3 = new FlexParam();
                $tipFlexParam3->setKey('value');
                $tipFlexParam3->setType('currency');
                $tipFlexParam3->setValue('');
                $tipFlexParam3->setView('frontoffice');
                $tipFlexParam3->setSequence(1);
                $tipFlexParam3->setAccountingPosition($tipAccountingPosition);

                //Tip persist

                $manager->persist($tipFlexParam1);
                $manager->persist($tipFlexParam2);
                $manager->persist($tipFlexParam3);

                ++$i;
            }

            //----------------------------------------------------------------------

            //Cash (Accounting Position)

            $cashAccountingPosition = $this->getReference($key . '_cashAccountingPosition_' . 0);

            $cashFlexParam1 = new FlexParam();
            $cashFlexParam1->setKey('name');
            $cashFlexParam1->setType('label');
            $cashFlexParam1->setValue('Cash');
            $cashFlexParam1->setView('backoffice');
            $cashFlexParam1->setSequence(1);
            $cashFlexParam1->setAccountingPosition($cashAccountingPosition);

            $cashFlexParam2 = new FlexParam();
            $cashFlexParam2->setKey('amount');
            $cashFlexParam2->setType('label');
            $cashFlexParam2->setValue('');
            $cashFlexParam2->setView('frontoffice');
            $cashFlexParam2->setSequence(2);
            $cashFlexParam2->setAccountingPosition($cashAccountingPosition);

            $cashFlexParam3 = new FlexParam();
            $cashFlexParam3->setKey('cashier');
            $cashFlexParam3->setType('dropdown');
            $cashFlexParam3->setValue('');
            $cashFlexParam3->setView('frontoffice');
            $cashFlexParam3->setSequence(1);
            $cashFlexParam3->setAccountingPosition($cashAccountingPosition);

            //Cash persist

            $manager->persist($cashFlexParam1);
            $manager->persist($cashFlexParam2);
            $manager->persist($cashFlexParam3);

            //----------------------------------------------------------------------

            //Sigarettes (Accounting Position)

            $cigarettesAccountingPosition = $this->getReference($key . '_cigarettesAccountingPosition_' . 0);

            $cigarettesFlexParam1 = new FlexParam();
            $cigarettesFlexParam1->setKey('name');
            $cigarettesFlexParam1->setType('label');
            $cigarettesFlexParam1->setValue('Cigarettes');
            $cigarettesFlexParam1->setView('backoffice');
            $cigarettesFlexParam1->setSequence(1);
            $cigarettesFlexParam1->setAccountingPosition($cigarettesAccountingPosition);

            $cigarettesFlexParam2 = new FlexParam();
            $cigarettesFlexParam2->setKey('amount');
            $cigarettesFlexParam2->setType('currency');
            $cigarettesFlexParam2->setValue('');
            $cigarettesFlexParam2->setView('frontoffice');
            $cigarettesFlexParam2->setSequence(1);
            $cigarettesFlexParam2->setAccountingPosition($cigarettesAccountingPosition);

            //Sigarettes persist

            $manager->persist($cigarettesFlexParam1);
            $manager->persist($cigarettesFlexParam2);

            //----------------------------------------------------------------------

            //Expenses (Accounting Position)

            $expensesAccountingPosition = $this->getReference($key . '_expensesAccountingPosition_' . 0);

            $expensesFlexParam1 = new FlexParam();
            $expensesFlexParam1->setKey('name');
            $expensesFlexParam1->setType('label');
            $expensesFlexParam1->setValue('Expenses');
            $expensesFlexParam1->setView('backoffice');
            $expensesFlexParam1->setSequence(1);
            $expensesFlexParam1->setAccountingPosition($expensesAccountingPosition);

            $expensesFlexParam2 = new FlexParam();
            $expensesFlexParam2->setKey('name');
            $expensesFlexParam2->setType('text');
            $expensesFlexParam2->setValue('');
            $expensesFlexParam2->setView('frontoffice');
            $expensesFlexParam2->setSequence(1);
            $expensesFlexParam2->setAccountingPosition($expensesAccountingPosition);

            $expensesFlexParam3 = new FlexParam();
            $expensesFlexParam3->setKey('amount');
            $expensesFlexParam3->setType('currency');
            $expensesFlexParam3->setValue('');
            $expensesFlexParam3->setView('frontoffice');
            $expensesFlexParam3->setSequence(2);
            $expensesFlexParam3->setAccountingPosition($expensesAccountingPosition);

            $expensesFlexParam4 = new FlexParam();
            $expensesFlexParam4->setKey('catalogNumber');
            $expensesFlexParam4->setType('textfield');
            $expensesFlexParam4->setValue('');
            $expensesFlexParam4->setView('frontoffice');
            $expensesFlexParam4->setSequence(2);
            $expensesFlexParam4->setAccountingPosition($expensesAccountingPosition);

            //Expenses card persist

            $manager->persist($expensesFlexParam1);
            $manager->persist($expensesFlexParam2);
            $manager->persist($expensesFlexParam3);
            $manager->persist($expensesFlexParam4);

            //----------------------------------------------------------------------

            //Accepted Voucher (Accounting Position)

            $voucherAccountingPositionAccepted = $this->getReference($key . '_acceptedVoucherAccountingPosition_' . 0);
            $voucherAccountingPositionIssued   = $this->getReference($key . '_issuedVoucherAccountingPosition_' . 0);

            //Accepted Voucher

            $voucherFlexParam1 = new FlexParam();
            $voucherFlexParam1->setKey('name');
            $voucherFlexParam1->setType('label');
            $voucherFlexParam1->setValue('Accepted Voucher');
            $voucherFlexParam1->setView('backoffice');
            $voucherFlexParam1->setSequence(1);
            $voucherFlexParam1->setAccountingPosition($voucherAccountingPositionAccepted);

            $voucherFlexParam2 = new FlexParam();
            $voucherFlexParam2->setKey('number');
            $voucherFlexParam2->setType('text');
            $voucherFlexParam2->setValue('');
            $voucherFlexParam2->setView('frontoffice');
            $voucherFlexParam2->setSequence(1);
            $voucherFlexParam2->setAccountingPosition($voucherAccountingPositionAccepted);

            $voucherFlexParam3 = new FlexParam();
            $voucherFlexParam3->setKey('amount');
            $voucherFlexParam3->setType('currency');
            $voucherFlexParam3->setValue('');
            $voucherFlexParam3->setView('frontoffice');
            $voucherFlexParam3->setSequence(2);
            $voucherFlexParam3->setAccountingPosition($voucherAccountingPositionAccepted);

            //Issued Voucher

            $voucherFlexParam4 = new FlexParam();
            $voucherFlexParam4->setKey('name');
            $voucherFlexParam4->setType('label');
            $voucherFlexParam4->setValue('Issued Voucher');
            $voucherFlexParam4->setView('backoffice');
            $voucherFlexParam4->setSequence(1);
            $voucherFlexParam4->setAccountingPosition($voucherAccountingPositionIssued);

            $voucherFlexParam5 = new FlexParam();
            $voucherFlexParam5->setKey('addToTotalSalesAmount');
            $voucherFlexParam5->setType('checkbox');
            $voucherFlexParam5->setValue('');
            $voucherFlexParam5->setView('backoffice');
            $voucherFlexParam5->setSequence(2);
            $voucherFlexParam5->setAccountingPosition($voucherAccountingPositionIssued);

            $voucherFlexParam6 = new FlexParam();
            $voucherFlexParam6->setKey('number');
            $voucherFlexParam6->setType('text');
            $voucherFlexParam6->setValue('');
            $voucherFlexParam6->setView('frontoffice');
            $voucherFlexParam6->setSequence(1);
            $voucherFlexParam6->setAccountingPosition($voucherAccountingPositionIssued);

            $voucherFlexParam7 = new FlexParam();
            $voucherFlexParam7->setKey('amount');
            $voucherFlexParam7->setType('currency');
            $voucherFlexParam7->setValue('');
            $voucherFlexParam7->setView('frontoffice');
            $voucherFlexParam7->setSequence(2);
            $voucherFlexParam7->setAccountingPosition($voucherAccountingPositionIssued);

            //Accepted Voucher persist

            $manager->persist($voucherFlexParam1);
            $manager->persist($voucherFlexParam2);
            $manager->persist($voucherFlexParam3);
            $manager->persist($voucherFlexParam4);

            //Issued Voucher persist

            $manager->persist($voucherFlexParam5);
            $manager->persist($voucherFlexParam6);
            $manager->persist($voucherFlexParam7);

            //----------------------------------------------------------------------

            //Bill (Accounting Position)

            $defaultBillAccountingPosition = $this->getReference($key . '_billAccountingPosition_' . 0);
            $addedBillAccountingPosition   = $this->getReference($key . '_billAccountingPosition_' . 1);

            //Default Bill

            $billFlexParam1 = new FlexParam();
            $billFlexParam1->setKey('name');
            $billFlexParam1->setType('label');
            $billFlexParam1->setValue('Default Bill');
            $billFlexParam1->setView('backoffice');
            $billFlexParam1->setSequence(1);
            $billFlexParam1->setAccountingPosition($defaultBillAccountingPosition);

            // Flex Parameter (front) for both Default

            $billFlexParam2 = new FlexParam();
            $billFlexParam2->setKey('receiver');
            $billFlexParam2->setType('text');
            $billFlexParam2->setValue('');
            $billFlexParam2->setView('frontoffice');
            $billFlexParam2->setSequence(1);
            $billFlexParam2->setAccountingPosition($defaultBillAccountingPosition);

            $billFlexParam3 = new FlexParam();
            $billFlexParam3->setKey('amount');
            $billFlexParam3->setType('currency');
            $billFlexParam3->setValue('');
            $billFlexParam3->setView('frontoffice');
            $billFlexParam3->setSequence(2);
            $billFlexParam3->setAccountingPosition($defaultBillAccountingPosition);

            $billFlexParam4 = new FlexParam();
            $billFlexParam4->setKey('tip');
            $billFlexParam4->setType('currency');
            $billFlexParam4->setValue('');
            $billFlexParam4->setView('frontoffice');
            $billFlexParam4->setSequence(3);
            $billFlexParam4->setAccountingPosition($defaultBillAccountingPosition);

            $billFlexParam5 = new FlexParam();
            $billFlexParam5->setKey('billSelection');
            $billFlexParam5->setType('select');
            $billFlexParam5->setValue('');
            $billFlexParam5->setView('frontoffice');
            $billFlexParam5->setSequence(4);
            $billFlexParam5->setAccountingPosition($addedBillAccountingPosition);

            //Bill persist

            $manager->persist($billFlexParam1);
            $manager->persist($billFlexParam2);
            $manager->persist($billFlexParam3);
            $manager->persist($billFlexParam4);
            $manager->persist($billFlexParam5);

            //----------------------------------------------------------------------

            //Questions (Flex Params for Questions)

            $paramData = [
                'Question 1' => 'text',
                'Question 2' => 'number'
            ];

            //Iterator

            $i = 0;

            foreach ($paramData as $name => $answerType) {

                //Questions (Accounting Position)

                $questionAccountingPosition = $this->getReference($key . '_questionsAccountingPosition_' . $i);

                $questionFlexParam1 = new FlexParam();
                $questionFlexParam1->setKey('questionName');
                $questionFlexParam1->setType('text');
                $questionFlexParam1->setValue($name);
                $questionFlexParam1->setView('backoffice');
                $questionFlexParam1->setSequence(1);
                $questionFlexParam1->setAccountingPosition($questionAccountingPosition);

                $questionFlexParam2 = new FlexParam();
                $questionFlexParam2->setKey('answer');
                $questionFlexParam2->setType('text');
                $questionFlexParam2->setValue('');
                $questionFlexParam2->setView('frontoffice');
                $questionFlexParam2->setSequence(1);
                $questionFlexParam2->setAccountingPosition($questionAccountingPosition);

                //Questions persist

                $manager->persist($questionFlexParam1);
                $manager->persist($questionFlexParam2);

                ++$i;
            }

            //Comment (Accounting Position)

            $commentAccountingPosition = $this->getReference($key . '_commentAccountingPosition_' . 0);

            $commentFlexParam1 = new FlexParam();
            $commentFlexParam1->setKey('value');
            $commentFlexParam1->setType('textfield');
            $commentFlexParam1->setValue('');
            $commentFlexParam1->setView('frontoffice');
            $commentFlexParam1->setSequence(1);
            $commentFlexParam1->setAccountingPosition($commentAccountingPosition);

            $manager->persist($commentFlexParam1);

            //Total Sales (Accounting Position)

            $totalSalesAccountingPosition = $this->getReference($key . '_totalSalesAccountingPosition_' . 1);

            $totalSalesFlexParam1 = new FlexParam();
            $totalSalesFlexParam1->setKey('value');
            $totalSalesFlexParam1->setType('currency');
            $totalSalesFlexParam1->setValue('');
            $totalSalesFlexParam1->setView('frontoffice');
            $totalSalesFlexParam1->setSequence(1);
            $totalSalesFlexParam1->setAccountingPosition($totalSalesAccountingPosition);

            $manager->persist($totalSalesFlexParam1);
        }

        //Saving

        $manager->flush();
    }

    /**
     * @return int
     */
    public function getOrder()
    {
        return FixtureOrder::FLEX_PARAM;
    }
}
