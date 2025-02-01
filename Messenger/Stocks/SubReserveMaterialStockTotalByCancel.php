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

namespace BaksDev\Materials\Stocks\Messenger\Stocks;

use BaksDev\Core\Deduplicator\DeduplicatorInterface;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Materials\Stocks\Entity\Stock\Event\MaterialStockEvent;
use BaksDev\Materials\Stocks\Entity\Stock\Materials\MaterialStockMaterial;
use BaksDev\Materials\Stocks\Messenger\MaterialStockMessage;
use BaksDev\Materials\Stocks\Messenger\Stocks\SubMaterialStocksReserve\SubMaterialStocksTotalReserveMessage;
use BaksDev\Materials\Stocks\Repository\MaterialStocksById\MaterialStocksByIdInterface;
use BaksDev\Materials\Stocks\Type\Status\MaterialStockstatus\Collection\MaterialStockStatusCancel;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(priority: 1)]
final readonly class SubReserveMaterialStockTotalByCancel
{
    public function __construct(
        #[Target('materialsStocksLogger')] private readonly LoggerInterface $logger,
        private MaterialStocksByIdInterface $materialStocks,
        private EntityManagerInterface $entityManager,
        private MessageDispatchInterface $messageDispatch,
        private DeduplicatorInterface $deduplicator,
    ) {}

    /**
     * Создаем события на снятие резерва при отмене складской заявки
     */
    public function __invoke(MaterialStockMessage $message): void
    {

        if($message->getLast() === null)
        {
            return;
        }

        /** Активный статус складской заявки */
        $MaterialStockEvent = $this->entityManager->getRepository(MaterialStockEvent::class)->find($message->getEvent());

        if(!$MaterialStockEvent)
        {
            return;
        }

        // Если статус события заявки не является Cancel «Отменен».
        if(false === $MaterialStockEvent->getStatus()->equals(MaterialStockStatusCancel::class))
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
                (string) $message->getId(),
                MaterialStockStatusCancel::STATUS,
                md5(self::class)
            ]);

        if($Deduplicator->isExecuted())
        {
            return;
        }

        /** Идентификатор профиля склада отгрузки, где производится отмена заявки */
        $UserProfileUid = $MaterialStockEvent->getProfile();

        /** @var MaterialStockMaterial $material */
        foreach($materials as $material)
        {
            $this->logger->info(
                'Отменяем резерв на складе при отмене складской заявки',
                [
                    self::class.':'.__LINE__,
                    'number' => $MaterialStockEvent->getNumber(),
                    'total' => $material->getTotal(),
                    'MaterialStockEventUid' => (string) $message->getEvent(),
                    'UserProfileUid' => (string) $UserProfileUid,
                    'ProductUid' => (string) $material->getMaterial(),
                    'MaterialOfferConst' => (string) $material->getOffer(),
                    'MaterialVariationConst' => (string) $material->getVariation(),
                    'MaterialModificationConst' => (string) $material->getModification(),
                ]
            );

            /** Снимаем ТОЛЬКО резерв продукции на складе */
            for($i = 1; $i <= $material->getTotal(); $i++)
            {
                $SubMaterialStocksTotalCancelMessage = new SubMaterialStocksTotalReserveMessage(
                    $UserProfileUid,
                    $material->getMaterial(),
                    $material->getOffer(),
                    $material->getVariation(),
                    $material->getModification()
                );

                $this->messageDispatch->dispatch($SubMaterialStocksTotalCancelMessage, transport: 'materials-stocks');

                if($i === $material->getTotal())
                {
                    break;
                }
            }
        }

        $Deduplicator->save();
    }
}
