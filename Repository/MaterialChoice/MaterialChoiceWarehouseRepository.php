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

namespace BaksDev\Materials\Stocks\Repository\MaterialChoice;

use BaksDev\Contacts\Region\Type\Call\ContactsRegionCallUid;
use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Core\Doctrine\ORMQueryBuilder;
use BaksDev\Materials\Catalog\Type\Id\MaterialUid;
use BaksDev\Materials\Stocks\Entity\Total\MaterialStockTotal;
use BaksDev\Products\Product\Entity\Product;
use BaksDev\Products\Product\Entity\Trans\ProductTrans;
use BaksDev\Users\User\Type\Id\UserUid;
use Generator;

final readonly class MaterialChoiceWarehouseRepository implements MaterialChoiceWarehouseInterface
{
    public function __construct(
        private DBALQueryBuilder $DBALQueryBuilder,
        private ORMQueryBuilder $ORMQueryBuilder
    ) {}

    /**
     * Метод возвращает все идентификаторы продуктов с названием, имеющиеся в наличии на данном складе
     */
    public function getMaterialsExistWarehouse(UserUid $usr): Generator
    {
        $dbal = $this
            ->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();


        $dbal
            ->from(MaterialStockTotal::class, 'stock')
            ->andWhere('stock.usr = :usr')
            ->setParameter('usr', $usr, UserUid::TYPE);


        $dbal->andWhere('(stock.total - stock.reserve)  > 0');


        $dbal->groupBy('stock.material');
        $dbal->addGroupBy('trans.name');

        $dbal->join(
            'stock',
            Product::class,
            'material',
            'material.id = stock.material'
        );

        $dbal->leftJoin(
            'material',
            ProductTrans::class,
            'trans',
            'trans.event = material.event AND trans.local = :local'
        );


        //        $select = sprintf('new %s(stock.material, trans.name, (SUM(stock.total) - SUM(stock.reserve)) )', ProductUid::class);
        //
        //        $qb->select($select);


        $dbal->addSelect('stock.material AS value');
        $dbal->addSelect('trans.name AS attr');
        $dbal->addSelect('(SUM(stock.total) - SUM(stock.reserve)) AS option');

        return $dbal
            ->enableCache('materials-material', 86400)
            ->fetchAllHydrate(ProductUid::class);


    }


    /** Метод возвращает все идентификаторы продуктов с названием, имеющиеся в наличии на данном складе */
    public function getMaterialsByWarehouse(ContactsRegionCallUid $warehouse): ?array
    {
        $qb = $this
            ->ORMQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $select = sprintf('new %s(stock.material, trans.name, SUM(stock.total))', ProductUid::class);

        $qb->select($select);

        $qb->from(MaterialStockTotal::class, 'stock');
        $qb->where('stock.warehouse = :warehouse');
        $qb->andWhere('stock.total > 0');

        $qb->setParameter('warehouse', $warehouse, ContactsRegionCallUid::TYPE);

        $qb->groupBy('stock.material');
        $qb->addGroupBy('trans.name');

        $qb->join(
            Product::class,
            'material',
            'WITH',
            'material.id = stock.material'
        );


        $qb->leftJoin(
            ProductTrans::class,
            'trans',
            'WITH',
            'trans.event = material.event AND trans.local = :local'
        );


        /* Кешируем результат ORM */
        return $qb->enableCache('materials-stocks', 86400)->getResult();
    }
}
