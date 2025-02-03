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

namespace BaksDev\Materials\Stocks\Messenger\Stocks\SubMaterialStocksTotal;

use BaksDev\Materials\Catalog\Type\Id\MaterialUid;
use BaksDev\Materials\Catalog\Type\Offers\ConstId\MaterialOfferConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\MaterialVariationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\ConstId\MaterialModificationConst;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Symfony\Component\Validator\Constraints as Assert;

/** @see SubMaterialStocksTotalReserveMessage */
final readonly class SubMaterialStocksTotalAndReserveMessage
{
    public function __construct(
        private UserProfileUid $profile,
        private MaterialUid $material,
        private ?MaterialOfferConst $offer,
        private ?MaterialVariationConst $variation,
        private ?MaterialModificationConst $modification
    ) {}

    /**
     * Profile
     */
    public function getProfile(): UserProfileUid
    {
        return $this->profile;
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

}
