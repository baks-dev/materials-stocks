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

namespace BaksDev\Materials\Stocks\Repository\MaterialStockMinQuantity;

use BaksDev\Core\Doctrine\ORMQueryBuilder;
use BaksDev\Materials\Catalog\Type\Offers\ConstId\MaterialOfferConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\MaterialVariationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\ConstId\MaterialModificationConst;
use BaksDev\Materials\Stocks\Entity\Total\MaterialStockTotal;
use BaksDev\Products\Product\Type\Material\MaterialUid;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use InvalidArgumentException;

final class MaterialStockQuantityRepository implements MaterialStockQuantityInterface
{
    private ?UserProfileUid $profile = null;

    private ?MaterialUid $material = null;

    private ?MaterialOfferConst $offer = null;

    private ?MaterialVariationConst $variation = null;

    private ?MaterialModificationConst $modification = null;

    public function __construct(private readonly ORMQueryBuilder $ORMQueryBuilder) {}


    public function profile(UserProfileUid $profile): self
    {
        $this->profile = $profile;
        return $this;
    }

    public function material(MaterialUid $material): self
    {
        $this->material = $material;
        return $this;
    }

    public function offerConst(?MaterialOfferConst $offer): self
    {
        $this->offer = $offer;
        return $this;
    }

    public function variationConst(?MaterialVariationConst $variation): self
    {
        $this->variation = $variation;
        return $this;
    }

    public function modificationConst(?MaterialModificationConst $modification): self
    {
        $this->modification = $modification;
        return $this;
    }


    private function builder(): ORMQueryBuilder
    {

        if(!$this->profile)
        {
            throw new InvalidArgumentException('profile not found : ->profile(UserProfileUid $profile) ');
        }

        if(!$this->material)
        {
            throw new InvalidArgumentException('material not found : ->material(MaterialUid $material) ');
        }

        $orm = $this->ORMQueryBuilder->createQueryBuilder(self::class);

        $orm->select('stock');

        $orm->from(MaterialStockTotal::class, 'stock');

        $orm
            ->andWhere('stock.profile = :profile')
            ->setParameter(
                key: 'profile',
                value: $this->profile,
                type: UserProfileUid::TYPE
            );

        $orm
            ->andWhere('stock.material = :material')
            ->setParameter(
                key: 'material',
                value: $this->material,
                type: MaterialUid::TYPE
            );


        if($this->offer)
        {
            $orm
                ->andWhere('stock.offer = :offer')
                ->setParameter(
                    key: 'offer',
                    value: $this->offer,
                    type: MaterialOfferConst::TYPE
                );
        }
        else
        {
            $orm->andWhere('stock.offer IS NULL');
        }

        if($this->variation)
        {
            $orm
                ->andWhere('stock.variation = :variation')
                ->setParameter(
                    key: 'variation',
                    value: $this->variation,
                    type: MaterialVariationConst::TYPE
                );
        }
        else
        {
            $orm->andWhere('stock.variation IS NULL');
        }

        if($this->modification)
        {
            $orm
                ->andWhere('stock.modification = :modification')
                ->setParameter(
                    key: 'modification',
                    value: $this->modification,
                    type: MaterialModificationConst::TYPE
                );
        }
        else
        {
            $orm->andWhere('stock.modification IS NULL');
        }

        $orm->setMaxResults(1);

        return $orm;

    }

    /**
     * Метод возвращает место складирования сырья с минимальным количеством в наличии без учета резерва
     */
    public function findOneByTotalMin(): ?MaterialStockTotal
    {

        $orm = $this->builder();

        $orm->orderBy('stock.total');

        /* складские места только с наличием */
        $orm->andWhere('stock.total > 0');

        /* складские места только с резервом */
        $orm->andWhere('stock.reserve > 0');

        return $orm->getOneOrNullResult();
    }

    /**
     * Метод возвращает место складирования сырья с максимальным количеством в наличии без учета резерва
     */
    public function findOneByTotalMax(): ?MaterialStockTotal
    {

        $orm = $this->builder();

        $orm->orderBy('stock.total', 'DESC');

        /* складские места только с наличием */
        $orm->andWhere('stock.total > 0');

        return $orm->getOneOrNullResult();
    }


    /**
     * Метод возвращает место складирования сырья с максимальным количеством в наличии и резервом > 0
     */
    public function findOneByReserveMax(): ?MaterialStockTotal
    {
        $orm = $this->builder();

        $orm->orderBy('stock.total', 'DESC');

        /* складские места только с резервом */
        $orm->andWhere('stock.reserve > 0');

        return $orm->getOneOrNullResult();
    }


    /**
     * Метод возвращает место складирования сырья с минимальным количеством в наличии с учетом резерва
     */
    public function findOneBySubReserve(): ?MaterialStockTotal
    {

        $orm = $this->builder();

        $orm->orderBy('stock.total');

        /* складские места только с наличием учитывая резерв */
        $orm->andWhere('(stock.total - stock.reserve) > 0');

        return $orm->getOneOrNullResult();
    }


}
