<?php
/*
 *  Copyright 2025.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Materials\Stocks\Messenger\Stocks\SubMaterialStocksReserve;

use BaksDev\Materials\Stocks\Entity\Total\MaterialStockTotal;
use BaksDev\Materials\Stocks\Repository\MaterialStockMinQuantity\MaterialStockQuantityInterface;
use BaksDev\Materials\Stocks\Repository\UpdateMaterialStock\SubMaterialStockInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(priority: 1)]
final readonly class SubMaterialStocksTotalReserve
{
    public function __construct(
        #[Target('materialsStocksLogger')] private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private MaterialStockQuantityInterface $materialStockMinQuantity,
        private SubMaterialStockInterface $updateMaterialStock,
    ) {}

    /**
     * Снимает резерв на единицу сырья с указанного склада с мест, начиная с максимального резерва
     */
    public function __invoke(SubMaterialStocksTotalReserveMessage $message): void
    {
        $this->entityManager->clear();

        /* Получаем одно место складирования с максимальным количеством сырья и резервом > 0 */
        $MaterialStockTotal = $this->materialStockMinQuantity
            ->profile($message->getProfile())
            ->material($message->getMaterial())
            ->offerConst($message->getOffer())
            ->variationConst($message->getVariation())
            ->modificationConst($message->getModification())
            ->findOneByReserveMax();

        if(!$MaterialStockTotal)
        {
            $this->logger->critical(
                'Не найдено сырья на складе для списания, либо нет резерва на указанную сырьё',
                [
                    self::class.':'.__LINE__,
                    'profile' => (string) $message->getProfile(),
                    'material' => (string) $message->getMaterial(),
                    'offer' => (string) $message->getOffer(),
                    'variation' => (string) $message->getVariation(),
                    'modification' => (string) $message->getModification()
                ]
            );

            return;
        }

        $this->handle($MaterialStockTotal);

    }

    public function handle(MaterialStockTotal $MaterialStockTotal): void
    {
        $rows = $this->updateMaterialStock
            ->total(null)
            ->reserve(1)
            ->updateById($MaterialStockTotal);

        if(empty($rows))
        {
            $this->logger->critical(
                'Невозможно снять резерв единицы сырья, которой заранее не зарезервирована',
                [
                    self::class.':'.__LINE__,
                    'MaterialStockTotalUid' => (string) $MaterialStockTotal->getId()
                ]
            );

            return;
        }

        $this->logger->info(
            sprintf('Место %s: Сняли резерв сырья на складе на одну единицу', $MaterialStockTotal->getStorage()),
            [
                self::class.':'.__LINE__,
                'MaterialStockTotalUid' => (string) $MaterialStockTotal->getId()
            ]
        );
    }
}
