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

namespace BaksDev\Materials\Stocks\UseCase\Admin\Warehouse;

use BaksDev\Contacts\Region\Type\Call\Const\ContactsRegionCallConst;
use BaksDev\Materials\Stocks\Entity\Stock\Event\MaterialStockEventInterface;
use BaksDev\Materials\Stocks\Type\Event\MaterialStockEventUid;
use BaksDev\Materials\Stocks\Type\Status\MaterialStockStatus;
use BaksDev\Materials\Stocks\Type\Status\MaterialStockStatus\Collection\MaterialStockStatusWarehouse;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Users\User\Entity\User;
use BaksDev\Users\User\Type\Id\UserUid;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

/** @see MaterialStockEvent */
final class WarehouseMaterialStockDTO implements MaterialStockEventInterface
{
    /** Идентификатор */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    private readonly MaterialStockEventUid $id;

    //    /** Склад */
    //    #[Assert\NotBlank]
    //    #[Assert\Uuid]
    //    private ?ContactsRegionCallConst $warehouse = null;

    //    /** Целевой склад при перемещении */
    //    #[Assert\Uuid]
    //    private ?UserProfileUid $destination = null;

    //    /** Ответственное лицо (Профиль пользователя) */
    //    #[Assert\NotBlank]
    //    #[Assert\Uuid]
    //    private UserProfileUid $profile;

    /** Статус заявки - ОТПАРВЛЕН НА СКЛАД */
    #[Assert\NotBlank]
    private readonly MaterialStockStatus $status;

    /** Комментарий */
    private ?string $comment = null;

    //    /** Вспомогательные свойства - для выбора доступных профилей */
    //    private readonly UserUid $usr;

    /** Коллекция перемещения  */
    #[Assert\Valid]
    private ?Move\MaterialStockMoveDTO $move;

    /** Фиксация заявки пользователем  */
    #[Assert\IsNull]
    private readonly ?UserProfileUid $fixed;

    /** Коллекция сырья  */
    #[Assert\Valid]
    private ArrayCollection $material;


    #[Assert\Valid]
    private Invariable\WarehouseMaterialInvariableDTO $invariable;


    public function __construct(User|UserUid $usr)
    {
        //$this->usr = $usr instanceof User ? $usr->getId() : $usr;
        $this->status = new MaterialStockStatus(MaterialStockStatusWarehouse::class);
        $this->fixed = null;
        $this->material = new ArrayCollection();

        $this->invariable = new Invariable\WarehouseMaterialInvariableDTO();
    }

    public function getEvent(): ?MaterialStockEventUid
    {
        return $this->id;
    }

    public function setId(MaterialStockEventUid $id): void
    {
        $this->id = $id;
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

    /** Профиль назначения */
    public function getProfile(): ?UserProfileUid
    {
        return $this->invariable->getProfile();
    }

    public function setProfile(UserProfileUid $profile): self
    {
        $this->invariable->setProfile($profile);
        return $this;
    }


    /** Статус заявки - ПРИХОД */
    public function getStatus(): MaterialStockStatus
    {
        return $this->status;
    }

    /**
     * Usr
     */
    public function getUsr(): UserUid
    {
        return $this->invariable->getUsr();
    }

    /**
     * Move
     */
    public function getMove(): ?Move\MaterialStockMoveDTO
    {
        return $this->move;
    }

    public function setMove(?Move\MaterialStockMoveDTO $move): self
    {
        $this->move = $move;
        return $this;
    }


    /** Фиксация заявки пользователем  */
    public function getFixed(): ?UserProfileUid
    {
        return $this->fixed;
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
        $this->material->add($material);
    }

    /**
     * Invariable
     */
    public function getInvariable(): Invariable\WarehouseMaterialInvariableDTO
    {
        return $this->invariable;
    }
}
