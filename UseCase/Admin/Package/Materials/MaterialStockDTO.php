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

namespace BaksDev\Materials\Stocks\UseCase\Admin\Package\Materials;

use BaksDev\Materials\Catalog\Type\Id\MaterialUid;
use BaksDev\Materials\Catalog\Type\Offers\ConstId\MaterialOfferConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\MaterialVariationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\ConstId\MaterialModificationConst;
use BaksDev\Materials\Stocks\Entity\Stock\Materials\MaterialStockMaterialInterface;
use BaksDev\Orders\Order\Entity\Products\OrderProductInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @see MaterialStockMaterial
 * @see OrderProduct
 */
final class MaterialStockDTO implements MaterialStockMaterialInterface, OrderProductInterface
{
    /** Продукт */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    private MaterialUid $material;

    /** Торговое предложение */
    #[Assert\Uuid]
    private ?MaterialOfferConst $offer = null;

    /** Множественный вариант */
    #[Assert\Uuid]
    private ?MaterialVariationConst $variation = null;

    /** Модификация множественного варианта */
    #[Assert\Uuid]
    private ?MaterialModificationConst $modification = null;

    //    /** Стоимость и количество в заказе */
    //    #[Assert\Valid]
    //    private Price\PackageOrderPriceDTO $price;

    /** Количество в заявке */
    private int $total = 0;


    /** Продукт */
    public function getMaterial(): MaterialUid
    {
        return $this->material;
    }

    public function setMaterial(MaterialUid $material): void
    {
        $this->material = $material;
    }

    /** Торговое предложение */
    public function getOffer(): ?MaterialOfferConst
    {
        return $this->offer;
    }

    public function setOffer(MaterialOfferConst $offer): void
    {
        $this->offer = $offer;
    }

    /** Множественный вариант */
    public function getVariation(): ?MaterialVariationConst
    {
        return $this->variation;
    }

    public function setVariation(?MaterialVariationConst $variation): void
    {
        $this->variation = $variation;
    }

    /** Модификация множественного варианта */
    public function getModification(): ?MaterialModificationConst
    {
        return $this->modification;
    }

    public function setModification(?MaterialModificationConst $modification): void
    {
        $this->modification = $modification;
    }

    //    /** Стоимость и количество */
    //    public function getPrice() : Price\PackageOrderPriceDTO
    //    {
    //        return $this->price;
    //    }
    //
    //    public function setPrice(Price\PackageOrderPriceDTO $price) : void
    //    {
    //        $this->price = $price;
    //    }

    /** Количество в заявке */
    public function getTotal(): int
    {
        /* Присваиваем значение из заказа */
        //$this->total = $this->getPrice()->getTotal();
        return $this->total;
    }

    public function setTotal(int $total): void
    {
        //$this->getPrice()->setTotal($total);
        $this->total = $total;
    }

}
