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

namespace BaksDev\Materials\Stocks\Repository\MaterialOfferChoice;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Materials\Catalog\Type\Id\MaterialUid;
use BaksDev\Materials\Catalog\Type\Offers\ConstId\MaterialOfferConst;
use BaksDev\Materials\Stocks\Entity\Total\MaterialStockTotal;
use BaksDev\Products\Category\Entity\Offers\CategoryProductOffers;
use BaksDev\Products\Category\Entity\Offers\Trans\CategoryProductOffersTrans;
use BaksDev\Products\Product\Entity\Offers\ProductOffer;
use BaksDev\Products\Product\Entity\Product;
use BaksDev\Users\User\Type\Id\UserUid;
use Generator;
use InvalidArgumentException;

final class MaterialOfferChoiceWarehouseRepository implements MaterialOfferChoiceWarehouseInterface
{
    private ?UserUid $user = null;

    private ?MaterialUid $material = null;

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


    /**
     * Метод возвращает все идентификаторы торговых предложений, имеющиеся в наличии на складе
     */
    public function getMaterialsOfferExistWarehouse(): Generator
    {
        if(!$this->user || !$this->material)
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

        $dbal
            ->andWhere('stock.material = :material')
            ->setParameter('material', $this->material, ProductUid::TYPE);

        $dbal->andWhere('(stock.total - stock.reserve) > 0');


        $dbal->join(
            'stock',
            Product::class,
            'material',
            'material.id = stock.material'
        );


        $dbal->join(
            'material',
            ProductOffer::class,
            'offer',
            'offer.const = stock.offer AND offer.event = material.event'
        );

        // Тип торгового предложения

        $dbal->join(
            'offer',
            CategoryProductOffers::class,
            'category_offer',
            'category_offer.id = offer.category_offer'
        );

        $dbal->leftJoin(
            'category_offer',
            CategoryProductOffersTrans::class,
            'category_offer_trans',
            'category_offer_trans.offer = category_offer.id AND category_offer_trans.local = :local'
        );


        $dbal->addSelect('stock.offer AS value');
        $dbal->addSelect('offer.value AS attr');
        $dbal->addSelect('category_offer_trans.name AS option');

        $dbal->addSelect('(SUM(stock.total) - SUM(stock.reserve)) AS property');
        $dbal->addSelect('offer.postfix AS characteristic');
        $dbal->addSelect('category_offer.reference AS reference');

        $dbal->groupBy('stock.offer');
        $dbal->addGroupBy('category_offer_trans.name');
        $dbal->addGroupBy('offer.value');
        $dbal->addGroupBy('offer.postfix');
        $dbal->addGroupBy('category_offer.reference');

        return $dbal
            ->enableCache('materials-stocks', 86400)
            ->fetchAllHydrate(MaterialOfferConst::class);


    }
}
