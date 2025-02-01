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
use BaksDev\Materials\Stocks\Type\Status\MaterialStockstatus\Collection\MaterialStockStatusCancel;
use BaksDev\Products\Product\Repository\CurrentProductIdentifier\CurrentProductIdentifierByConstInterface;
use BaksDev\Products\Product\Repository\ProductQuantity\ProductModificationQuantityInterface;
use BaksDev\Products\Product\Repository\ProductQuantity\ProductOfferQuantityInterface;
use BaksDev\Products\Product\Repository\ProductQuantity\ProductQuantityInterface;
use BaksDev\Products\Product\Repository\ProductQuantity\ProductVariationQuantityInterface;
use BaksDev\Products\Product\Repository\UpdateProductQuantity\SubProductQuantityInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Снимает ТОЛЬКО! резерв продукции при отмене заявки Cancel «Отменен»
 */
#[AsMessageHandler(priority: 1)]
final readonly class SubReserveProductByMaterialStockCancel
{
    public function __construct(
        #[Target('materialsStocksLogger')] private LoggerInterface $logger,
        private CurrentProductIdentifierByConstInterface $currentProductIdentifierByConst,
        private SubProductQuantityInterface $subProductQuantity,
        private MaterialStocksByIdInterface $materialStocks,
        private EntityManagerInterface $entityManager,
        private DeduplicatorInterface $deduplicator,
    ) {}

    /**
     * Снимает ТОЛЬКО РЕЗЕРВ! продукции в карточке при отмене заявки без заказа
     */
    public function __invoke(MaterialStockMessage $message): void
    {

        if(!$message->getLast())
        {
            return;
        }

        /** Получаем статус заявки */
        $MaterialStockEvent = $this->entityManager
            ->getRepository(MaterialStockEvent::class)
            ->find($message->getEvent());

        if(!$MaterialStockEvent)
        {
            return;
        }

        // Если Статус не является Cancel «Отменен».
        if(false === $MaterialStockEvent->equalsMaterialStockstatus(MaterialStockStatusCancel::class))
        {
            return;
        }

        /** Если заявка по заказу - не снимаем резерв (будет снят при отмене заказа) */
        if($MaterialStockEvent->getOrder())
        {
            return;
        }

        // Получаем всю продукцию в заявке со статусом Cancel «Отменен»
        $materials = $this->materialStocks->getMaterialsCancelStocks($message->getId());

        if(empty($materials))
        {
            $this->logger->warning('Заявка не имеет продукции в коллекции', [self::class.':'.__LINE__]);
            return;
        }

        $Deduplicator = $this->deduplicator
            ->namespace('materials-stocks')
            ->deduplication([
                $message->getId(),
                MaterialStockStatusCancel::STATUS,
                self::class
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
        $CurrentProductDTO = $this->currentProductIdentifierByConst
            ->forMaterial($material->getMaterial())
            ->forOfferConst($material->getOffer())
            ->forVariationConst($material->getVariation())
            ->forModificationConst($material->getModification())
            ->execute();


        $rows = $this->subProductQuantity
            ->forEvent($CurrentProductDTO->getEvent())
            ->forOffer($CurrentProductDTO->getOffer())
            ->forVariation($CurrentProductDTO->getVariation())
            ->forModification($CurrentProductDTO->getModification())
            ->subQuantity(false)
            ->subReserve($material->getTotal())
            ->update();


        $context = [
            self::class.':'.__LINE__,
            'total' => $material->getTotal(),
            'ProductUid' => (string) $material->getMaterial(),
            'MaterialStockEventUid' => (string) $material->getEvent()->getId(),
            'MaterialOfferConst' => (string) $material->getOffer(),
            'MaterialVariationConst' => (string) $material->getVariation(),
            'MaterialModificationConst' => (string) $material->getModification(),
        ];

        if($rows)
        {
            $this->logger->info('Перемещение: Отменили общий резерв в карточке при отмене складской заявки на перемещение', $context);
        }
        else
        {
            $this->logger->critical('Перемещение: Невозможно отменить общий резерв продукции (карточка не найдена либо недостаточное количество резерва)', $context);
        }

    }
}
