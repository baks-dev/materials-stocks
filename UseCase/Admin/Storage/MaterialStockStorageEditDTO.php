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

namespace BaksDev\Materials\Stocks\UseCase\Admin\Storage;

use BaksDev\Materials\Catalog\Type\Offers\ConstId\MaterialOfferConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\MaterialVariationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\ConstId\MaterialModificationConst;
use BaksDev\Materials\Stocks\Entity\Total\MaterialStockTotal;
use BaksDev\Materials\Stocks\Type\Total\MaterialStockTotalUid;
use BaksDev\Products\Product\Type\Material\MaterialUid;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Symfony\Component\Validator\Constraints as Assert;

/** @see MaterialStockTotal */
final class MaterialStockStorageEditDTO
{
    /** ID  */
    #[Assert\NotBlank]
    private readonly MaterialStockTotalUid $id;

    /** ID профиля (склад) */
    #[Assert\NotBlank]
    private readonly UserProfileUid $profile;

    /** ID продукта */
    private readonly MaterialUid $material;

    /** Постоянный уникальный идентификатор ТП */
    private readonly ?MaterialOfferConst $offer;

    /** Постоянный уникальный идентификатор варианта */
    private readonly ?MaterialVariationConst $variation;

    /** Постоянный уникальный идентификатор модификации */
    private readonly ?MaterialModificationConst $modification;

    /** Общее количество на данном складе */
    private readonly int $total;

    /** Зарезервировано на данном складе */
    private readonly int $reserve;

    /** Место складирования */
    private ?string $storage = null;

    /** Комментарий */
    private ?string $comment = null;


    /**
     * Storage
     */
    public function getStorage(): ?string
    {
        return $this->storage;
    }

    public function setStorage(?string $storage): self
    {
        $this->storage = $storage;
        return $this;
    }


    /**
     * Id
     */
    public function getId(): MaterialStockTotalUid
    {
        return $this->id;
    }

    /**
     * Material
     */
    public function getMaterial(): MaterialUid
    {
        return $this->material;
    }

    /**
     * Offer
     */
    public function getOffer(): ?MaterialOfferConst
    {
        return $this->offer;
    }

    /**
     * Variation
     */
    public function getVariation(): ?MaterialVariationConst
    {
        return $this->variation;
    }

    /**
     * Modification
     */
    public function getModification(): ?MaterialModificationConst
    {
        return $this->modification;
    }

    /**
     * Profile
     */
    public function getProfile(): UserProfileUid
    {
        return $this->profile;
    }

    /**
     * Total
     */
    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * Reserve
     */
    public function getReserve(): int
    {
        return $this->reserve;
    }

    /**
     * Comment
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;
        return $this;
    }


}
