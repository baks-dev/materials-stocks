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

namespace BaksDev\Materials\Stocks\Entity\Stock\Materials;

use BaksDev\Core\Entity\EntityEvent;
use BaksDev\Materials\Catalog\Type\Id\MaterialUid;
use BaksDev\Materials\Catalog\Type\Offers\ConstId\MaterialOfferConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\MaterialVariationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\ConstId\MaterialModificationConst;
use BaksDev\Materials\Stocks\Entity\Stock\Event\MaterialStockEvent;
use BaksDev\Materials\Stocks\Type\Material\MaterialStockCollectionUid;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

// MaterialStockProductMaterial

#[ORM\Entity]
#[ORM\Table(name: 'material_stock_material')]
class MaterialStockMaterial extends EntityEvent
{
    /** ID */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\Column(type: MaterialStockCollectionUid::TYPE)]
    private MaterialStockCollectionUid $id;

    /** ID события */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\ManyToOne(targetEntity: MaterialStockEvent::class, inversedBy: 'material')]
    #[ORM\JoinColumn(name: 'event', referencedColumnName: 'id')]
    private MaterialStockEvent $event;

    /** ID продукта */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Column(type: MaterialUid::TYPE)]
    private MaterialUid $material;

    /** Постоянный уникальный идентификатор ТП */
    #[Assert\Uuid]
    #[ORM\Column(type: MaterialOfferConst::TYPE, nullable: true)]
    private ?MaterialOfferConst $offer;

    /** Постоянный уникальный идентификатор варианта */
    #[Assert\Uuid]
    #[ORM\Column(type: MaterialVariationConst::TYPE, nullable: true)]
    private ?MaterialVariationConst $variation;

    /** Постоянный уникальный идентификатор модификации */
    #[Assert\Uuid]
    #[ORM\Column(type: MaterialModificationConst::TYPE, nullable: true)]
    private ?MaterialModificationConst $modification;

    /** Количество */
    #[Assert\Range(min: 1)]
    #[ORM\Column(type: Types::INTEGER)]
    private int $total;

    /** Место складирования */
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $storage = null;

    public function __construct(MaterialStockEvent $event)
    {
        $this->event = $event;
        $this->id = new MaterialStockCollectionUid();
    }

    public function __clone(): void
    {
        $this->id = clone $this->id;
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }

    public function getDto($dto): mixed
    {
        $dto = is_string($dto) && class_exists($dto) ? new $dto() : $dto;

        if($dto instanceof MaterialStockMaterialInterface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function setEntity($dto): mixed
    {
        if($dto instanceof MaterialStockMaterialInterface || $dto instanceof self)
        {
            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function getMaterial(): MaterialUid
    {
        return $this->material;
    }

    public function getOffer(): ?MaterialOfferConst
    {
        return $this->offer;
    }

    public function getVariation(): ?MaterialVariationConst
    {
        return $this->variation;
    }

    public function getModification(): ?MaterialModificationConst
    {
        return $this->modification;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getStorage(): ?string
    {
        return $this->storage;
    }

    public function getEvent(): MaterialStockEvent
    {
        return $this->event;
    }
}
