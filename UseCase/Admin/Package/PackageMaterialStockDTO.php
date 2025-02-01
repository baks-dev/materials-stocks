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

namespace BaksDev\Materials\Stocks\UseCase\Admin\Package;

use BaksDev\Contacts\Region\Type\Call\Const\ContactsRegionCallConst;
use BaksDev\Core\Type\UidType\Uid;
use BaksDev\Materials\Stocks\Entity\Stock\Event\MaterialStockEventInterface;
use BaksDev\Materials\Stocks\Type\Event\MaterialStockEventUid;
use BaksDev\Materials\Stocks\Type\Status\MaterialStockStatus;
use BaksDev\Materials\Stocks\Type\Status\MaterialStockstatus\Collection\MaterialStockStatusPackage;
use BaksDev\Orders\Order\Entity\Event\OrderEventInterface;
use BaksDev\Orders\Order\Entity\Invariable\OrderInvariableInterface;
use BaksDev\Orders\Order\Type\Event\OrderEventUid;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Users\User\Entity\User;
use BaksDev\Users\User\Type\Id\UserUid;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

/** @see MaterialStockEvent */
final class PackageMaterialStockDTO implements MaterialStockEventInterface, OrderEventInterface
{
    /** Идентификатор */
    private ?MaterialStockEventUid $id = null;

    /**
     * Ответственное лицо (Профиль пользователя)
     * @deprecated Переносится в Invariable
     */
    #[Assert\Uuid]
    private ?UserProfileUid $profile = null;

    /** Статус заявки - УПАКОВКА */
    #[Assert\NotBlank]
    private readonly MaterialStockStatus $status;

    /** Номер заявки */
    #[Assert\NotBlank]
    #[Assert\Type('string')]
    #[Assert\Length(max: 36)]
    private string $number;

    /** Постоянная величина */
    #[Assert\Valid]
    private readonly Invariable\PackageOrderInvariableDTO $invariable;


    //    /** Константа Целевого склада */
    //    #[Assert\NotBlank]
    //    #[Assert\Uuid]
    //    private ?ContactsRegionCallConst $warehouse = null;

    //    /** Константа склада назначения при перемещении */
    //    #[Assert\NotBlank]
    //    #[Assert\Uuid]
    //    private ?ContactsRegionCallConst $destination = null;


    /** Идентификатор заказа на сборку */
    private Orders\MaterialStockOrderDTO $ord;

    /** Коллекция продукции  */
    #[Assert\Valid]
    private ArrayCollection $material;

    /** Комментарий */
    private ?string $comment = null;

    /** Вспомогательные свойства */
    private readonly UserUid $usr;


    public function __construct(User|UserUid $user)
    {
        $user = $user instanceof User ? $user->getId() : $user;

        $this->usr = $user;

        $this->status = new MaterialStockStatus(MaterialStockStatusPackage::class);
        $this->material = new ArrayCollection();

        //$this->number = number_format(microtime(true) * 100, 0, '.', '.');

        $this->ord = new Orders\MaterialStockOrderDTO();


        $PackageOrderInvariable = new Invariable\PackageOrderInvariableDTO();
        $PackageOrderInvariable->setUsr($user);

        $this->invariable = $PackageOrderInvariable;

    }


    public function getEvent(): ?Uid
    {
        return null;
    }

    public function setId(MaterialStockEventUid|OrderEventUid $id): void
    {
        if($id instanceof MaterialStockEventUid)
        {
            $this->id = $id;
        }
    }

    public function resetId(): void
    {
        $this->id = null;
    }

    /** Коллекция продукции  */
    public function getMaterial(): ArrayCollection
    {
        return $this->material;
    }

    public function setMaterial(ArrayCollection $material): void
    {
        $this->material = $material;
    }

    public function addMaterial(Products\MaterialStockDTO $material): void
    {
        $this->material->add($material);
    }

    public function removeMaterial(Products\MaterialStockDTO $material): void
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

        /** Присваиваем постоянную величину  */
        $PackageOrderInvariable = $this->getInvariable();
        $PackageOrderInvariable->setProfile($profile);

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
        $this->invariable->setNumber($number);
        $this->number = $number;
    }

    //    /** Константа Целевого склада */
    //    public function getWarehouse(): ?ContactsRegionCallConst
    //    {
    //        return $this->warehouse;
    //    }
    //
    //    public function setWarehouse(?ContactsRegionCallConst $warehouse): void
    //    {
    //        $this->warehouse = $warehouse;
    //    }


    /** Идентификатор заказа на сборку */

    public function getOrd(): Orders\MaterialStockOrderDTO
    {
        return $this->ord;
    }


    public function setOrd(Orders\MaterialStockOrderDTO $ord): void
    {
        $this->ord = $ord;
    }


    /**
     * Usr
     */
    public function getUsr(): UserUid
    {
        return $this->usr;
    }

    /**
     * Invariable
     */
    public function getInvariable(): Invariable\PackageOrderInvariableDTO
    {
        return $this->invariable;
    }


}
