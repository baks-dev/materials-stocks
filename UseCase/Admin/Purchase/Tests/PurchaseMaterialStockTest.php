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

namespace BaksDev\Materials\Stocks\UseCase\Admin\Purchase\Tests;

use BaksDev\Materials\Catalog\Type\Id\MaterialUid;
use BaksDev\Materials\Catalog\Type\Offers\ConstId\MaterialOfferConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\MaterialVariationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\ConstId\MaterialModificationConst;
use BaksDev\Materials\Stocks\Entity\Stock\Event\MaterialStockEvent;
use BaksDev\Materials\Stocks\Entity\Stock\MaterialStock;
use BaksDev\Materials\Stocks\Entity\Total\MaterialStockTotal;
use BaksDev\Materials\Stocks\Type\Id\MaterialStockUid;
use BaksDev\Materials\Stocks\Type\Status\MaterialStockstatus\Collection\MaterialStockStatusPurchase;
use BaksDev\Materials\Stocks\UseCase\Admin\Purchase\Materials\MaterialStockDTO;
use BaksDev\Materials\Stocks\UseCase\Admin\Purchase\PurchaseMaterialStockDTO;
use BaksDev\Materials\Stocks\UseCase\Admin\Purchase\PurchaseMaterialStockHandler;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

/**
 * @group materials-stocks
 * @group materials-stocks-purchase
 */
#[When(env: 'test')]
final class PurchaseMaterialStockTest extends KernelTestCase
{

    public static function setUpBeforeClass(): void
    {
        $MaterialStockStatus = new MaterialStockStatusPurchase();

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

        $total = $em->getRepository(MaterialStockTotal::class)
            ->findBy(['profile' => UserProfileUid::TEST]);

        foreach($total as $remove)
        {
            $em->remove($remove);
        }

        $em->flush();

        $em->clear();

    }


    /**
     * Тест нового закупочного листа
     */
    public function testUseCase(): void
    {
        $PurchaseMaterialStockDTO = new PurchaseMaterialStockDTO();
        $PurchaseMaterialStockDTO->setProfile(clone new UserProfileUid());

        $PurchaseMaterialStockDTO->setComment('Comment');
        self::assertEquals('Comment', $PurchaseMaterialStockDTO->getComment());

        $PurchaseMaterialStockDTO->setNumber('Number');
        self::assertEquals('Number', $PurchaseMaterialStockDTO->getNumber());

        $MaterialStockDTO = new MaterialStockDTO();

        $MaterialUid = new ProductUid();
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

        $MaterialStockDTO->setTotal(100);
        self::assertEquals(100, $MaterialStockDTO->getTotal());

        $PurchaseMaterialStockDTO->addMaterial($MaterialStockDTO);
        self::assertCount(1, $PurchaseMaterialStockDTO->getMaterial());


        $MaterialStockDTO = new MaterialStockDTO();
        $MaterialStockDTO->setMaterial(clone $MaterialUid);
        $MaterialStockDTO->setOffer(clone $MaterialOfferConst);
        $MaterialStockDTO->setVariation(clone $MaterialVariationConst);
        $MaterialStockDTO->setModification(clone $MaterialModificationConst);
        $MaterialStockDTO->setTotal(200);

        $PurchaseMaterialStockDTO->addMaterial($MaterialStockDTO);
        self::assertCount(2, $PurchaseMaterialStockDTO->getMaterial());

        $PurchaseMaterialStockDTO->removeMaterial($MaterialStockDTO);
        self::assertCount(1, $PurchaseMaterialStockDTO->getMaterial());

        /** @var PurchaseMaterialStockHandler $PurchaseMaterialStockHandler */
        $PurchaseMaterialStockHandler = self::getContainer()->get(PurchaseMaterialStockHandler::class);
        $handle = $PurchaseMaterialStockHandler->handle($PurchaseMaterialStockDTO);

        self::assertTrue(($handle instanceof MaterialStock), $handle.': Ошибка MaterialStock');


    }
}
