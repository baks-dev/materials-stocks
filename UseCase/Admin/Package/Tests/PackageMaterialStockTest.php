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

declare(strict_types=1);

namespace BaksDev\Materials\Stocks\UseCase\Admin\Package\Tests;

use BaksDev\Materials\Catalog\Type\Offers\ConstId\MaterialOfferConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\MaterialVariationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\ConstId\MaterialModificationConst;
use BaksDev\Materials\Stocks\Entity\Stock\Event\MaterialStockEvent;
use BaksDev\Materials\Stocks\Entity\Stock\MaterialStock;
use BaksDev\Materials\Stocks\Entity\Total\MaterialStockTotal;
use BaksDev\Materials\Stocks\Repository\MaterialWarehouseTotal\MaterialWarehouseTotalInterface;
use BaksDev\Materials\Stocks\Type\Id\MaterialStockUid;
use BaksDev\Materials\Stocks\Type\Status\MaterialStockStatus\MaterialStockStatusCollection;
use BaksDev\Materials\Stocks\UseCase\Admin\Incoming\Tests\IncomingMaterialStockTest;
use BaksDev\Materials\Stocks\UseCase\Admin\Package\Materials\MaterialStockDTO;
use BaksDev\Materials\Stocks\UseCase\Admin\Package\PackageMaterialStockDTO;
use BaksDev\Materials\Stocks\UseCase\Admin\Package\PackageMaterialStockHandler;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Products\Product\Type\Material\MaterialUid;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Users\User\Type\Id\UserUid;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

/**
 * @group materials-stocks
 * @group materials-stocks-package
 *
 * @depends \BaksDev\Materials\Stocks\UseCase\Admin\Incoming\Tests\IncomingMaterialStockTest::class
 * @see     IncomingMaterialStockTest
 */
#[When(env: 'test')]
final class PackageMaterialStockTest extends KernelTestCase
{
    public static function setUpBeforeClass(): void
    {
        /** @var MaterialStockStatusCollection $MaterialStockStatusCollection */

        $MaterialStockStatusCollection = self::getContainer()->get(MaterialStockStatusCollection::class);
        $MaterialStockStatusCollection->cases();

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $main = $em->getRepository(MaterialStock::class)
            ->findBy(['id' => MaterialStockUid::TEST]);

        foreach($main as $remove)
        {
            $em->remove($remove);
        }

        $event = $em->getRepository(MaterialStockEvent::class)
            ->findBy(['main' => MaterialStockUid::TEST]);

        foreach($event as $remove)
        {
            $em->remove($remove);
        }

        $em->flush();
    }

    /**
     * Тест создания заказа на упаковку
     */
    public function testUseCase(): void
    {

        $PackageMaterialStockDTO = new PackageMaterialStockDTO(new UserUid());

        $UserProfileUid = new UserProfileUid();
        $PackageMaterialStockDTO->setProfile($UserProfileUid);
        self::assertSame($UserProfileUid, $PackageMaterialStockDTO->getProfile());

        $PackageMaterialStockDTO->setNumber('Number');
        self::assertEquals('Number', $PackageMaterialStockDTO->getNumber());

        $MaterialStockOrderDTO = $PackageMaterialStockDTO->getOrd();

        $OrderUid = new OrderUid();
        $MaterialStockOrderDTO->setOrd($OrderUid);
        self::assertSame($OrderUid, $MaterialStockOrderDTO->getOrd());


        $PackageMaterialStockDTO->setComment('PackageComment');
        self::assertEquals('PackageComment', $PackageMaterialStockDTO->getComment());


        $MaterialStockDTO = new MaterialStockDTO();

        $MaterialUid = new MaterialUid();
        $MaterialStockDTO->setMaterial($MaterialUid);
        self::assertSame($MaterialUid, $MaterialStockDTO->getMaterial());

        $MaterialOfferConst = new MaterialOfferConst();
        $MaterialStockDTO->setOffer($MaterialOfferConst);
        self::assertSame($MaterialOfferConst, $MaterialStockDTO->getOffer());

        $MaterialVariationConst = new MaterialVariationConst();
        $MaterialStockDTO->setVariation($MaterialVariationConst);
        self::assertSame($MaterialVariationConst, $MaterialStockDTO->getVariation());

        $MaterialModificationConst = new MaterialModificationConst();
        $MaterialStockDTO->setModification($MaterialModificationConst);
        self::assertSame($MaterialModificationConst, $MaterialStockDTO->getModification());


        $MaterialStockDTO->setTotal(15);
        self::assertEquals(15, $MaterialStockDTO->getTotal());

        //$PackageOrderPriceDTO = new PackageOrderPriceDTO();
        //$PackageOrderPriceDTO->setTotal(123);
        //$MaterialStockDTO->setPrice($PackageOrderPriceDTO);

        //self::assertEquals(123, $PackageOrderPriceDTO->getTotal());


        $PackageMaterialStockDTO->addMaterial($MaterialStockDTO);
        self::assertCount(1, $PackageMaterialStockDTO->getMaterial());


        /** @var PackageMaterialStockHandler $PackageMaterialStockHandler */
        $PackageMaterialStockHandler = self::getContainer()->get(PackageMaterialStockHandler::class);
        $handle = $PackageMaterialStockHandler->handle($PackageMaterialStockDTO);

        self::assertTrue(($handle instanceof MaterialStock), $handle.': Ошибка MaterialStock');


        /** @var MaterialWarehouseTotalInterface $MaterialWarehouseTotal */
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->clear();

        /** @var MaterialStockTotal $MaterialStockTotal */
        $MaterialStockTotal = $em->getRepository(MaterialStockTotal::class)->findOneBy(
            [
                'profile' => new UserProfileUid(),
                'material' => $MaterialUid,
                'offer' => $MaterialOfferConst,
                'variation' => $MaterialVariationConst,
                'modification' => $MaterialModificationConst,
            ]
        );

        /** Общий остаток 200, резерв 123 */
        self::assertEquals(100, $MaterialStockTotal->getTotal());
        self::assertEquals(15, $MaterialStockTotal->getReserve());

        $em->clear();
        //$em->close();

    }
}
