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

namespace BaksDev\Materials\Stocks\Repository\MaterialStocksTotalAccess;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Materials\Catalog\Type\Offers\ConstId\MaterialOfferConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\MaterialVariationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\ConstId\MaterialModificationConst;
use BaksDev\Materials\Stocks\Entity\Total\MaterialStockTotal;
use BaksDev\Products\Product\Type\Material\MaterialUid;
use InvalidArgumentException;

final class MaterialStocksTotalAccessRepository implements MaterialStocksTotalAccessInterface
{
    public function __construct(private readonly DBALQueryBuilder $DBALQueryBuilder) {}

    private MaterialUid|false $material = false;

    private MaterialOfferConst|false $offer = false;

    private MaterialVariationConst|false $variation = false;

    private MaterialModificationConst|false $modification = false;


    public function material(MaterialUid|string $material): self
    {
        if(is_string($material))
        {
            $material = new MaterialUid($material);
        }

        $this->material = $material;

        return $this;
    }

    public function offer(MaterialOfferConst|string|null|false $offer): self
    {
        if(empty($offer))
        {
            $this->offer = false;
            return $this;
        }

        if(is_string($offer))
        {
            $offer = new MaterialOfferConst($offer);
        }

        $this->offer = $offer;

        return $this;
    }

    public function variation(MaterialVariationConst|string|null $variation): self
    {
        if(empty($variation))
        {
            $this->variation = false;
            return $this;
        }

        if(is_string($variation))
        {
            $variation = new MaterialVariationConst($variation);
        }

        $this->variation = $variation;

        return $this;
    }

    public function modification(MaterialModificationConst|string|null $modification): self
    {
        if(empty($modification))
        {
            $this->modification = false;
            return $this;
        }

        if(is_string($modification))
        {
            $modification = new MaterialModificationConst($modification);
        }

        $this->modification = $modification;

        return $this;
    }

    /**
     * Метод возвращает общее количество ДОСТУПНОЙ сырья на всех складах (за вычетом резерва)
     */
    public function get(): int
    {
        if(false === ($this->material instanceof MaterialUid))
        {
            throw new InvalidArgumentException('Invalid Argument material');
        }

        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbal
            ->select('(SUM(stock.total) - SUM(stock.reserve))')
            ->from(MaterialStockTotal::class, 'stock')
            ->andWhere('stock.material = :material')
            ->setParameter('material', $this->material, MaterialUid::TYPE);

        if($this->offer)
        {
            $dbal
                ->andWhere('stock.offer = :offer')
                ->setParameter('offer', $this->offer, MaterialOfferConst::TYPE);
        }
        else
        {
            $dbal->andWhere('stock.offer IS NULL');
        }

        if($this->variation)
        {
            $dbal
                ->andWhere('stock.variation = :variation')
                ->setParameter('variation', $this->variation, MaterialVariationConst::TYPE);
        }
        else
        {
            $dbal->andWhere('stock.variation IS NULL');
        }

        if($this->modification)
        {
            $dbal
                ->andWhere('stock.modification = :modification')
                ->setParameter('modification', $this->modification, MaterialModificationConst::TYPE);
        }
        else
        {
            $dbal->andWhere('stock.modification IS NULL');
        }

        return $dbal->fetchOne() ?: 0;
    }
}
