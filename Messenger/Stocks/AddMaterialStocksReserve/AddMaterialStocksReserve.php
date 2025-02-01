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

namespace BaksDev\Materials\Stocks\Messenger\Stocks\AddMaterialStocksReserve;

use BaksDev\Materials\Stocks\Entity\Total\MaterialStockTotal;
use BaksDev\Materials\Stocks\Repository\MaterialStockMinQuantity\MaterialStockQuantityInterface;
use BaksDev\Materials\Stocks\Repository\UpdateMaterialStock\AddMaterialStockInterface;
use Doctrine\ORM\EntityManagerInterface;
use DomainException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(priority: 1)]
final readonly class AddMaterialStocksReserve
{

    public function __construct(
        #[Target('materialsStocksLogger')] private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private MaterialStockQuantityInterface $materialStockMinQuantity,
        private AddMaterialStockInterface $addMaterialStock,
    ) {}

    /**
     * Создает резерв на единицу продукции на указанный склад начиная с минимального наличия
     */
    public function __invoke(AddMaterialStocksReserveMessage $message): void
    {
        $this->entityManager->clear();

        $MaterialStockTotal = $this->materialStockMinQuantity
            ->profile($message->getProfile())
            ->material($message->getMaterial())
            ->offerConst($message->getOffer())
            ->variationConst($message->getVariation())
            ->modificationConst($message->getModification())
            ->findOneBySubReserve();

        if(!$MaterialStockTotal)
        {
            $this->logger->critical(
                'Не найдено продукции на складе для резервирования',
                [
                    self::class.':'.__LINE__,
                    'profile' => (string) $message->getProfile(),
                    'material' => (string) $message->getMaterial(),
                    'offer' => (string) $message->getOffer(),
                    'variation' => (string) $message->getVariation(),
                    'modification' => (string) $message->getModification()
                ]
            );

            throw new DomainException('Невозможно добавить резерв на продукцию');

        }

        $this->handle($MaterialStockTotal);
    }

    public function handle(MaterialStockTotal $MaterialStockTotal): void
    {
        /** Добавляем в резерв единицу продукции */
        $rows = $this->addMaterialStock
            ->total(null)
            ->reserve(1)
            ->updateById($MaterialStockTotal);

        if(empty($rows))
        {
            $this->logger->critical(
                'Не найдено продукции на складе для резервирования. Возможно остатки были изменены в указанном месте',
                [
                    self::class.':'.__LINE__,
                    'MaterialStockTotalUid' => (string) $MaterialStockTotal->getId()
                ]
            );

            return;
        }

        $this->logger->info(
            sprintf('%s : Добавили резерв на склад единицы продукции', $MaterialStockTotal->getStorage()),
            [
                self::class.':'.__LINE__,
                'MaterialStockTotalUid' => (string) $MaterialStockTotal->getId()
            ]
        );
    }
}
