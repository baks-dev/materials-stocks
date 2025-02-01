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
use BaksDev\Materials\Stocks\Entity\Stock\Materials\MaterialStockMaterial;
use BaksDev\Materials\Stocks\Messenger\MaterialStockMessage;
use BaksDev\Materials\Stocks\Repository\MaterialStocksById\MaterialStocksByIdInterface;
use BaksDev\Materials\Stocks\Repository\MaterialStocksEvent\MaterialStocksEventInterface;
use BaksDev\Materials\Stocks\Type\Status\MaterialStockstatus\Collection\MaterialStockStatusIncoming;
use BaksDev\Products\Product\Repository\CurrentProductIdentifier\CurrentProductIdentifierByConstInterface;
use BaksDev\Products\Product\Repository\UpdateProductQuantity\AddProductQuantityInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(priority: 1)]
final readonly class AddQuantityProductByIncomingMaterialStock
{
    public function __construct(
        #[Target('materialsProductLogger')] private LoggerInterface $logger,
        private CurrentProductIdentifierByConstInterface $currentProductIdentifierByConst,
        private AddProductQuantityInterface $addProductQuantity,
        private MaterialStocksEventInterface $MaterialStocksEventRepository,
        private MaterialStocksByIdInterface $materialStocks,
        private DeduplicatorInterface $deduplicator,
    ) {}

    /**
     * Пополнение наличием продукции в карточке при поступлении на склад
     */
    public function __invoke(MaterialStockMessage $message): void
    {
        $MaterialStockEvent = $this
            ->MaterialStocksEventRepository
            ->find($message->getEvent());

        if($MaterialStockEvent === false)
        {
            return;
        }

        // Если статус не является Incoming «Приход на склад»
        if(false === $MaterialStockEvent->equalsMaterialStockstatus(MaterialStockStatusIncoming::class))
        {
            return;
        }

        // Получаем всю продукцию в ордере со статусом Incoming
        $materials = $this->materialStocks->getMaterialsIncomingStocks($message->getId());

        if(empty($materials))
        {
            $this->logger->warning('Заявка не имеет продукции в коллекции', [self::class.':'.__LINE__]);
            return;
        }

        $Deduplicator = $this->deduplicator
            ->namespace('materials-stocks')
            ->deduplication([
                (string) $message->getId(),
                MaterialStockStatusIncoming::STATUS,
                md5(self::class)
            ]);

        if($Deduplicator->isExecuted())
        {
            return;
        }

        /** @var MaterialStockMaterial $material */
        foreach($materials as $material)
        {
            /** Пополняем наличие карточки */
            $this->changeTotal($material);
        }


        $Deduplicator->save();
    }

    public function changeTotal(MaterialStockMaterial $material): void
    {

        $context = [
            self::class.':'.__LINE__,
            'total' => $material->getTotal(),
            'ProductUid' => (string) $material->getMaterial(),
            'MaterialStockEventUid' => (string) $material->getEvent()->getId(),
            'MaterialOfferConst' => (string) $material->getOffer(),
            'MaterialVariationConst' => (string) $material->getVariation(),
            'MaterialModificationConst' => (string) $material->getModification(),
        ];

        $CurrentProductDTO = $this->currentProductIdentifierByConst
            ->forMaterial($material->getMaterial())
            ->forOfferConst($material->getOffer())
            ->forVariationConst($material->getVariation())
            ->forModificationConst($material->getModification())
            ->execute();

        if($CurrentProductDTO === false)
        {
            $this->logger->critical('Поступление на склад: Невозможно пополнить общий остаток (карточка не найдена)', $context);
            return;
        }

        $rows = $this->addProductQuantity
            ->forEvent($CurrentProductDTO->getEvent())
            ->forOffer($CurrentProductDTO->getOffer())
            ->forVariation($CurrentProductDTO->getVariation())
            ->forModification($CurrentProductDTO->getModification())
            ->addQuantity($material->getTotal())
            ->addReserve(false)
            ->update();

        if($rows)
        {
            $this->logger->info('Поступление на склад: Пополнили общий остаток в карточке', $context);
        }
        else
        {
            $this->logger->critical('Поступление на склад: Невозможно пополнить общий остаток (карточка не найдена)', $context);
        }
    }
}
