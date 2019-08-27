<?php

namespace App\Tests\Controller;

use App\Entity\Account;
use App\Form\AccountRequestPasswordResetType;
use App\Form\Model\PasswordResetRequest;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\BrowserKit\Cookie;

/**
 * Class SecurityControllerTest
 * @package App\Tests
 */
class SecurityControllerTest extends WebTestCase
{
    private $client = null;

    public function setUp()
    {
        $this->client = static::createClient([
            'environment' => 'test'
        ]);
    }

    const PAGES_TO_TEST = [
        '/admin/users',
        '/admin/tenants',

        '/tenant/facilities/1/stakeholders',
        '/tenant/facilities/1/dashboard',
        '/tenant/facilities',

        '/tenant/users',

        '/tenant/configuration',

        '/tenant/facilities/1/reports',
        '/tenant/facilities/1/cashiers',
        '/tenant/facilities/1/statistics',

        //TODO: uncomment when Report task is done.
        //'/report',

        '/tenant'
    ];

    const USERS_TO_TEST = [
        'ROLE_ADMIN' => [
            'name' => 'admin',
            'allowedPages' => [
                '/admin/users',
                '/admin/tenants'
            ]
        ],
        'ROLE_TENANT_MANAGER' => [
            'name' => 'ts+tenantmanager',
            'allowedPages' => [
                '/tenant/facilities/1/stakeholders',
                '/tenant/facilities/1/dashboard',
                '/tenant/facilities',
                '/tenant/users',
                '/tenant/configuration',
                '/tenant',
            ]
        ],
        'ROLE_TENANT_USER' => [
            'name' => 'ts+tenantuser',
            'allowedPages' => [
                '/tenant/facilities/1/reports',
                '/tenant/facilities/1/cashiers',
                '/tenant/users',
                '/tenant/facilities/1/dashboard',
                '/tenant',
            ]
        ],
        'ROLE_FACILITY_STAKEHOLDER' => [
            'name' => 'ts+facilitystakeholder',
            'allowedPages' => [
                '/tenant/facilities/1/statistics',
                '/tenant/facilities/1/dashboard',
                '/tenant',
            ]
        ],
        'ROLE_FACILITY_MANAGER'     => [
            'name' => 'ts+facilitymanager',
            'allowedPages' => [
                '/report'
            ]
        ],
        'ROLE_FACILITY_USER'     => [
            'name' => 'ts+facilityuser',
            'allowedPages' => [
                '/report'
            ]
        ],
    ];

    const RIGHT_PASSWORD = 'asdfasdf';

    const WRONG_PASSWORD = 'asdfasdf_WRONG_PASSWORD';

    /**
     * Tests login and redirection after success.
     */
    public function testLoginSuccess()
    {
        $client = static::createClient([
            'environment' => 'test'
        ]);

        $client->followRedirects(true);

        if (self::USERS_TO_TEST) {
            foreach(self::USERS_TO_TEST as $role => $data) {
                $client->request(
                    'POST',
                    '/login',
                    [
                        '_username' => $data['name'],
                        '_password' => self::RIGHT_PASSWORD
                    ]
                );

                $this->assertEquals(200, $client->getResponse()->getStatusCode());

                //Checking Admin redirection
                if ('ROLE_ADMIN' == $role ) {
                    $this->assertEquals('/admin/users', $client->getRequest()->getPathInfo());
                }

                //Checking Tenant Manager and Tenant User redirection
                if (
                    'ROLE_TENANT_MANAGER' == $role  ||
                    'ROLE_TENANT_USER' == $role
                ) {
                    $this->assertEquals('/tenant', $client->getRequest()->getPathInfo());
                }

                //Checking Facility Stakeholder redirection
                if ('ROLE_FACILITY_STAKEHOLDER' == $role) {
                    $this->assertEquals(1, preg_match("/\/tenant\/facilities\/\d+\/statistics/", $client->getRequest()->getPathInfo()));
                }

                //Checking Tenant Manager and Tenant User redirection
//                if (
//                    'ROLE_FACILITY_MANAGER' == $role  ||
//                    'ROLE_FACILITY_USER' == $role
//                ) {
//                    $this->assertEquals('/report', $client->getRequest()->getPathInfo());
//                }
            }
        }
    }

    /**
     * Tests login failure and redirection after that.
     */
    public function testLoginFailure()
    {
        $client = static::createClient([
            'environment' => 'test'
        ]);

        $client->followRedirects(true);

        if (self::USERS_TO_TEST) {
            foreach(self::USERS_TO_TEST as $role => $data) {
                $client->request(
                    'POST',
                    '/login',
                    [
                        '_username' => $data['name'],
                        '_password' => self::WRONG_PASSWORD
                    ]
                );

                $this->assertEquals(200, $client->getResponse()->getStatusCode());
                $this->assertEquals('/login', $client->getRequest()->getPathInfo());
            }
        }
    }

