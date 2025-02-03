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

namespace BaksDev\Materials\Stocks\Entity\Stock\Event;

use BaksDev\Core\Entity\EntityEvent;
use BaksDev\Materials\Stocks\Entity\Stock\Invariable\MaterialStocksInvariable;
use BaksDev\Materials\Stocks\Entity\Stock\Materials\MaterialStockMaterial;
use BaksDev\Materials\Stocks\Entity\Stock\MaterialStock;
use BaksDev\Materials\Stocks\Entity\Stock\Modify\MaterialStockModify;
use BaksDev\Materials\Stocks\Entity\Stock\Move\MaterialStockMove;
use BaksDev\Materials\Stocks\Entity\Stock\Orders\MaterialStockOrder;
use BaksDev\Materials\Stocks\Type\Event\MaterialStockEventUid;
use BaksDev\Materials\Stocks\Type\Id\MaterialStockUid;
use BaksDev\Materials\Stocks\Type\Status\MaterialStockStatus;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Users\User\Type\Id\UserUid;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

// MaterialStockEvent

#[ORM\Entity]
#[ORM\Table(name: 'material_stock_event')]
class MaterialStockEvent extends EntityEvent
{
    /** ID */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\Column(type: MaterialStockEventUid::TYPE)]
    private MaterialStockEventUid $id;

    /** ID MaterialStock */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Column(type: MaterialStockUid::TYPE, nullable: false)]
    private ?MaterialStockUid $main = null;


    /** Статус заявки */
    #[Assert\NotBlank]
    #[ORM\Column(type: MaterialStockStatus::TYPE)]
    protected MaterialStockStatus $status;

    /** Коллекция сырья в заявке */
    #[Assert\Valid]
    #[Assert\Count(min: 1)]
    #[ORM\OneToMany(targetEntity: MaterialStockMaterial::class, mappedBy: 'event', cascade: ['all'])]
    protected Collection $material;

    /** Модификатор */
    #[ORM\OneToOne(targetEntity: MaterialStockModify::class, mappedBy: 'event', cascade: ['all'])]
    private MaterialStockModify $modify;

    /** Фиксация заявки пользователем  */
    #[Assert\Uuid]
    #[ORM\Column(type: UserProfileUid::TYPE, nullable: true)]
    private ?UserProfileUid $fixed = null;

    /**
     * Постоянная величина
     */
    #[ORM\OneToOne(targetEntity: MaterialStocksInvariable::class, mappedBy: 'event', cascade: ['all'])]
    private ?MaterialStocksInvariable $invariable = null;

    /** Профиль назначения (при перемещении) */
    #[ORM\OneToOne(targetEntity: MaterialStockMove::class, mappedBy: 'event', cascade: ['all'])]
    private ?MaterialStockMove $move = null;

    /** ID Заказа на сборку */
    #[ORM\OneToOne(targetEntity: MaterialStockOrder::class, mappedBy: 'event', cascade: ['all'])]
    private ?MaterialStockOrder $ord = null;

    /** Комментарий */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $comment = null;

    public function __construct()
    {
        $this->id = new MaterialStockEventUid();
        $this->modify = new MaterialStockModify($this);
    }

    public function __clone()
    {
        $this->id = clone $this->id;
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }

    public function getId(): MaterialStockEventUid
    {
        return $this->id;
    }

    public function setMain(MaterialStockUid|MaterialStock $main): void
    {
        $this->main = $main instanceof MaterialStock ? $main->getId() : $main;
    }

    public function getMain(): ?MaterialStockUid
    {
        return $this->main;
    }

    public function getNumber(): string
    {
        return $this->invariable->getNumber();
    }

    public function getProfile(): ?UserProfileUid
    {
        return $this->invariable->getProfile();
    }

    public function equalsMaterialStockStatus(mixed $status): bool
    {
        return $this->status->equals($status);
    }

    /**
     * Fixed
     */
    public function isFixed(): bool
    {
        return $this->fixed === null;
    }





    /**
     * Идентификатор заказа.
     */
    public function getOrder(): ?OrderUid
    {
        return $this->ord?->getOrder();
    }

    /**
     * Идентификатор заказа при перемещении.
     */
    public function getMoveOrder(): ?OrderUid
    {
        return $this->move?->getOrder();
    }

    /**
     * Идентификатор целевого склада при перемещении.
     */
    public function getMoveDestination(): ?UserProfileUid
    {
        return $this->move?->getDestination();
    }


    public function getMove(): ?MaterialStockMove
    {
        return $this->move;
    }

    /**
     * Идентификатор ответственного.
     */
    public function getStocksProfile(): UserProfileUid
    {
        return $this->invariable->getProfile();
    }

    /**
     * Material
     */
    public function getMaterial(): Collection
    {
        return $this->material;
    }

    public function getModifyUser(): UserUid|null
    {
        return $this->modify->getUser();
    }

    public function getDto($dto): mixed
    {
        $dto = is_string($dto) && class_exists($dto) ? new $dto() : $dto;

        if($dto instanceof MaterialStockEventInterface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function setEntity($dto): mixed
    {
        if($dto instanceof MaterialStockEventInterface || $dto instanceof self)
        {
            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }
}
