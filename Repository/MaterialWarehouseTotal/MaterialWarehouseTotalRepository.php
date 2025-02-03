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

namespace BaksDev\Materials\Stocks\Repository\MaterialWarehouseTotal;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Materials\Catalog\Type\Id\MaterialUid;
use BaksDev\Materials\Catalog\Type\Offers\ConstId\MaterialOfferConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\MaterialVariationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\ConstId\MaterialModificationConst;
use BaksDev\Materials\Stocks\Entity\Total\MaterialStockTotal;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;

final class MaterialWarehouseTotalRepository implements MaterialWarehouseTotalInterface
{
    public function __construct(private readonly DBALQueryBuilder $DBALQueryBuilder) {}

    /**
     * Метод возвращает доступное количество данной сырья на указанном складе
     */
    public function getMaterialProfileTotal(
        UserProfileUid $profile,
        MaterialUid $material,
        ?MaterialOfferConst $offer = null,
        ?MaterialVariationConst $variation = null,
        ?MaterialModificationConst $modification = null
    ): int
    {

        $qb = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $qb->select('(SUM(stock.total) - SUM(stock.reserve))');

        $qb->from(MaterialStockTotal::class, 'stock');

        $qb->andWhere('stock.profile = :profile');
        $qb->setParameter('profile', $profile, UserProfileUid::TYPE);

        $qb->andWhere('stock.material = :material');
        $qb->setParameter('material', $material, MaterialUid::TYPE);

        if($offer)
        {
            $qb->andWhere('stock.offer = :offer');
            $qb->setParameter('offer', $offer, MaterialOfferConst::TYPE);
        }
        else
        {
            $qb->andWhere('stock.offer IS NULL');
        }

        if($variation)
        {
            $qb->andWhere('stock.variation = :variation');
            $qb->setParameter('variation', $variation, MaterialVariationConst::TYPE);
        }
        else
        {
            $qb->andWhere('stock.variation IS NULL');
        }

        if($modification)
        {
            $qb->andWhere('stock.modification = :modification');
            $qb->setParameter('modification', $modification, MaterialModificationConst::TYPE);
        }
        else
        {
            $qb->andWhere('stock.modification IS NULL');
        }

        return $qb->fetchOne() ?: 0;
    }

    /**
     * Метод возвращает весь резерв данной сырья на указанном складе
     */
    public function getMaterialProfileReserve(
        UserProfileUid $profile,
        MaterialUid $material,
        ?MaterialOfferConst $offer = null,
        ?MaterialVariationConst $variation = null,
        ?MaterialModificationConst $modification = null
    ): int
    {

        $qb = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $qb->select('SUM(stock.reserve)');

        $qb->from(MaterialStockTotal::class, 'stock');

        $qb->andWhere('stock.profile = :profile');
        $qb->setParameter('profile', $profile, UserProfileUid::TYPE);

        $qb->andWhere('stock.material = :material');
        $qb->setParameter('material', $material, MaterialUid::TYPE);

        if($offer)
        {
            $qb->andWhere('stock.offer = :offer');
            $qb->setParameter('offer', $offer, MaterialOfferConst::TYPE);
        }
        else
        {
            $qb->andWhere('stock.offer IS NULL');
        }

        if($variation)
        {
            $qb->andWhere('stock.variation = :variation');
            $qb->setParameter('variation', $variation, MaterialVariationConst::TYPE);
        }
        else
        {
            $qb->andWhere('stock.variation IS NULL');
        }

        if($modification)
        {
            $qb->andWhere('stock.modification = :modification');
            $qb->setParameter('modification', $modification, MaterialModificationConst::TYPE);
        }
        else
        {
            $qb->andWhere('stock.modification IS NULL');
        }

        return $qb->fetchOne() ?: 0;
    }

    /**
     * Метод возвращает количество данной сырья на указанном складе без резерва
     */
    public function getMaterialProfileTotalNotReserve(
        UserProfileUid $profile,
        MaterialUid $material,
        ?MaterialOfferConst $offer = null,
        ?MaterialVariationConst $variation = null,
        ?MaterialModificationConst $modification = null
    ): int
    {

        $qb = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $qb->select('SUM(stock.total)');

        $qb->from(MaterialStockTotal::class, 'stock');

        $qb->andWhere('stock.profile = :profile');
        $qb->setParameter('profile', $profile, UserProfileUid::TYPE);

        $qb->andWhere('stock.material = :material');
        $qb->setParameter('material', $material, MaterialUid::TYPE);

        if($offer)
        {
            $qb->andWhere('stock.offer = :offer');
            $qb->setParameter('offer', $offer, MaterialOfferConst::TYPE);
        }
        else
        {
            $qb->andWhere('stock.offer IS NULL');
        }

        if($variation)
        {
            $qb->andWhere('stock.variation = :variation');
            $qb->setParameter('variation', $variation, MaterialVariationConst::TYPE);
        }
        else
        {
            $qb->andWhere('stock.variation IS NULL');
        }

        if($modification)
        {
            $qb->andWhere('stock.modification = :modification');
            $qb->setParameter('modification', $modification, MaterialModificationConst::TYPE);
        }
        else
        {
            $qb->andWhere('stock.modification IS NULL');
        }

        return $qb->fetchOne() ?: 0;
    }
}
