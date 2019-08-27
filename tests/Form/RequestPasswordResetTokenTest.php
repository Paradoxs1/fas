<?php

namespace App\Tests\Form;

use App\Entity\Account;
use App\Form\AccountRequestPasswordResetType;
use App\Form\Model\PasswordResetRequest;
use App\Repository\AccountRepository;
use Symfony\Component\Form\Test\TypeTestCase;

class RequestPasswordResetTokenTest extends TypeTestCase
{
    public function testRequestPasswordResetTokenFormTypeSubmitAndValid()
    {
        $formData = [
            'username' => 'ns+facilityuser',
        ];

        $passwordReset = new PasswordResetRequest();
        $form = $this->factory->create(AccountRequestPasswordResetType::class, $passwordReset);

        $passwordReset2 = new PasswordResetRequest();
        $passwordReset2->setUsername('ns+facilityuser');

        $form->submit($formData);

        $this->assertEquals($form->isValid(), true);

        $this->assertTrue($form->isSynchronized());

        $this->assertEquals($passwordReset2, $form->getData());

        $view = $form->createView();
        $children = $view->children;

        foreach (array_keys($formData) as $key) {
            $this->assertArrayHasKey($key, $children);
        }
    }

    public function testRequestPasswordResetTokenFindUser()
    {
        $formData = [
            'username' => 'ns+facilityuser',
        ];

        $account = new Account();
        $account->setLogin('ns+facilityuser');

        $passwordReset = new PasswordResetRequest();
        $form = $this->factory->create(AccountRequestPasswordResetType::class, $passwordReset);

        $form->submit($formData);

        $accountRepository = $this->createMock(AccountRepository::class);
        $accountRepository
            ->expects($this->any())
            ->method('findUserByLogin')
            ->willReturn($account);

        $this->assertEquals(
            $form->getData()->getUsername(),
            $account->getLogin()
        );
    }
}