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

namespace BaksDev\Materials\Stocks\Repository\MaterialStocksById;

use BaksDev\Core\Doctrine\ORMQueryBuilder;
use BaksDev\Materials\Stocks\Entity\Stock\Event\MaterialStockEvent;
use BaksDev\Materials\Stocks\Entity\Stock\Materials\MaterialStockMaterial;
use BaksDev\Materials\Stocks\Entity\Stock\MaterialStock;
use BaksDev\Materials\Stocks\Type\Id\MaterialStockUid;
use BaksDev\Materials\Stocks\Type\Status\MaterialStockStatus;
use BaksDev\Materials\Stocks\Type\Status\MaterialStockstatus\Collection\MaterialStockStatusCancel;
use BaksDev\Materials\Stocks\Type\Status\MaterialStockstatus\Collection\MaterialStockStatusIncoming;
use BaksDev\Materials\Stocks\Type\Status\MaterialStockstatus\Collection\MaterialStockStatusMoving;
use BaksDev\Materials\Stocks\Type\Status\MaterialStockstatus\Collection\MaterialStockStatusPackage;
use BaksDev\Materials\Stocks\Type\Status\MaterialStockstatus\Collection\MaterialStockStatusWarehouse;
use BaksDev\Materials\Stocks\Type\Status\MaterialStockstatus\MaterialStockStatusInterface;

final class MaterialStocksByIdRepository implements MaterialStocksByIdInterface
{
    public function __construct(private readonly ORMQueryBuilder $ORMQueryBuilder) {}


    private function builder()
    {
        $orm = $this->ORMQueryBuilder->createQueryBuilder(self::class);

        $orm
            ->from(MaterialStock::class, 'stock')
            ->where('stock.id = :id');

        $orm
            ->join(
                MaterialStockEvent::class,
                'event',
                'WITH',
                'event.id = stock.event AND event.status = :status'
            );

        $orm
            ->select('material')
            ->leftJoin(
                MaterialStockMaterial::class,
                'material',
                'WITH',
                'material.event = event.id'
            );

        return $orm;
    }


    /**
     * Метод возвращает всю продукцию заявке с определенным статусом
     */
    public function getMaterialsByMaterialStocksStatus(
        MaterialStockUid $id,
        MaterialStockStatus|MaterialStockStatusInterface|string $status
    ): ?array
    {
        //        if(is_string($status))
        //        {
        //            $status = new $status;
        //        }

        //        $status = $status instanceof MaterialStockstatus ? $status : new MaterialStockstatus($status);
        //
        //        $qb = $this->entityManager->createQueryBuilder();
        //
        //        $qb->select('material');
        //        $qb->from(MaterialStockEntity\MaterialStock::class, 'stock');
        //
        //        $qb->join(
        //            MaterialStockEntity\Event\MaterialStockEvent::class,
        //            'event',
        //            'WITH',
        //            'event.id = stock.event AND event.status = :status'
        //        );
        //
        //        $qb->leftJoin(
        //            MaterialStockEntity\Products\MaterialStockProductMaterial::class,
        //            'material',
        //            'WITH',
        //            'material.event = event.id'
        //        );
        //
        //        $qb->where('stock.id = :id');
        //        $qb->setParameter('id', $id, MaterialStockUid::TYPE);
        //        $qb->setParameter('status', $status, MaterialStockstatus::TYPE);

        $orm = $this->builder();

        $orm->setParameter('id', $id, MaterialStockUid::TYPE);
        $orm->setParameter('status', new MaterialStockStatus($status), MaterialStockStatus::TYPE);


        return $orm->getResult();
    }


    /** Метод возвращает всю продукция в приходном ордере */
    public function getMaterialsIncomingStocks(MaterialStockUid $id): ?array
    {
        //        $qb = $this->entityManager->createQueryBuilder();
        //
        //        $qb->select('material');
        //        $qb->from(MaterialStockEntity\MaterialStock::class, 'stock');
        //
        //        $qb->join(
        //            MaterialStockEntity\Event\MaterialStockEvent::class,
        //            'event',
        //            'WITH',
        //            'event.id = stock.event AND event.status = :status'
        //        );
        //
        //        $qb->leftJoin(
        //            MaterialStockEntity\Products\MaterialStockProductMaterial::class,
        //            'material',
        //            'WITH',
        //            'material.event = event.id'
        //        );
        //
        //        $qb->where('stock.id = :id');
        //        $qb->setParameter('id', $id, MaterialStockUid::TYPE);

        $orm = $this->builder();

        $orm->setParameter('id', $id, MaterialStockUid::TYPE);
        $orm->setParameter('status', new MaterialStockStatus(MaterialStockStatusIncoming::class), MaterialStockStatus::TYPE);

        return $orm->getResult();
    }


