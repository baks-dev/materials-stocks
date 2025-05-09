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

namespace BaksDev\Materials\Stocks\Repository\CurrentMaterialStocks;

use BaksDev\Core\Doctrine\ORMQueryBuilder;
use BaksDev\Materials\Stocks\Entity\Stock\Event\MaterialStockEvent;
use BaksDev\Materials\Stocks\Entity\Stock\MaterialStock;
use BaksDev\Materials\Stocks\Type\Id\MaterialStockUid;

final class CurrentMaterialStocksRepository implements CurrentMaterialStocksInterface
{
    public function __construct(private readonly ORMQueryBuilder $ORMQueryBuilder) {}

    /**
     * Метод возвращает активное событие
     */
    public function getCurrentEvent(MaterialStockUid $stock): ?MaterialStockEvent
    {
        $qb = $this->ORMQueryBuilder->createQueryBuilder(self::class);

        $qb
            ->from(MaterialStock::class, 'main')
            ->where('main.id = :stock')
            ->setParameter(
                key: 'stock',
                value: $stock,
                type: MaterialStockUid::TYPE
            );

        $qb
            ->select('event')
            ->leftJoin(
                MaterialStockEvent::class,
                'event',
                'WITH',
                'event.id = main.event'
            );

        return $qb->getOneOrNullResult();
    }
}
