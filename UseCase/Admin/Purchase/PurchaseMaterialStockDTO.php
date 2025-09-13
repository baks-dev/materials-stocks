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

namespace BaksDev\Materials\Stocks\UseCase\Admin\Purchase;

use BaksDev\Contacts\Region\Type\Call\ContactsRegionCallUid;
use BaksDev\Materials\Catalog\Type\Offers\ConstId\MaterialOfferConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\MaterialVariationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\ConstId\MaterialModificationConst;
use BaksDev\Materials\Category\Type\Id\CategoryMaterialUid;
use BaksDev\Materials\Stocks\Entity\Stock\Event\MaterialStockEventInterface;
use BaksDev\Materials\Stocks\Type\Event\MaterialStockEventUid;
use BaksDev\Materials\Stocks\Type\Status\MaterialStockStatus;
use BaksDev\Materials\Stocks\Type\Status\MaterialStockStatus\Collection\MaterialStockStatusPurchase;
use BaksDev\Materials\Stocks\UseCase\Admin\Purchase\Invariable\PurchaseMaterialInvariableDTO;
use BaksDev\Products\Product\Type\Material\MaterialUid;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

/** @see MaterialStockEvent */
final class PurchaseMaterialStockDTO implements MaterialStockEventInterface
{
    /** Идентификатор */
    #[Assert\Uuid]
    #[Assert\IsNull]
    private ?MaterialStockEventUid $id = null;


    /** Статус заявки - ПРИХОД */
    #[Assert\NotBlank]
    private readonly MaterialStockStatus $status;


    /** Коллекция сырья  */
    #[Assert\Valid]
    private ArrayCollection $material;

    /** Комментарий */
    private ?string $comment = null;

    /** Постоянная величина */
    #[Assert\Valid]
    private PurchaseMaterialInvariableDTO $invariable;


    /**
     *  ВСПОМОГАТЕЛЬНЫЕ СВОЙСТВА
     */

    /** Категория */
    private ?CategoryMaterialUid $category = null;

    /** Продукт */
    private ?MaterialUid $preMaterial = null;

    /** Торговое предложение */
    private ?MaterialOfferConst $preOffer = null;

    /** Множественный вариант */
    private ?MaterialVariationConst $preVariation = null;

    /** Модификация множественного варианта */
    private ?MaterialModificationConst $preModification = null;

    /** Количество */
    private ?int $preTotal = null;

    public function __construct()
    {
        $this->status = new MaterialStockStatus(MaterialStockStatusPurchase::class);
        $this->material = new ArrayCollection();
        $this->invariable = new PurchaseMaterialInvariableDTO();
    }

    public function getEvent(): ?MaterialStockEventUid
    {
        return $this->id;
    }

    public function setId(MaterialStockEventUid $id): void
    {
        $this->id = $id;
    }

    /**
     * Category
     */
    public function getCategory(): ?CategoryMaterialUid
    {
        return $this->category;
    }

    public function setCategory(?CategoryMaterialUid $category): self
    {
        $this->category = $category;
        return $this;
    }

    /** Коллекция сырья  */
    public function getMaterial(): ArrayCollection
    {
        return $this->material;
    }

    public function setMaterial(ArrayCollection $material): void
    {
        $this->material = $material;
    }

    public function addMaterial(Materials\MaterialStockDTO $material): void
    {
        $filter = $this->material->filter(function(Materials\MaterialStockDTO $element) use ($material) {
            return $element->getMaterial()->equals($material->getMaterial()) &&
                $element->getOffer()?->equals($material->getOffer()) &&
                $element->getVariation()?->equals($material->getVariation()) &&
                $element->getModification()?->equals($material->getModification());
        });

        if($filter->isEmpty())
        {
            $this->material->add($material);
        }
    }

    public function removeMaterial(Materials\MaterialStockDTO $material): void
    {
        $this->material->removeElement($material);
    }

    /** Комментарий */
    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): void
    {
        $this->comment = $comment;
    }


    /** Статус заявки - ПРИХОД */
    public function getStatus(): MaterialStockStatus
    {
        return $this->status;
    }

    /**
     * Invariable
     */
    public function getInvariable(): PurchaseMaterialInvariableDTO
    {
        return $this->invariable;
    }


    // MATERIAL

    public function getPreMaterial(): ?MaterialUid
    {
        return $this->preMaterial;
    }

    public function setPreMaterial(MaterialUid $material): void
    {
        $this->preMaterial = $material;
    }

    // OFFER

    public function getPreOffer(): ?MaterialOfferConst
    {
        return $this->preOffer;
    }

    public function setPreOffer(MaterialOfferConst $offer): void
    {
        $this->preOffer = $offer;
    }

    // VARIATION

    public function getPreVariation(): ?MaterialVariationConst
    {
        return $this->preVariation;
    }

    public function setPreVariation(?MaterialVariationConst $preVariation): void
    {
        $this->preVariation = $preVariation;
    }

    // MODIFICATION

    public function getPreModification(): ?MaterialModificationConst
    {
        return $this->preModification;
    }

    public function setPreModification(?MaterialModificationConst $preModification): void
    {
        $this->preModification = $preModification;
    }

    // TOTAL

    public function getPreTotal(): ?int
    {
        return $this->preTotal;
    }

    public function setPreTotal(int $total): void
    {
        $this->preTotal = $total;
    }


}