    /**
     * Метод возвращает всю продукцию для сборки (Package)
     */
    public function getMaterialsPackageStocks(MaterialStockUid $id): ?array
    {
        //        $qb = $this->entityManager->createQueryBuilder();
        //
        //        $qb->select('material');
        //        $qb->from(MaterialStockEntity\MaterialStock::class, 'stock');
        //
        //        $qb->join(
        //            MaterialStockEntity\Event\MaterialStockEvent::class,
        //            'event',
        //            'WITH',
        //            'event.id = stock.event AND event.status = :status'
        //        );
        //
        //        $qb->leftJoin(
        //            MaterialStockEntity\Products\MaterialStockProductMaterial::class,
        //            'material',
        //            'WITH',
        //            'material.event = event.id'
        //        );
        //
        //        $qb->where('stock.id = :id');


        $orm = $this->builder();

        $orm->setParameter('id', $id, MaterialStockUid::TYPE);
        $orm->setParameter('status', new MaterialStockStatus(MaterialStockStatusPackage::class), MaterialStockStatus::TYPE);

        return $orm->getResult();
    }

    /**
     * Метод возвращает всю продукцию для перемещения
     */
    public function getMaterialsMovingStocks(MaterialStockUid $id): ?array
    {
        //        $qb = $this->entityManager->createQueryBuilder();
        //
        //        $qb->select('material');
        //        $qb->from(MaterialStockEntity\MaterialStock::class, 'stock');
        //
        //        $qb->join(
        //            MaterialStockEntity\Event\MaterialStockEvent::class,
        //            'event',
        //            'WITH',
        //            'event.id = stock.event AND event.status = :status'
        //        );
        //
        //        $qb->leftJoin(
        //            MaterialStockEntity\Products\MaterialStockProductMaterial::class,
        //            'material',
        //            'WITH',
        //            'material.event = event.id'
        //        );
        //
        //        $qb->where('stock.id = :id');

        $orm = $this->builder();

        $orm->setParameter('id', $id, MaterialStockUid::TYPE);
        $orm->setParameter('status', new MaterialStockStatus(MaterialStockStatusMoving::class), MaterialStockStatus::TYPE);

        return $orm->getResult();
    }


    /**
     * Метод возвращает всю продукцию которая переместилась со склада
     */
    public function getMaterialsWarehouseStocks(MaterialStockUid $id): ?array
    {
        //        $qb = $this->entityManager->createQueryBuilder();
        //
        //        $qb->select('material');
        //        $qb->from(MaterialStockEntity\MaterialStock::class, 'stock');
        //
        //        $qb->join(
        //            MaterialStockEntity\Event\MaterialStockEvent::class,
        //            'event',
        //            'WITH',
        //            'event.id = stock.event AND event.status = :status'
        //        );
        //
        //        $qb->leftJoin(
        //            MaterialStockEntity\Products\MaterialStockProductMaterial::class,
        //            'material',
        //            'WITH',
        //            'material.event = event.id'
        //        );
        //
        //        $qb->where('stock.id = :id');

        $orm = $this->builder();

        $orm->setParameter('id', $id, MaterialStockUid::TYPE);
        $orm->setParameter('status', new MaterialStockStatus(MaterialStockStatusWarehouse::class), MaterialStockStatus::TYPE);

        return $orm->getResult();
    }


    /**
     * Метод возвращает всю продукцию в отмененной заявке
     */
    public function getMaterialsCancelStocks(MaterialStockUid $id): ?array
    {
        //        $qb = $this->entityManager->createQueryBuilder();
        //
        //        $qb->select('material');
        //        $qb->from(MaterialStockEntity\MaterialStock::class, 'stock');
        //
        //        $qb->join(
        //            MaterialStockEntity\Event\MaterialStockEvent::class,
        //            'event',
        //            'WITH',
        //            'event.id = stock.event AND event.status = :status'
        //        );
        //
        //        $qb->leftJoin(
        //            MaterialStockEntity\Products\MaterialStockProductMaterial::class,
        //            'material',
        //            'WITH',
        //            'material.event = event.id'
        //        );
        //
        //        $qb->where('stock.id = :id');

        $orm = $this->builder();

        $orm->setParameter('id', $id, MaterialStockUid::TYPE);
        $orm->setParameter('status', new MaterialStockStatus(MaterialStockStatusCancel::class), MaterialStockStatus::TYPE);

        return $orm->getResult();
    }


}
