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

namespace BaksDev\Materials\Stocks\UseCase\Admin\Moving;

use BaksDev\Contacts\Region\Type\Call\Const\ContactsRegionCallConst;
use BaksDev\Materials\Stocks\Entity\Stock\Event\MaterialStockEventInterface;
use BaksDev\Materials\Stocks\Type\Event\MaterialStockEventUid;
use BaksDev\Materials\Stocks\Type\Status\MaterialStockStatus;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

/** @see MaterialStockEvent */
final class MaterialStockDTO implements MaterialStockEventInterface
{
    /** Идентификатор */
    private ?MaterialStockEventUid $id = null;

    /** Ответственное лицо (Профиль пользователя) */
    #[Assert\Uuid]
    private ?UserProfileUid $profile = null;

    /** Статус заявки - ПЕРЕМЕЩЕНИЕ */
    #[Assert\NotBlank]
    private readonly MaterialStockStatus $status;

    /** Номер заявки */
    #[Assert\NotBlank]
    #[Assert\Type('string')]
    #[Assert\Length(max: 36)]
    private string $number;

    //    /** Константа Целевого склада */
    //    #[Assert\NotBlank]
    //    #[Assert\Uuid]
    //    private ?ContactsRegionCallConst $warehouse = null;

    /** Склад назначения при перемещении */
    #[Assert\Valid]
    private Move\MaterialStockMoveDTO $move;

    //    /** Константа склада назначения при перемещении */
    //    #[Assert\NotBlank]
    //    #[Assert\Uuid]
    //    private ?ContactsRegionCallConst $destination = null;

    /** Коллекция сырья  */
    #[Assert\Valid]
    private ArrayCollection $material;

    /** Комментарий */
    private ?string $comment = null;

    /** Идентификатор заказа на сборку */
    private Orders\MaterialStockOrderDTO $ord;

    public function __construct()
    {
        $this->status = new MaterialStockStatus(new MaterialStockstatus\Collection\MaterialStockStatusMoving());
        $this->material = new ArrayCollection();
        //$this->number = time().random_int(100, 999);

        $this->number = number_format(microtime(true) * 100, 0, '.', '.');
        $this->move = new Move\MaterialStockMoveDTO();
        $this->ord = new Orders\MaterialStockOrderDTO();
    }

    public function getEvent(): ?MaterialStockEventUid
    {
        return $this->id;
    }

    public function setId(MaterialStockEventUid $id): void
    {
        $this->id = $id;
    }

    /** Коллекция сырья  */
    public function getMaterial(): ArrayCollection
    {
        /** Сбрасываем идентификатор заявки */
        $this->number = number_format(microtime(true) * 100, 0, '.', '.');
        return $this->material;
    }

    public function setMaterial(ArrayCollection $material): void
    {
        $this->material = $material;
    }

    public function addMaterial(Materials\MaterialStockDTO $material): void
    {
        $containsMaterials = $this->material->filter(function(Materials\MaterialStockDTO $element) use ($material) {

            return
                $element->getMaterial()->equals($material->getMaterial()) &&
                $element->getOffer()?->equals($material->getOffer()) &&
                $element->getVariation()?->equals($material->getVariation()) &&
                $element->getModification()?->equals($material->getModification());
        });


        if($containsMaterials->isEmpty())
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

    /** Ответственное лицо (Профиль пользователя) */
    public function getProfile(): ?UserProfileUid
    {
        return $this->profile;
    }

    public function setProfile(?UserProfileUid $profile): void
    {
        $this->profile = $profile;
    }

    /** Статус заявки - ПРИХОД */
    public function getStatus(): MaterialStockStatus
    {
        return $this->status;
    }

    /** Номер заявки */
    public function getNumber(): string
    {
        return $this->number;
    }

    public function setNumber(string $number): void
    {
        $this->number = $number;
    }


    /** Склад назначения при перемещении */
    public function getMove(): Move\MaterialStockMoveDTO
    {
        return $this->move;
    }

    public function setMove(Move\MaterialStockMoveDTO $move): void
    {
        $this->move = $move;
    }

    /** Идентификатор заказа на сборку */

    public function getOrd(): Orders\MaterialStockOrderDTO
    {
        return $this->ord;
    }


    public function setOrd(Orders\MaterialStockOrderDTO $ord): void
    {
        $this->ord = $ord;
    }
}
