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

namespace BaksDev\Materials\Stocks\UseCase\Admin\EditTotal;

use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Core\Validator\ValidatorCollectionInterface;
use BaksDev\Materials\Stocks\Entity\Stock\MaterialStock;
use BaksDev\Materials\Stocks\Entity\Total\MaterialStockTotal;
use BaksDev\Materials\Stocks\UseCase\Admin\Storage\MaterialStockStorageEditDTO;
use Doctrine\ORM\EntityManagerInterface;

final readonly class MaterialStockTotalEditHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorCollectionInterface $validatorCollection,
        private MessageDispatchInterface $messageDispatch
    ) {}

    /** @see MaterialStock */
    public function handle(MaterialStockTotalEditDTO|MaterialStockStorageEditDTO $command): string|MaterialStockTotal
    {
        /** Валидация DTO  */
        $this->validatorCollection->add($command);

        /** @var MaterialStockTotal $MaterialStockTotal */
        $MaterialStockTotal = $this->entityManager
            ->getRepository(MaterialStockTotal::class)
            ->find($command->getId());

        if(
            !$MaterialStockTotal || false === $this->validatorCollection->add($MaterialStockTotal, context: [
                self::class.':'.__LINE__,
                'class' => MaterialStockTotal::class,
                'id' => $command->getId(),
            ])
        )
        {
            return $this->validatorCollection->getErrorUniqid();
        }

        $MaterialStockTotal->setEntity($command);

        /** Валидация всех объектов */
        if($this->validatorCollection->isInvalid())
        {
            return $this->validatorCollection->getErrorUniqid();
        }

        $this->entityManager->flush();


        $this->messageDispatch->addClearCacheOther('materials-stocks');

        return $MaterialStockTotal;
    }
}
