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
use BaksDev\Materials\Stocks\Entity\Stock\Materials\MaterialStockMaterial;
use BaksDev\Materials\Stocks\Messenger\MaterialStockMessage;
use BaksDev\Materials\Stocks\Messenger\Stocks\AddMaterialStocksReserve\AddMaterialStocksReserveMessage;
use BaksDev\Materials\Stocks\Repository\CurrentMaterialStocks\CurrentMaterialStocksInterface;
use BaksDev\Materials\Stocks\Repository\MaterialStocksById\MaterialStocksByIdInterface;
use BaksDev\Materials\Stocks\Type\Status\MaterialStockStatus\Collection\MaterialStockStatusPackage;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(priority: 1)]
final readonly class AddReserveMaterialStocksTotalByPackage
{
    public function __construct(
        #[Target('materialsStocksLogger')] private LoggerInterface $logger,
        private MaterialStocksByIdInterface $materialStocks,
        private EntityManagerInterface $entityManager,
        private CurrentMaterialStocksInterface $currentMaterialStocks,
        private MessageDispatchInterface $messageDispatch,
        private DeduplicatorInterface $deduplicator,
    ) {}

    /**
     * Резервирование на складе сырья при статусе "ОТПАРВЛЕН НА СБОРКУ"
     */
    public function __invoke(MaterialStockMessage $message): void
    {

        $this->entityManager->clear();

        $MaterialStockEvent = $this->currentMaterialStocks->getCurrentEvent($message->getId());

        if(!$MaterialStockEvent)
        {
            return;
        }

        if(false === $MaterialStockEvent->equalsMaterialStockStatus(MaterialStockStatusPackage::class))
        {
            return;
        }


        // Получаем всю сырьё в ордере со статусом Package (УПАКОВКА)
        $materials = $this->materialStocks->getMaterialsPackageStocks($message->getId());

        if(empty($materials))
        {
            $this->logger->warning('Заявка не имеет сырья в коллекции', [self::class.':'.__LINE__]);
            return;
        }


        $Deduplicator = $this->deduplicator
            ->namespace('materials-stocks')
            ->deduplication([
                (string) $message->getId(),
                MaterialStockStatusPackage::STATUS,
                md5(self::class)
            ]);

        if($Deduplicator->isExecuted())
        {
            return;
        }

        /** Идентификатор профиля, куда была отправлена заявка на упаковку */
        $UserProfileUid = $MaterialStockEvent->getStocksProfile();

        /** @var MaterialStockMaterial $material */
        foreach($materials as $key => $material)
        {
            $this->logger->info(
                'Добавляем резерв сырья на складе при создании заявки на упаковку',
                ['total' => $material->getTotal()]
            );


            /**
             * Создаем резерв на единицу сырья при упаковке
             */
            for($i = 1; $i <= $material->getTotal(); $i++)
            {
                $AddMaterialStocksReserve = new AddMaterialStocksReserveMessage(
                    $UserProfileUid,
                    $material->getMaterial(),
                    $material->getOffer(),
                    $material->getVariation(),
                    $material->getModification()
                );

                $this->messageDispatch->dispatch(
                    $AddMaterialStocksReserve,
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
