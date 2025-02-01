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

namespace BaksDev\Materials\Stocks\Entity\Stock\Orders;

use BaksDev\Core\Entity\EntityEvent;
use BaksDev\Materials\Stocks\Entity\Stock\Event\MaterialStockEvent;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

/* Заявки на сборку */

#[ORM\Entity]
#[ORM\Table(name: 'material_stock_order')]
#[ORM\Index(columns: ['ord'])]
class MaterialStockOrder extends EntityEvent
{
    /** ID события */
    #[Assert\NotBlank]
    #[ORM\Id]
    #[ORM\OneToOne(targetEntity: MaterialStockEvent::class, inversedBy: 'ord')]
    #[ORM\JoinColumn(name: 'event', referencedColumnName: 'id')]
    private MaterialStockEvent $event;

    /** Идентификатор заказа для сборки */
    #[Assert\NotBlank]
    #[ORM\Column(type: OrderUid::TYPE)]
    private ?OrderUid $ord = null;

    public function __construct(MaterialStockEvent $event)
    {
        $this->event = $event;
    }

    public function __toString(): string
    {
        return (string) $this->event;
    }

    public function getDto($dto): mixed
    {
        $dto = is_string($dto) && class_exists($dto) ? new $dto() : $dto;

        if($dto instanceof MaterialStockOrderInterface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function setEntity($dto): mixed
    {
        if(empty($dto->getOrd()))
        {
            return false;
        }

        if($dto instanceof MaterialStockOrderInterface || $dto instanceof self)
        {
            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    /**
     * Идентификатор заказа
     */
    public function getOrder(): ?OrderUid
    {
        return $this->ord;
    }
}
