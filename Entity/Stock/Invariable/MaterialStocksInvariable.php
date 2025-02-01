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

namespace BaksDev\Materials\Stocks\Entity\Stock\Invariable;

use BaksDev\Core\Entity\EntityEvent;
use BaksDev\Core\Entity\EntityReadonly;
use BaksDev\Materials\Catalog\Type\Barcode\ProductBarcode;
use BaksDev\Materials\Catalog\Type\Id\MaterialUid;
use BaksDev\Materials\Catalog\Type\Offers\ConstId\MaterialOfferConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\MaterialVariationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\ConstId\MaterialModificationConst;
use BaksDev\Materials\Stocks\Entity\Stock\Event\MaterialStockEvent;
use BaksDev\Materials\Stocks\Type\Id\MaterialStockUid;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Orders\Order\Type\Product\OrderProductUid;
use BaksDev\Products\Product\Entity\Event\ProductEvent;
use BaksDev\Products\Product\Entity\Product;
use BaksDev\Products\Sign\Entity\Event\ProductSignEvent;
use BaksDev\Products\Sign\Type\Id\ProductSignUid;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Users\User\Type\Id\UserUid;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'material_stock_invariable')]
#[ORM\Index(columns: ['number'])]
class MaterialStocksInvariable extends EntityReadonly
{
    /** ID Material */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\Column(type: MaterialStockUid::TYPE)]
    private readonly MaterialStockUid $main;

    /** ID события */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\OneToOne(targetEntity: MaterialStockEvent::class, inversedBy: 'invariable')]
    #[ORM\JoinColumn(name: 'event', referencedColumnName: 'id')]
    private MaterialStockEvent $event;

    /** Пользователь */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Column(type: UserUid::TYPE)]
    private readonly UserUid $usr;

    /** Профиль пользователя */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Column(type: UserProfileUid::TYPE)]
    private UserProfileUid $profile;

    /** Номер заявки */
    #[Assert\NotBlank]
    #[Assert\Length(max: 36)]
    #[ORM\Column(type: Types::STRING)]
    private string $number;


    public function __construct(MaterialStockEvent $event)
    {
        $this->event = $event;
        $this->main = $event->getMain();
    }

    public function __toString(): string
    {
        return (string) $this->main;
    }

    /**
     * Main
     */
    public function getMain(): MaterialStockUid
    {
        return $this->main;
    }

    public function setEvent(MaterialStockEvent $event): self
    {
        $this->event = $event;
        return $this;
    }

    /**
     * Profile
     */
    public function getProfile(): UserProfileUid
    {
        return $this->profile;
    }


    public function getDto($dto): mixed
    {
        $dto = is_string($dto) && class_exists($dto) ? new $dto() : $dto;

        if($dto instanceof MaterialStocksInvariableInterface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function setEntity($dto): mixed
    {
        if($dto instanceof MaterialStocksInvariableInterface || $dto instanceof self)
        {
            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

}
