<?php
/*
 *  Copyright 2024.  Baks.dev <admin@baks.dev>
 *  
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is furnished
 *  to do so, subject to the following conditions:
 *  
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *  
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 */

namespace BaksDev\Materials\Stocks\Controller\Admin\Warehouse\Tests;

use BaksDev\Materials\Stocks\Type\Event\MaterialStockEventUid;
use BaksDev\Users\User\Tests\TestUserAccount;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

/**
 * @group materials-stocks
 *
 * @depends BaksDev\Materials\Stocks\UseCase\Admin\Warehouse\Tests\WarehouseMaterialStockTest::class
 * @see     WarehouseMaterialStockTest
 */
#[When(env: 'test')]
final class WarehouseAdminControllerTest extends WebTestCase
{
    private const string URL = '/admin/material/stock/warehouse/%s';

    private const string ROLE = 'ROLE_MATERIAL_STOCK_WAREHOUSE_SEND';

    //    private static ?MaterialStockEventUid $identifier;

    //    public static function setUpBeforeClass(): void
    //    {
    //        $em = self::getContainer()->get(EntityManagerInterface::class);
    //        self::$identifier = $em->getRepository(MaterialStock::class)->findOneBy([], ['id' => 'DESC'])?->getEvent();
    //    }

    /** Доступ по роли */
    public function testRoleSuccessful(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        $usr = TestUserAccount::getModer(self::ROLE);

        $client->loginUser($usr, 'user');
        $client->request('GET', sprintf(self::URL, MaterialStockEventUid::TEST));

        self::assertResponseIsSuccessful();

    }

    // доступ по роли ROLE_ADMIN
    public function testRoleAdminSuccessful(): void
    {

        self::ensureKernelShutdown();
        $client = static::createClient();

        $usr = TestUserAccount::getAdmin();

        $client->loginUser($usr, 'user');
        $client->request('GET', sprintf(self::URL, MaterialStockEventUid::TEST));

        self::assertResponseIsSuccessful();

    }

    // Закрытый доступ по роли ROLE_USER
    public function testRoleUserDeny(): void
    {

        self::ensureKernelShutdown();
        $client = static::createClient();

        $usr = TestUserAccount::getUsr();

        $client->loginUser($usr, 'user');
        $client->request('GET', sprintf(self::URL, MaterialStockEventUid::TEST));

        self::assertResponseStatusCodeSame(403);

    }

    /** Закрытый Доступ по без роли */
    public function testGuestFiled(): void
    {

        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->request('GET', sprintf(self::URL, MaterialStockEventUid::TEST));

        // Full authentication is required to access this resource
        self::assertResponseStatusCodeSame(401);

    }
}
