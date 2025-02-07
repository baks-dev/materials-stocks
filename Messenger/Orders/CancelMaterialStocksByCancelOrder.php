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

namespace BaksDev\Materials\Stocks\Messenger\Orders;

use BaksDev\Core\Deduplicator\DeduplicatorInterface;
use BaksDev\Materials\Stocks\Entity\Stock\Event\MaterialStockEvent;
use BaksDev\Materials\Stocks\Entity\Stock\MaterialStock;
use BaksDev\Materials\Stocks\Repository\MaterialStocksByOrder\MaterialStocksByOrderInterface;
use BaksDev\Materials\Stocks\Type\Status\MaterialStockStatus\Collection\MaterialStockStatusCancel;
use BaksDev\Materials\Stocks\UseCase\Admin\Cancel\CancelMaterialStockDTO;
use BaksDev\Materials\Stocks\UseCase\Admin\Cancel\CancelMaterialStockHandler;
use BaksDev\Orders\Order\Messenger\OrderMessage;
use BaksDev\Orders\Order\Repository\CurrentOrderEvent\CurrentOrderEventInterface;
use BaksDev\Orders\Order\Type\Status\OrderStatus\OrderStatusCanceled;
use BaksDev\Orders\Order\UseCase\Admin\Canceled\CanceledOrderDTO;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CancelMaterialStocksByCancelOrder
{
    public function __construct(
        #[Target('materialsStocksLogger')] private LoggerInterface $logger,
        private CurrentOrderEventInterface $currentOrderEvent,
        private MaterialStocksByOrderInterface $materialStocksByOrder,
        private CancelMaterialStockHandler $cancelMaterialStockHandler,
        private DeduplicatorInterface $deduplicator,
    ) {}


    /**
     * Отменяем складскую заявку при отмене заказа
     */

    public function __invoke(OrderMessage $message): void
    {
        $Deduplicator = $this->deduplicator
            ->namespace('materials-stocks')
            ->deduplication([
                (string) $message->getId(),
                OrderStatusCanceled::STATUS,
                self::class
            ]);

        if($Deduplicator->isExecuted())
        {
            return;
        }

        /** Получаем активное состояние заказа */
        $OrderEvent = $this->currentOrderEvent
            ->forOrder($message->getId())
            ->find();

        if(!$OrderEvent)
        {
            return;
        }

        /** Если статус заказа не Canceled «Отменен» - завершаем обработчик */
        if(false === $OrderEvent->isStatusEquals(OrderStatusCanceled::class))
        {
            return;
        }

        /** Получаем все заявки по идентификатору заказа */
        $stocks = $this->materialStocksByOrder->findByOrder($message->getId());

        if(empty($stocks))
        {
            return;
        }

        /** @var MaterialStockEvent $MaterialStockEvent */
        foreach($stocks as $MaterialStockEvent)
        {
            /** Если статус складской заявки Canceled «Отменен» - пропускаем */
            if(true === $MaterialStockEvent->equalsMaterialStockStatus(MaterialStockStatusCancel::class))
            {
                continue;
            }

            /**
             * Присваиваем рандомные пользователя и профиль,
             * т.к. при отмене заявки нам важен только комментарий
             */
            $OrderCanceledDTO = new CanceledOrderDTO();
            $OrderEvent->getDto($OrderCanceledDTO);

            $CancelMaterialStockDTO = new CancelMaterialStockDTO();
            $MaterialStockEvent->getDto($CancelMaterialStockDTO);
            $CancelMaterialStockDTO->setComment($OrderCanceledDTO->getComment());

            $MaterialStock = $this->cancelMaterialStockHandler->handle($CancelMaterialStockDTO);

            if($MaterialStock instanceof MaterialStock)
            {
                $this->logger->info(sprintf('Отменили складскую заявку %s при отмене заказа', $MaterialStockEvent->getNumber()));
                continue;
            }

            $this->logger->critical('Ошибка отмены складской заявки', [
                self::class.':'.__LINE__,
                'MaterialStockEventUid' => (string) $MaterialStockEvent->getId(),
                'OrderUid' => (string) $message->getId()
            ]);
        }

        $Deduplicator->save();
    }
}
