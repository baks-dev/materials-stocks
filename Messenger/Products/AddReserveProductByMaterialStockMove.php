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

namespace BaksDev\Materials\Stocks\Messenger\Products;

use BaksDev\Core\Deduplicator\DeduplicatorInterface;
use BaksDev\Materials\Stocks\Entity\Stock\Event\MaterialStockEvent;
use BaksDev\Materials\Stocks\Entity\Stock\Materials\MaterialStockMaterial;
use BaksDev\Materials\Stocks\Messenger\MaterialStockMessage;
use BaksDev\Materials\Stocks\Repository\MaterialStocksById\MaterialStocksByIdInterface;
use BaksDev\Materials\Stocks\Type\Status\MaterialStockstatus\Collection\MaterialStockStatusMoving;
use BaksDev\Products\Product\Repository\ProductQuantity\ProductModificationQuantityInterface;
use BaksDev\Products\Product\Repository\ProductQuantity\ProductOfferQuantityInterface;
use BaksDev\Products\Product\Repository\ProductQuantity\ProductQuantityInterface;
use BaksDev\Products\Product\Repository\ProductQuantity\ProductVariationQuantityInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Добавляет резерв продукции при перемещении
 */
#[AsMessageHandler(priority: 1)]
final readonly class AddReserveProductByMaterialStockMove
{
    public function __construct(
        #[Target('materialsProductLogger')] private LoggerInterface $logger,
        private MaterialStocksByIdInterface $materialStocks,
        private ProductModificationQuantityInterface $modificationQuantity,
        private ProductVariationQuantityInterface $variationQuantity,
        private ProductOfferQuantityInterface $offerQuantity,
        private ProductQuantityInterface $materialQuantity,
        private EntityManagerInterface $entityManager,
        private DeduplicatorInterface $deduplicator,
    ) {}

    /**
     * Добавляет резерв продукции при перемещении
     */
    public function __invoke(MaterialStockMessage $message): void
    {

        $this->entityManager->clear();

        $MaterialStockEvent = $this->entityManager
            ->getRepository(MaterialStockEvent::class)
            ->find($message->getEvent());

        if(!$MaterialStockEvent)
        {
            return;
        }

        /** Если Статус не является Статус Moving «Перемещение» */
        if(false === $MaterialStockEvent->getStatus()->equals(MaterialStockStatusMoving::class))
        {
            return;
        }

        // Получаем всю продукцию в ордере со статусом Moving (перемещение)
        $materials = $this->materialStocks->getMaterialsMovingStocks($message->getId());

        if(empty($materials))
        {
            $this->logger->warning('Заявка не имеет продукции в коллекции', [self::class.':'.__LINE__]);
            return;
        }

        $Deduplicator = $this->deduplicator
            ->namespace('materials-stocks')
            ->deduplication([
                (string) $message->getId(),
                MaterialStockStatusMoving::STATUS,
                md5(self::class)
            ]);

        if($Deduplicator->isExecuted())
        {
            return;
        }

        $this->entityManager->clear();

        /** @var MaterialStockMaterial $material */
        foreach($materials as $material)
        {
            $this->changeReserve($material);
        }

        $Deduplicator->save();
    }


    public function changeReserve(MaterialStockMaterial $material): void
    {
        $MaterialUpdateReserve = null;

        // Количественный учет модификации множественного варианта торгового предложения
        if(null === $MaterialUpdateReserve && $material->getModification())
        {
            $this->entityManager->clear();

            $MaterialUpdateReserve = $this->modificationQuantity->getMaterialModificationQuantity(
                $material->getMaterial(),
                $material->getOffer(),
                $material->getVariation(),
                $material->getModification()
            );
        }

        // Количественный учет множественного варианта торгового предложения
        if(null === $MaterialUpdateReserve && $material->getVariation())
        {
            $this->entityManager->clear();

            $MaterialUpdateReserve = $this->variationQuantity->getMaterialVariationQuantity(
                $material->getMaterial(),
                $material->getOffer(),
                $material->getVariation()
            );
        }

        // Количественный учет торгового предложения
        if(null === $MaterialUpdateReserve && $material->getOffer())
        {
            $this->entityManager->clear();

            $MaterialUpdateReserve = $this->offerQuantity->getMaterialOfferQuantity(
                $material->getMaterial(),
                $material->getOffer()
            );
        }

        // Количественный учет продукта
        if(null === $MaterialUpdateReserve)
        {
            $this->entityManager->clear();

            $MaterialUpdateReserve = $this->materialQuantity->getMaterialQuantity(
                $material->getMaterial()
            );
        }


        $context = [
            self::class.':'.__LINE__,
            'total' => $material->getTotal(),
            'ProductUid' => (string) $material->getMaterial(),
            'MaterialStockEventUid' => (string) $material->getEvent()->getId(),
            'MaterialOfferConst' => (string) $material->getOffer(),
            'MaterialVariationConst' => (string) $material->getVariation(),
            'MaterialModificationConst' => (string) $material->getModification(),
        ];

        if($MaterialUpdateReserve && $MaterialUpdateReserve->addReserve($material->getTotal()))
        {
            $this->entityManager->flush();
            $this->logger->info('Перемещение: Добавили общий резерв продукции в карточке', $context);
            return;
        }

        $this->logger->critical('Перемещение: Невозможно добавить общий резерв продукции (карточка не найдена)', $context);
    }
}
