<?php

namespace App\Tests\Repository;

use App\Entity\Account;
use App\Repository\AccountRepository;
use PHPUnit\Framework\TestCase;


class AccountRepositoryTest extends TestCase
{
    public function testLoadUserByUsername()
    {
        $username = 'test_user';
        $account = new Account();
        $account->setLogin($username);

        $accountRepository = $this->createMock(AccountRepository::class);
        $accountRepository
            ->expects($this->any())
            ->method('loadUserByUsername')
            ->willReturn($account);
    }
}
