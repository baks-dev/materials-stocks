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

namespace BaksDev\Materials\Stocks\Repository\MaterialModificationChoice;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Materials\Catalog\Type\Id\MaterialUid;
use BaksDev\Materials\Catalog\Type\Offers\ConstId\MaterialOfferConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\MaterialVariationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\ConstId\MaterialModificationConst;
use BaksDev\Materials\Stocks\Entity\Total\MaterialStockTotal;
use BaksDev\Products\Category\Entity\Offers\Variation\Modification\CategoryProductModification;
use BaksDev\Products\Category\Entity\Offers\Variation\Modification\Trans\CategoryProductModificationTrans;
use BaksDev\Products\Product\Entity\Offers\ProductOffer;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Products\Product\Entity\Offers\Variation\ProductVariation;
use BaksDev\Products\Product\Entity\Product;
use BaksDev\Users\User\Type\Id\UserUid;
use Generator;
use InvalidArgumentException;

final class MaterialModificationChoiceWarehouseRepository implements MaterialModificationChoiceWarehouseInterface
{

    private ?UserUid $user = null;
    private ?MaterialUid $material = null;
    private ?MaterialOfferConst $offer = null;
    private ?MaterialVariationConst $variation = null;

    public function __construct(private readonly DBALQueryBuilder $DBALQueryBuilder) {}


    public function user(UserUid|string $user): self
    {
        if(is_string($user))
        {
            $user = new UserUid($user);
        }

        $this->user = $user;

        return $this;
    }


    public function material(ProductUid|string $material): self
    {
        if(is_string($material))
        {
            $material = new ProductUid($material);
        }

        $this->material = $material;

        return $this;
    }


    public function offerConst(MaterialOfferConst|string $offer): self
    {
        if(is_string($offer))
        {
            $offer = new MaterialOfferConst($offer);
        }

        $this->offer = $offer;

        return $this;
    }

    public function variationConst(MaterialVariationConst|string $variation): self
    {
        if(is_string($variation))
        {
            $variation = new MaterialVariationConst($variation);
        }

        $this->variation = $variation;

        return $this;
    }


    /**
     * Метод возвращает все идентификаторы множественных вариантов, имеющиеся в наличии на склад
     */
    public function getMaterialsModificationExistWarehouse(): Generator
    {
        if(!$this->user || !$this->material || !$this->offer || !$this->variation)
        {
            throw new InvalidArgumentException('Необходимо передать все параметры');
        }

        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();


        $dbal->from(MaterialStockTotal::class, 'stock');

        $dbal
            ->andWhere('stock.usr = :usr')
            ->setParameter('usr', $this->user, UserUid::TYPE);

        $dbal->andWhere('stock.material = :material')
            ->setParameter('material', $this->material, ProductUid::TYPE);


        $dbal->andWhere('stock.offer = :offer')
            ->setParameter('offer', $this->offer, MaterialOfferConst::TYPE);


        $dbal->andWhere('stock.variation = :variation')
            ->setParameter('variation', $this->variation, MaterialVariationConst::TYPE);

        $dbal->andWhere('(stock.total - stock.reserve) > 0');


        $dbal->join(
            'stock',
            Product::class,
            'material',
            'material.id = stock.material',
        );


        $dbal->join(
            'stock',
            ProductOffer::class,
            'offer',
            'offer.const = stock.offer AND offer.event = material.event',
        );


        $dbal->join(
            'stock',
            ProductVariation::class,
            'variation',
            'variation.const = stock.variation AND variation.offer = offer.id',
        );


        $dbal->join(
            'stock',
            ProductModification::class,
            'modification',
            'modification.const = stock.modification AND modification.variation = variation.id'
        );


        // Тип торгового предложения

        $dbal->join(
            'modification',
            CategoryProductModification::class,
            'category_modification',
            'category_modification.id = modification.category_modification'
        );

        $dbal->leftJoin(
            'category_modification',
            CategoryProductModificationTrans::class,
            'category_modification_trans',
            'category_modification_trans.modification = category_modification.id AND category_modification_trans.local = :local'
        );


        $dbal->addSelect('stock.modification AS value')->groupBy('stock.modification');
        $dbal->addSelect('modification.value AS attr')->addGroupBy('modification.value');
        $dbal->addSelect('category_modification_trans.name AS option')->addGroupBy('category_modification_trans.name');

        $dbal->addSelect('(SUM(stock.total) - SUM(stock.reserve)) AS property');
        $dbal->addSelect('modification.postfix AS characteristic')->addGroupBy('modification.postfix');
        $dbal->addSelect('category_modification.reference AS reference')->addGroupBy('category_modification.reference');


        return $dbal
            ->enableCache('materials-stocks', 86400)
            ->fetchAllHydrate(MaterialModificationConst::class);

    }
}
