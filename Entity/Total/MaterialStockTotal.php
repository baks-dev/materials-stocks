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

namespace BaksDev\Materials\Stocks\Entity\Total;

use BaksDev\Core\Entity\EntityState;
use BaksDev\Materials\Catalog\Type\Id\MaterialUid;
use BaksDev\Materials\Catalog\Type\Offers\ConstId\MaterialOfferConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\MaterialVariationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\ConstId\MaterialModificationConst;
use BaksDev\Materials\Stocks\Type\Total\MaterialStockTotalUid;
use BaksDev\Materials\Stocks\UseCase\Admin\EditTotal\MaterialStockTotalEditDTO;
use BaksDev\Materials\Stocks\UseCase\Admin\Storage\MaterialStockStorageEditDTO;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Users\User\Type\Id\UserUid;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

// MaterialStockTotal

#[ORM\Entity]
#[ORM\Table(name: 'material_stock_total')]
#[ORM\Index(columns: ['usr', 'profile'])]
class MaterialStockTotal extends EntityState
{
    /** ID  */
    #[ORM\Id]
    #[ORM\Column(type: MaterialStockTotalUid::TYPE)]
    private MaterialStockTotalUid $id;

    /** ID продукта */
    #[ORM\Column(type: MaterialUid::TYPE)]
    private MaterialUid $material;

    /** Постоянный уникальный идентификатор ТП */
    #[ORM\Column(type: MaterialOfferConst::TYPE, nullable: true)]
    private ?MaterialOfferConst $offer;

    /** Постоянный уникальный идентификатор варианта */
    #[ORM\Column(type: MaterialVariationConst::TYPE, nullable: true)]
    private ?MaterialVariationConst $variation;

    /** Постоянный уникальный идентификатор модификации */
    #[ORM\Column(type: MaterialModificationConst::TYPE, nullable: true)]
    private ?MaterialModificationConst $modification;


    /** ID пользователя */
    #[ORM\Column(type: UserUid::TYPE, nullable: true, options: ['default' => null])]
    private ?UserUid $usr = null;

    /** ID профиля (склад) */
    #[ORM\Column(type: UserProfileUid::TYPE)]
    private UserProfileUid $profile;


    /** Комментарий */
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $comment = null;

    /** Стоимость сырья на указанном складе */
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $price = 0;


    /** Место складирования */
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $storage = null;

    /** Общее количество на данном складе */
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $total = 0;

    /** Зарезервировано на данном складе */
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $reserve = 0;


    public function __construct(
        UserUid $usr,
        UserProfileUid $profile,
        MaterialUid $material,
        ?MaterialOfferConst $offer,
        ?MaterialVariationConst $variation,
        ?MaterialModificationConst $modification,
        ?string $storage
    )
    {
        $this->id = clone new MaterialStockTotalUid();

        $this->material = $material;
        $this->offer = $offer;
        $this->variation = $variation;
        $this->modification = $modification;
        $this->profile = $profile;
        $this->usr = $usr;
        $this->storage = $storage ?: null;
    }

    /**
     * Id
     */
    public function getId(): MaterialStockTotalUid
    {
        return $this->id;
    }


    /** Количество */

    // Увеличиваем количество
    public function addTotal(int $total): void
    {
        $this->total += $total;
    }

    // Уменьшаем количество
    public function subTotal(int $total): void
    {
        $this->total -= $total;
    }

    public function setTotal(int $total): self
    {
        $this->total = $total;
        return $this;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    /** Резервирование */

    // Увеличиваем количество
    public function addReserve(int $reserve): void
    {
        $this->reserve += $reserve;
    }

    // Уменьшаем количество
    public function subReserve(int $reserve): void
    {
        $this->reserve -= $reserve;

        if($this->reserve < 0)
        {
            $this->reserve = 0;
        }

    }

    public function getReserve(): int
    {
        return $this->reserve;
    }

    /**
     * Storage
     */
    public function getStorage(): ?string
    {
        return $this->storage;
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }

    public function getDto($dto): mixed
    {
        $dto = is_string($dto) && class_exists($dto) ? new $dto() : $dto;

        if($dto instanceof MaterialStockTotalEditDTO || $dto instanceof MaterialStockStorageEditDTO)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function setEntity($dto): mixed
    {
        if($dto instanceof MaterialStockTotalEditDTO || $dto instanceof MaterialStockStorageEditDTO || $dto instanceof self)
        {
            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }


}
