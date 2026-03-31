<?php
/*
 *  Copyright 2026.  Baks.dev <admin@baks.dev>
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
 *
 */

declare(strict_types=1);

namespace BaksDev\Materials\Stocks\Messenger\PurchaseMaterialStock;

use BaksDev\Materials\Catalog\Type\Offers\ConstId\MaterialOfferConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\MaterialVariationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\ConstId\MaterialModificationConst;
use BaksDev\Products\Product\Type\Material\MaterialUid;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class PurchaseMaterialStockMessage
{
    /** Количество продукции */
    #[Assert\NotBlank]
    private int $total;

    /** ID профиля */
    #[Assert\Uuid]
    private ?string $profile;

    /** ID material */
    #[Assert\Uuid]
    private ?string $material;

    /** Постоянный уникальный идентификатор ТП */
    #[Assert\Uuid]
    private ?string $offer;

    /** Постоянный уникальный идентификатор варианта */
    #[Assert\Uuid]
    private ?string $variation;

    /** Постоянный уникальный идентификатор модификации */
    #[Assert\Uuid]
    private ?string $modification;

    public function __construct(
        int $total,

        ?UserProfileUid $profile,

        ?MaterialUid $material,
        ?MaterialOfferConst $offer,
        ?MaterialVariationConst $variation,
        ?MaterialModificationConst $modification,
    )
    {
        $this->total = $total;

        $this->profile = $profile ? (string) $profile : null;

        $this->material = $material ? (string) $material : null;
        $this->offer = $offer ? (string) $offer : null;
        $this->variation = $variation ? (string) $variation : null;
        $this->modification = $modification ? (string) $modification : null;
    }

    /**
     * Profile
     */
    public function getProfile(): ?UserProfileUid
    {
        return $this->profile ? new UserProfileUid($this->profile) : null;
    }

    /**
     * Product
     */
    public function getMaterial(): ?MaterialUid
    {
        return $this->material ? new MaterialUid($this->material) : null;
    }

    /**
     * Offer
     */
    public function getOffer(): ?MaterialOfferConst
    {
        return $this->offer ? new MaterialOfferConst($this->offer) : null;
    }

    /**
     * Variation
     */
    public function getVariation(): ?MaterialVariationConst
    {
        return $this->variation ? new MaterialVariationConst($this->variation) : null;
    }

    /**
     * Modification
     */
    public function getModification(): ?MaterialModificationConst
    {
        return $this->modification ? new MaterialModificationConst($this->modification) : null;
    }

    public function getTotal(): int
    {
        return $this->total;
    }
}
