<?php
/*
 *  Copyright 2022.  Baks.dev <admin@baks.dev>
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *  http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *   limitations under the License.
 *
 */

namespace BaksDev\Materials\Stocks\Controller\Admin\Warehouse\Tests;

use BaksDev\Users\User\Tests\TestUserAccount;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

/** @group materials-stocks */
#[When(env: 'test')]
final class IndexAdminControllerTest extends WebTestCase
{
    private const string URL = '/admin/material/stocks/warehouse';
    private const string ROLE = 'ROLE_MATERIAL_STOCK_WAREHOUSE';

    /** Доступ по роли ROLE_MATERIAL */
    public function testRoleSuccessful(): void
    {
        $client = static::createClient();

        $usr = TestUserAccount::getModer(self::ROLE);

        $client->loginUser($usr, 'user');
        $client->request('GET', self::URL);

        self::assertResponseIsSuccessful();

    }

    /** Доступ по роли ROLE_ADMIN */
    public function testRoleAdminSuccessful(): void
    {
        $client = static::createClient();

        $usr = TestUserAccount::getAdmin();

        $client->loginUser($usr, 'user');
        $client->request('GET', self::URL);

        self::assertResponseIsSuccessful();
    }

    /** Доступ по роли ROLE_USER */
    public function testRoleUserFiled(): void
    {
        $client = static::createClient();

        $usr = TestUserAccount::getUsr();
        $client->loginUser($usr, 'user');
        $client->request('GET', self::URL);

        self::assertResponseStatusCodeSame(403);
    }

    /** Доступ по без роли */
    public function testGuestFiled(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->request('GET', self::URL);

        // Full authentication is required to access this resource
        self::assertResponseStatusCodeSame(401);
    }
}
