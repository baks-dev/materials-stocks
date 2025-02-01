<?php

/*
 *  Copyright 2023-2024.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Materials\Stocks\UseCase\Admin\Incoming\Tests;

use BaksDev\Materials\Catalog\Type\Id\MaterialUid;
use BaksDev\Materials\Catalog\Type\Offers\ConstId\MaterialOfferConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\MaterialVariationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\ConstId\MaterialModificationConst;
use BaksDev\Materials\Stocks\Entity\Stock\MaterialStock;
use BaksDev\Materials\Stocks\Entity\Total\MaterialStockTotal;
use BaksDev\Materials\Stocks\Repository\CurrentMaterialStocks\CurrentMaterialStocksInterface;
use BaksDev\Materials\Stocks\Repository\MaterialWarehouseTotal\MaterialWarehouseTotalInterface;
use BaksDev\Materials\Stocks\Type\Id\MaterialStockUid;
use BaksDev\Materials\Stocks\Type\Status\MaterialStockStatus;
use BaksDev\Materials\Stocks\Type\Status\MaterialStockstatus\MaterialStockStatusCollection;
use BaksDev\Materials\Stocks\UseCase\Admin\Incoming\IncomingMaterialStockDTO;
use BaksDev\Materials\Stocks\UseCase\Admin\Incoming\IncomingMaterialStockHandler;
use BaksDev\Materials\Stocks\UseCase\Admin\Warehouse\Tests\WarehouseMaterialStockTest;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

/**
 * @group materials-stocks
 * @group materials-stocks-incoming
 *
 * @depends BaksDev\Materials\Stocks\UseCase\Admin\Warehouse\Tests\WarehouseMaterialStockTest::class
 * @see     WarehouseMaterialStockTest
 */
#[When(env: 'test')]
final class IncomingMaterialStockTest extends KernelTestCase
{
    public function testMaterialStockDTO(): void
    {
        /** @var MaterialStockStatusCollection $MaterialStockStatusCollection */

        $MaterialStockStatusCollection = self::getContainer()->get(MaterialStockStatusCollection::class);
        $MaterialStockStatusCollection->cases();

        /** @var CurrentMaterialStocksInterface $CurrentMaterialStocksInterface */
        $CurrentMaterialStocksInterface = self::getContainer()->get(CurrentMaterialStocksInterface::class);
        $MaterialStockEvent = $CurrentMaterialStocksInterface->getCurrentEvent(new MaterialStockUid());


        /** @var IncomingMaterialStockDTO $IncomingMaterialStockDTO */
        $IncomingMaterialStockDTO = $MaterialStockEvent->getDto(IncomingMaterialStockDTO::class);

        self::assertEquals('WarehouseComment', $IncomingMaterialStockDTO->getComment());
        $IncomingMaterialStockDTO->setComment('IncomingComment');

        self::assertInstanceOf(MaterialStockStatus::class, $IncomingMaterialStockDTO->getStatus());
        self::assertTrue($IncomingMaterialStockDTO->getStatus()->equals(MaterialStockstatus\MaterialStockStatusIncoming::class));

        self::assertCount(1, $IncomingMaterialStockDTO->getMaterial());


        $MaterialStockDTO = $IncomingMaterialStockDTO->getMaterial()->current();

        $MaterialUid = new ProductUid();
        self::assertTrue($MaterialUid->equals($MaterialStockDTO->getMaterial()));

        $MaterialOfferConst = new MaterialOfferConst();
        self::assertTrue($MaterialOfferConst->equals($MaterialStockDTO->getOffer()));

        $MaterialVariationConst = new MaterialVariationConst();
        self::assertTrue($MaterialVariationConst->equals($MaterialStockDTO->getVariation()));

        $MaterialModificationConst = new MaterialModificationConst();
        self::assertTrue($MaterialModificationConst->equals($MaterialStockDTO->getModification()));


        self::assertEquals(100, $MaterialStockDTO->getTotal());
        /** TODO: Временно блокируем изменение прихода */
        //$MaterialStockDTO->setTotal(200);


        /** @var IncomingMaterialStockHandler $IncomingMaterialStockHandler */
        $IncomingMaterialStockHandler = self::getContainer()->get(IncomingMaterialStockHandler::class);
        $handle = $IncomingMaterialStockHandler->handle($IncomingMaterialStockDTO);

        self::assertTrue(($handle instanceof MaterialStock), $handle.': Ошибка MaterialStock');


        /** @var MaterialWarehouseTotalInterface $MaterialWarehouseTotal */
        $em = self::getContainer()->get(EntityManagerInterface::class);

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

        self::assertNotNull($MaterialStockTotal);

        /** Общий остаток 200 */
        /** TODO: Временно блокируем изменение прихода */
        //self::assertEquals(200, $MaterialStockTotal->getTotal());
        self::assertEquals(100, $MaterialStockTotal->getTotal());


        self::assertEquals(0, $MaterialStockTotal->getReserve());

        $em->clear();
        //$em->close();

    }
}
