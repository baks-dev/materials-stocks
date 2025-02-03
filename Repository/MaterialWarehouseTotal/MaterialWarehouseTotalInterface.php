<?php
/*
 *  Copyright 2023.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Materials\Stocks\Repository\MaterialWarehouseTotal;

use BaksDev\Contacts\Region\Type\Call\Const\ContactsRegionCallConst;
use BaksDev\Materials\Catalog\Type\Id\MaterialUid;
use BaksDev\Materials\Catalog\Type\Offers\ConstId\MaterialOfferConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\MaterialVariationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\ConstId\MaterialModificationConst;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;

interface MaterialWarehouseTotalInterface
{
    /**
     * Метод возвращает доступное количество (с учетом резерва!!!) данной сырья на указанном складе
     */
    public function getMaterialProfileTotal(
        UserProfileUid $profile,
        MaterialUid $material,
        ?MaterialOfferConst $offer,
        ?MaterialVariationConst $variation,
        ?MaterialModificationConst $modification
    ): int;


    /**
     * Метод возвращает весь резерв данной сырья на указанном складе
     */
    public function getMaterialProfileReserve(
        UserProfileUid $profile,
        MaterialUid $material,
        ?MaterialOfferConst $offer,
        ?MaterialVariationConst $variation,
        ?MaterialModificationConst $modification
    ): int;

    /**
     * Метод возвращает общее количество (без резерва!!!) данной сырья на указанном складе
     */
    public function getMaterialProfileTotalNotReserve(
        UserProfileUid $profile,
        MaterialUid $material,
        ?MaterialOfferConst $offer = null,
        ?MaterialVariationConst $variation = null,
        ?MaterialModificationConst $modification = null
    ): int;

}