<?php
/*
 *  Copyright 2025.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Materials\Stocks\Repository\MaterialStocksTotalStorage;

use BaksDev\Core\Doctrine\ORMQueryBuilder;
use BaksDev\Materials\Catalog\Type\Offers\ConstId\MaterialOfferConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\MaterialVariationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\ConstId\MaterialModificationConst;
use BaksDev\Materials\Stocks\Entity\Total\MaterialStockTotal;
use BaksDev\Products\Product\Type\Material\MaterialUid;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use InvalidArgumentException;

final class MaterialStocksTotalStorageRepository implements MaterialStocksTotalStorageInterface
{
    private UserProfileUid|false $profile = false;

    private MaterialUid|false $material = false;

    private MaterialOfferConst|false $offer = false;

    private MaterialVariationConst|false $variation = false;

    private MaterialModificationConst|false $modification = false;

    private ?string $storage = null;

    public function __construct(private readonly ORMQueryBuilder $ORMQueryBuilder) {}

    public function profile(UserProfileUid|string $profile): self
    {
        if(is_string($profile))
        {
            $profile = new UserProfileUid($profile);
        }

        $this->profile = $profile;

        return $this;
    }

    public function material(MaterialUid|string $material): self
    {
        if(is_string($material))
        {
            $material = new MaterialUid($material);
        }

        $this->material = $material;

        return $this;
    }

    public function offer(MaterialOfferConst|string|false|null $offer): self
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

    public function variation(MaterialVariationConst|string|false|null $variation): self
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

    public function modification(MaterialModificationConst|string|false|null $modification): self
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


    public function storage(string|false|null $storage): self
    {
        if(empty($storage))
        {
            $this->storage = null;
            return $this;
        }

        $storage = trim($storage);
        $storage = mb_strtolower($storage);

        $this->storage = $storage;

        return $this;
    }


    /** Метод возвращает складской остаток (место для хранения указанной сырья) указанного профиля */
    public function find(): ?MaterialStockTotal
    {
        if(false === ($this->profile instanceof UserProfileUid))
        {
            throw new InvalidArgumentException('Invalid Argument profile');
        }

        if(false === ($this->material instanceof MaterialUid))
        {
            throw new InvalidArgumentException('Invalid Argument material');
        }

        $orm = $this->ORMQueryBuilder->createQueryBuilder(self::class);

        $orm
            ->select('stock')
            ->from(MaterialStockTotal::class, 'stock');

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


        if($this->storage)
        {
            $orm
                ->andWhere('LOWER(stock.storage) = :storage')
                ->setParameter(
                    key: 'storage',
                    value: $this->storage
                );
        }
        else
        {
            $orm->andWhere('stock.storage IS NULL');
        }

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

        return $orm->getOneOrNullResult();
    }
}
