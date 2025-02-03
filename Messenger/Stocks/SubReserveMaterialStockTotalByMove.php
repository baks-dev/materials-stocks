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
use BaksDev\Materials\Stocks\Messenger\Stocks\SubMaterialStocksTotal\SubMaterialStocksTotalAndReserveMessage;
use BaksDev\Materials\Stocks\Repository\MaterialStocksById\MaterialStocksByIdInterface;
use BaksDev\Materials\Stocks\Type\Status\MaterialStockStatus\Collection\MaterialStockStatusMoving;
use BaksDev\Materials\Stocks\Type\Status\MaterialStockStatus\Collection\MaterialStockStatusWarehouse;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(priority: 1)]
final class SubReserveMaterialStockTotalByMove
{
    public function __construct(
        #[Target('materialsStocksLogger')] private readonly LoggerInterface $logger,
        private MaterialStocksByIdInterface $materialStocks,
        private EntityManagerInterface $entityManager,
        private MessageDispatchInterface $messageDispatch,
        private DeduplicatorInterface $deduplicator,
    ) {}

    /**
     * Снимаем резерв и наличие со склада отгрузки при статусе Moving «Перемещение»
     */
    public function __invoke(MaterialStockMessage $message): void
    {
        if($message->getLast() === null)
        {
            return;
        }

        /** Получаем статус прошлого события заявки */
        /** @var MaterialStockEvent $MaterialStockEventLast */

        $MaterialStockEventLast = $this->entityManager
            ->getRepository(MaterialStockEvent::class)
            ->find($message->getLast());

        /** Если статус предыдущего события заявки не является Moving «Перемещение» - завершаем обработчик*/
        if(!$MaterialStockEventLast || false === $MaterialStockEventLast->equalsMaterialStockStatus(MaterialStockStatusMoving::class))
        {
            return;
        }

        /** Получаем статус активного события заявки */
        /** @var MaterialStockEvent $MaterialStockEvent */
        $MaterialStockEvent = $this->entityManager
            ->getRepository(MaterialStockEvent::class)
            ->find($message->getEvent());


        /** Если статус активного события не является Warehouse «Отправили на склад» */
        if(!$MaterialStockEvent || false === $MaterialStockEvent->equalsMaterialStockStatus(MaterialStockStatusWarehouse::class))
        {
            return;
        }

        // Получаем всю сырьё в заявке которая перемещается со склада
        $materials = $this->materialStocks->getMaterialsWarehouseStocks($message->getId());

        if(empty($materials))
        {
            $this->logger->warning('Заявка не имеет сырья в коллекции', [self::class.':'.__LINE__]);
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

        /** Идентификатор профиля склада отгрузки (из прошлого события!) */
        $UserProfileUid = $MaterialStockEventLast->getProfile();

        /** @var MaterialStockMaterial $material */
        foreach($materials as $material)
        {

            $this->logger->info(
                'Снимаем резерв и наличие на складе грузоотправителя при перемещении сырья',
                [
                    self::class.':'.__LINE__,
                    'number' => $MaterialStockEvent->getNumber(),
                    'event' => (string) $message->getEvent(),
                    'profile' => (string) $MaterialStockEvent->getProfile(),
                    'material' => (string) $material->getMaterial(),
                    'offer' => (string) $material->getOffer(),
                    'variation' => (string) $material->getVariation(),
                    'modification' => (string) $material->getModification(),
                    'total' => $material->getTotal(),
                ]
            );

            /** Снимаем резерв и остаток на единицу сырья на складе грузоотправителя */
            for($i = 1; $i <= $material->getTotal(); $i++)
            {
                $SubMaterialStocksTotalMessage = new SubMaterialStocksTotalAndReserveMessage(
                    $UserProfileUid,
                    $material->getMaterial(),
                    $material->getOffer(),
                    $material->getVariation(),
                    $material->getModification()
                );

                $this->messageDispatch->dispatch(
                    $SubMaterialStocksTotalMessage,
                    transport: 'materials-stocks'
                );

                if($i === $material->getTotal())
                {
                    break;
                }
            }
        }

        $Deduplicator->save();
    }
}
