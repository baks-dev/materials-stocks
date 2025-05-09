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

namespace BaksDev\Materials\Stocks\Repository\MaterialStockByNumber;

use BaksDev\Core\Doctrine\ORMQueryBuilder;
use BaksDev\Materials\Stocks\Entity\Stock\Event\MaterialStockEvent;
use BaksDev\Materials\Stocks\Entity\Stock\Invariable\MaterialStocksInvariable;
use BaksDev\Materials\Stocks\Entity\Stock\MaterialStock;


final readonly class MaterialStockByNumberRepository implements MaterialStockByNumberInterface
{
    public function __construct(private ORMQueryBuilder $ORMQueryBuilder) {}

    /**
     * Метод возвращает складскую заявку номеру
     */
    public function find(string $number): MaterialStockEvent|false
    {
        $qb = $this->ORMQueryBuilder->createQueryBuilder(self::class);

        $qb
            ->from(MaterialStocksInvariable::class, 'invariable')
            ->where('invariable.number = :number')
            ->setParameter(
                key: 'number',
                value: $number
            );

        $qb->join(
            MaterialStock::class,
            'stock',
            'WITH',
            'stock.id = invariable.main'
        );

        $qb
            ->select('event')
            ->leftJoin(
                MaterialStockEvent::class,
                'event',
                'WITH',
                'event.id = stock.event'
            );

        return $qb->getOneOrNullResult() ?: false;
    }
}