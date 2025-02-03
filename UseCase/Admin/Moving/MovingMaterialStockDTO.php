<?php
/*
 *  Copyright 2022.  Baks.dev <admin@baks.dev>
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *  http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *   limitations under the License.
 *
 */

namespace BaksDev\Materials\Stocks\UseCase\Admin\Moving;

use BaksDev\Contacts\Region\Type\Call\Const\ContactsRegionCallConst;
use BaksDev\Materials\Catalog\Type\Id\MaterialUid;
use BaksDev\Materials\Catalog\Type\Offers\ConstId\MaterialOfferConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\MaterialVariationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\ConstId\MaterialModificationConst;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Users\User\Entity\User;
use BaksDev\Users\User\Type\Id\UserUid;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

final class MovingMaterialStockDTO
{

    /** Целевой склад */
    private ?UserProfileUid $targetWarehouse = null;

    /** Склад назначения */
    private ?UserProfileUid $destinationWarehouse = null;


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

    /** Коллекция перемещения  */
    #[Assert\Valid]
    private ArrayCollection $move;

    /** Комментарий */
    private ?string $comment = null;

    private UserUid $usr;

    public function __construct(User|UserUid $usr)
    {
        $this->usr = $usr instanceof User ? $usr->getId() : $usr;

        $this->move = new ArrayCollection();
    }


    // WAREHOUSE

    public function getTargetWarehouse(): ?UserProfileUid
    {
        return $this->targetWarehouse;
    }

    public function setTargetWarehouse(?UserProfileUid $warehouse): void
    {
        $this->targetWarehouse = $warehouse;
    }


    public function getDestinationWarehouse(): ?UserProfileUid
    {
        return $this->destinationWarehouse;
    }

    public function setDestinationWarehouse(?UserProfileUid $warehouse): void
    {
        $this->destinationWarehouse = $warehouse;
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


    /** Комментарий */
    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): void
    {
        $this->comment = $comment;
    }


    /** Коллекция сырья  */
    public function getMove(): ArrayCollection
    {
        return $this->move;
    }

    public function setMove(ArrayCollection $move): void
    {
        $this->move = $move;
    }

    public function addMove(MaterialStockDTO $move): void
    {
        $this->move->add($move);
    }

    public function removeMove(MaterialStockDTO $move): void
    {
        $this->move->removeElement($move);
    }

    /**
     * Usr
     */
    public function getUsr(): UserUid
    {
        return $this->usr;
    }

}