    /**
     * Tests pages access.
     */
    public function testIndexRedirect()
    {
        $client = static::createClient([
            'environment' => 'test'
        ]);

        $client->followRedirects(true);

        if (self::USERS_TO_TEST) {
            foreach(self::USERS_TO_TEST as $role => $data) {
                $client->request(
                    'POST',
                    '/',
                    [
                        '_username' => $data['name'],
                        '_password' => self::WRONG_PASSWORD
                    ]
                );

                $this->assertEquals(200, $client->getResponse()->getStatusCode());
                $this->assertEquals('/login', $client->getRequest()->getPathInfo());
            }
        }
    }

    /**
     * Tests pages access.
     */
    public function testLogout()
    {
        $client = static::createClient([
            'environment' => 'test'
        ]);

        $client->followRedirects(true);

        if (self::USERS_TO_TEST) {
            foreach(self::USERS_TO_TEST as $role => $data) {
                $client->request(
                    'POST',
                    '/',
                    [
                        '_username' => $data['name'],
                        '_password' => self::RIGHT_PASSWORD
                    ]
                );

                $this->assertEquals(200, $client->getResponse()->getStatusCode());

                //Logging out
                $client->request('GET','/logout');

                $this->assertEquals(200, $client->getResponse()->getStatusCode());
                $this->assertEquals('/login', $client->getRequest()->getPathInfo());
            }
        }
    }

    /**
     * Tests pages access.
     */
    public function testPagesAcceccibilityByUsers()
    {
        $client = static::createClient([
            'environment' => 'test'
        ]);

        $client->followRedirects(true);

        if (self::USERS_TO_TEST) {
            foreach(self::USERS_TO_TEST as $role => $data) {

                //Log in
                $client->request(
                    'POST',
                    '/login',
                    [
                        '_username' => $data['name'],
                        '_password' => self::RIGHT_PASSWORD
                    ]
                );

                $this->assertEquals(200, $client->getResponse()->getStatusCode());

                //Test  pages
                if (self::PAGES_TO_TEST) {
                    foreach(self::PAGES_TO_TEST as $page) {

                        $client->request('GET', $page);

                        if (in_array($page, $data['allowedPages'])) {
                            $this->assertEquals(200, $client->getResponse()->getStatusCode());
                        }
                        else {
                            $this->assertEquals(403, $client->getResponse()->getStatusCode());
                        }
                    }
                }
            }
        }
    }

    public function testPasswordResetPageNotAllowedForLoggedUser()
    {
        $this->logIn();
        $this->client->request('GET', '/password/reset');
        $this->assertEquals(
            403,
            $this->client->getResponse()->getStatusCode()
        );
    }

    public function testPasswordSentPageNotAllowedForLoggedUser()
    {
        $this->logIn();
        $this->client->request('GET', '/password/sent');
        $this->assertEquals(
            403,
            $this->client->getResponse()->getStatusCode()
        );
    }

    public function testPasswordSetPageNotAllowedForLoggedUser()
    {
        $this->logIn();
        $this->client->request('GET', '/password/set/058a316c48353dbfbc66c90d97d90b52');
        $this->assertEquals(
            403,
            $this->client->getResponse()->getStatusCode()
        );
    }

    public function testPasswordExpiredPageNotAllowedForLoggedUser()
    {
        $this->logIn();
        $this->client->request('GET', '/password/expired');
        $this->assertEquals(
            403,
            $this->client->getResponse()->getStatusCode()
        );
    }

    public function testPasswordSetSuccessPageNotAllowedForLoggedUser()
    {
        $this->logIn();
        $this->client->request('GET', '/password/set-success');
        $this->assertEquals(
            403,
            $this->client->getResponse()->getStatusCode()
        );
    }

    public function testPasswordResetPageAllowedForLoggedUser()
    {
        $this->client->request('GET', '/password/reset');
        $this->assertEquals(
            200,
            $this->client->getResponse()->getStatusCode()
        );
    }

    public function testPasswordSentPageAllowedForLoggedUser()
    {
        $this->client->request('GET', '/password/sent');
        $this->assertEquals(
            200,
            $this->client->getResponse()->getStatusCode()
        );
    }

    //TODO: please add a Fixture so this test works.
//    public function testPasswordSetPageAllowedForLoggedUser()
//    {
//        $this->client->request('GET', '/password/set/058a316c48353dbfbc66c90d97d90b52');
//        $this->assertEquals(
//            200,
//            $this->client->getResponse()->getStatusCode()
//        );
//    }

    public function testPasswordExpiredPageAllowedForLoggedUser()
    {
        $this->client->request('GET', '/password/expired');
        $this->assertEquals(
            200,
            $this->client->getResponse()->getStatusCode()
        );
    }

    public function testPasswordSetSuccessPageAllowedForLoggedUser()
    {
        $this->client->request('GET', '/password/set-success');
        $this->assertEquals(
            200,
            $this->client->getResponse()->getStatusCode()
        );
    }

    private function logIn()
    {
        $session = $this->client->getContainer()->get('session');

        $firewallName = 'main';
        $firewallContext = 'main';

        $account = $this->client->getContainer()->get('doctrine')->getRepository(Account::class)->loadUserByUsername('ns+facilityuser');

        $token = new UsernamePasswordToken($account, null, $firewallName, $account->getRoles());
        $session->set('_security_' . $firewallContext, serialize($token));
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());
        $this->client->getCookieJar()->set($cookie);
    }
}
