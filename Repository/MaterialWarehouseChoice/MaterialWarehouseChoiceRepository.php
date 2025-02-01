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

namespace BaksDev\Materials\Stocks\Repository\MaterialWarehouseChoice;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Materials\Catalog\Type\Id\MaterialUid;
use BaksDev\Materials\Catalog\Type\Offers\ConstId\MaterialOfferConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\MaterialVariationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\ConstId\MaterialModificationConst;
use BaksDev\Materials\Stocks\Entity\Total\MaterialStockTotal;
use BaksDev\Users\Profile\UserProfile\Entity\Personal\UserProfilePersonal;
use BaksDev\Users\Profile\UserProfile\Entity\UserProfile;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Users\User\Type\Id\UserUid;
use Generator;
use InvalidArgumentException;

final class MaterialWarehouseChoiceRepository implements MaterialWarehouseChoiceInterface
{
    private ?UserUid $user = null;

    private ?MaterialUid $material = null;

    private ?MaterialOfferConst $offer = null;

    private ?MaterialVariationConst $variation = null;

    private ?MaterialModificationConst $modification = null;

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

    public function modificationConst(MaterialModificationConst|string $modification): self
    {
        if(is_string($modification))
        {
            $modification = new MaterialModificationConst($modification);
        }

        $this->modification = $modification;

        return $this;
    }


    /**
     * Возвращает список складов (профилей пользователя) на которых имеется данный вид продукта
     */
    public function fetchWarehouseByMaterial(): Generator
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


        if($this->offer)
        {
            $dbal->andWhere('stock.offer = :offer');
            $dbal->setParameter('offer', $this->offer, MaterialOfferConst::TYPE);
            $dbal->addGroupBy('stock.offer');
        }
        else
        {
            $dbal->andWhere('stock.offer IS NULL');
        }

        if($this->variation)
        {
            $dbal->andWhere('stock.variation = :variation');
            $dbal->setParameter('variation', $this->variation, MaterialVariationConst::TYPE);

            $dbal->addGroupBy('stock.variation');
        }
        else
        {
            $dbal->andWhere('stock.variation IS NULL');
        }

        if($this->modification)
        {
            $dbal->andWhere('stock.modification = :modification');
            $dbal->setParameter('modification', $this->modification, MaterialModificationConst::TYPE);

            $dbal->addGroupBy('stock.modification');

        }
        else
        {
            $dbal->andWhere('stock.modification IS NULL');
        }

        $dbal->join(
            'stock',
            UserProfile::class,
            'profile',
            'profile.id = stock.profile',
        );

        $dbal->join(
            'profile',
            UserProfilePersonal::class,
            'profile_personal',
            'profile_personal.event = profile.event',
        );

        $dbal->addSelect('stock.profile AS value')->groupBy('stock.profile');
        $dbal->addSelect('profile_personal.username AS attr')->addGroupBy('profile_personal.username');
        $dbal->addSelect('(SUM(stock.total) - SUM(stock.reserve)) AS property');

        return $dbal
            ->fetchAllHydrate(UserProfileUid::class);


    }
}
