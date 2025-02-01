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

namespace BaksDev\Materials\Stocks\Entity\Stock;

use BaksDev\Materials\Stocks\Entity\Stock\Event\MaterialStockEvent;
use BaksDev\Materials\Stocks\Type\Event\MaterialStockEventUid;
use BaksDev\Materials\Stocks\Type\Id\MaterialStockUid;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\AbstractUid;
use Symfony\Component\Validator\Constraints as Assert;

// MaterialStock

#[ORM\Entity]
#[ORM\Table(name: 'material_stock')]
class MaterialStock
{
    /** ID */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\Column(type: MaterialStockUid::TYPE)]
    private MaterialStockUid $id;

    /** ID События */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Column(type: MaterialStockEventUid::TYPE, unique: true)]
    private MaterialStockEventUid $event;

    public function __construct(AbstractUid|string|null $id = null)
    {
        $this->id = new MaterialStockUid($id);
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }

    public function getId(): MaterialStockUid
    {
        return $this->id;
    }

    public function getEvent(): MaterialStockEventUid
    {
        return $this->event;
    }

    public function setEvent(MaterialStockEventUid|MaterialStockEvent $event): void
    {
        $this->event = $event instanceof MaterialStockEvent ? $event->getId() : $event;
    }
}
