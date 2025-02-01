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

namespace BaksDev\Materials\Stocks\Type\Status;

use BaksDev\Materials\Stocks\Type\Status\MaterialStockstatus\MaterialStockStatusInterface;
use InvalidArgumentException;

final class MaterialStockStatus
{
    public const string TYPE = 'material_stock_status_type';

    private ?MaterialStockStatusInterface $status = null;

    public function __construct(MaterialStockStatusInterface|self|string $status)
    {

        if(is_string($status) && class_exists($status))
        {
            $instance = new $status();

            if($instance instanceof MaterialStockStatusInterface)
            {
                $this->status = $instance;
                return;
            }
        }

        if($status instanceof MaterialStockStatusInterface)
        {
            $this->status = $status;
            return;
        }

        if($status instanceof self)
        {
            $this->status = $status->getMaterialStockstatus();
            return;
        }


        /** @var MaterialStockStatusInterface $declare */
        foreach(self::getDeclared() as $declare)
        {
            $instance = new self($declare);

            if($instance->getMaterialStockstatusValue() === $status)
            {
                $this->status = new $declare;
                return;
            }
        }

        throw new InvalidArgumentException(sprintf('Not found MaterialStockstatus %s', $status));


    }

    public function __toString(): string
    {
        return $this->status ? $this->status->getValue() : '';
    }

    /** Возвращает значение (value) страны String */
    public function getMaterialStockstatus(): MaterialStockStatusInterface
    {
        return $this->status;
    }

    /** Возвращает значение (value) страны String */
    public function getMaterialStockstatusValue(): ?string
    {
        return $this->status?->getValue();
    }


    public static function cases(): array
    {
        $case = [];

        foreach(self::getDeclared() as $status)
        {
            /** @var MaterialStockStatusInterface $status */
            $class = new $status;
            $case[] = new self($class);
        }

        return $case;
    }

    public static function getDeclared(): array
    {
        return array_filter(
            get_declared_classes(),
            static function($className) {
                return in_array(MaterialStockStatusInterface::class, class_implements($className), true);
            }
        );
    }


    public function equals(mixed $status): bool
    {
        $status = new self($status);

        return $this->getMaterialStockstatusValue() === $status->getMaterialStockstatusValue();
    }

}
