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

namespace BaksDev\Materials\Stocks\UseCase\Admin\Extradition\Tests;

use BaksDev\Materials\Stocks\Entity\Stock\MaterialStock;
use BaksDev\Materials\Stocks\Repository\CurrentMaterialStocks\CurrentMaterialStocksInterface;
use BaksDev\Materials\Stocks\Type\Id\MaterialStockUid;
use BaksDev\Materials\Stocks\Type\Status\MaterialStockStatus\Collection\MaterialStockStatusExtradition;
use BaksDev\Materials\Stocks\UseCase\Admin\Extradition\ExtraditionMaterialStockDTO;
use BaksDev\Materials\Stocks\UseCase\Admin\Extradition\ExtraditionMaterialStockHandler;
use BaksDev\Materials\Stocks\UseCase\Admin\Package\Tests\PackageMaterialStockTest;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use PHPUnit\Framework\Attributes\DependsOnClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[When(env: 'test')]
#[Group('materials-stocks')]
final class ExtraditionMaterialStockTest extends KernelTestCase
{
    /**
     * Тест упаковки заказа
     */
    #[DependsOnClass(PackageMaterialStockTest::class)]
    public function testMaterialStockDTO(): void
    {
        // Бросаем событие консольной комманды
        $dispatcher = self::getContainer()->get(EventDispatcherInterface::class);
        $event = new ConsoleCommandEvent(new Command(), new StringInput(''), new NullOutput());
        $dispatcher->dispatch($event, 'console.command');


        /** @var CurrentMaterialStocksInterface $CurrentMaterialStocksInterface */
        $CurrentMaterialStocksInterface = self::getContainer()->get(CurrentMaterialStocksInterface::class);
        $MaterialStockEvent = $CurrentMaterialStocksInterface->getCurrentEvent(new MaterialStockUid());


        /** @var ExtraditionMaterialStockDTO $ExtraditionMaterialStockDTO */
        $ExtraditionMaterialStockDTO = $MaterialStockEvent->getDto(ExtraditionMaterialStockDTO::class);
        self::assertEquals(UserProfileUid::TEST, $ExtraditionMaterialStockDTO->getProfile());
        self::assertTrue($ExtraditionMaterialStockDTO->getStatus()->equals(MaterialStockStatusExtradition::class));
        self::assertEquals('PackageComment', $ExtraditionMaterialStockDTO->getComment());
        $ExtraditionMaterialStockDTO->setComment('ExtraditionComment');


        /** @var ExtraditionMaterialStockHandler $ExtraditionMaterialStockHandler */
        $ExtraditionMaterialStockHandler = self::getContainer()->get(ExtraditionMaterialStockHandler::class);
        $handle = $ExtraditionMaterialStockHandler->handle($ExtraditionMaterialStockDTO);

        self::assertTrue(($handle instanceof MaterialStock), $handle.': Ошибка MaterialStock');

    }
}
