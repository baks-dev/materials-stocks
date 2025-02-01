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
use BaksDev\Materials\Stocks\Entity\Stock\Event\MaterialStockEvent;
use BaksDev\Materials\Stocks\Entity\Stock\Materials\MaterialStockMaterial;
use BaksDev\Materials\Stocks\Entity\Total\MaterialStockTotal;
use BaksDev\Materials\Stocks\Messenger\MaterialStockMessage;
use BaksDev\Materials\Stocks\Repository\MaterialStocksById\MaterialStocksByIdInterface;
use BaksDev\Materials\Stocks\Repository\MaterialStocksTotalStorage\MaterialStocksTotalStorageInterface;
use BaksDev\Materials\Stocks\Repository\UpdateMaterialStock\AddMaterialStockInterface;
use BaksDev\Materials\Stocks\Type\Status\MaterialStockstatus\Collection\MaterialStockStatusIncoming;
use BaksDev\Users\Profile\UserProfile\Repository\UserByUserProfile\UserByUserProfileInterface;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(priority: 1)]
final readonly class AddQuantityMaterialStocksTotalByIncomingStock
{
    public function __construct(
        #[Target('materialsStocksLogger')] private LoggerInterface $logger,
        private MaterialStocksByIdInterface $materialStocks,
        private EntityManagerInterface $entityManager,
        private UserByUserProfileInterface $userByUserProfile,
        private MaterialStocksTotalStorageInterface $materialStocksTotalStorage,
        private AddMaterialStockInterface $addMaterialStock,
        private DeduplicatorInterface $deduplicator,
    ) {}

    /**
     * Пополнение складских остатков при поступлении на склад
     */
    public function __invoke(MaterialStockMessage $message): void
    {

        /** Получаем статус заявки */
        $MaterialStockEvent = $this->entityManager
            ->getRepository(MaterialStockEvent::class)
            ->find($message->getEvent());

        $this->entityManager->clear();

        if(!$MaterialStockEvent)
        {
            return;
        }

        /**
         * Если Статус заявки не является Incoming «Приход на склад»
         */
        if(false === $MaterialStockEvent->getStatus()->equals(MaterialStockStatusIncoming::class))
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


        /** Идентификатор профиля склада при поступлении */
        $UserProfileUid = $MaterialStockEvent->getProfile();

        /** @var MaterialStockMaterial $material */
        foreach($materials as $material)
        {

            /** Получаем место для хранения указанной продукции данного профиля */
            $MaterialStockTotal = $this->materialStocksTotalStorage
                ->profile($UserProfileUid)
                ->material($material->getMaterial())
                ->offer($material->getOffer())
                ->variation($material->getVariation())
                ->modification($material->getModification())
                ->storage($material->getStorage())
                ->find();

            if(!$MaterialStockTotal)
            {
                /* получаем пользователя профиля, для присвоения новому месту складирования */
                $User = $this->userByUserProfile
                    ->forProfile($UserProfileUid)
                    ->findUser();

                if(!$User)
                {
                    $this->logger->error(
                        'Ошибка при обновлении складских остатков. Не удалось получить пользователя по профилю.',
                        [
                            self::class.':'.__LINE__,
                            'profile' => (string) $UserProfileUid,
                        ]
                    );

                    throw new InvalidArgumentException('Ошибка при обновлении складских остатков.');
                }

                /* Создаем новое место складирования на указанный профиль и пользовтаеля  */
                $MaterialStockTotal = new MaterialStockTotal(
                    $User->getId(),
                    $UserProfileUid,
                    $material->getMaterial(),
                    $material->getOffer(),
                    $material->getVariation(),
                    $material->getModification(),
                    $material->getStorage()
                );

                $this->entityManager->persist($MaterialStockTotal);
                $this->entityManager->flush();

                $this->logger->info(
                    'Место складирования не найдено! Создали новое место для указанной продукции',
                    [
                        self::class.':'.__LINE__,
                        'storage' => $material->getStorage(),
                        'profile' => (string) $UserProfileUid,
                        'material' => (string) $material->getMaterial(),
                        'offer' => (string) $material->getOffer(),
                        'variation' => (string) $material->getVariation(),
                        'modification' => (string) $material->getModification(),
                    ]
                );
            }

            $this->logger->info(
                sprintf('Добавляем приход продукции по заявке %s', $MaterialStockEvent->getNumber()),
                [self::class.':'.__LINE__]
            );

            $this->handle($MaterialStockTotal, $material->getTotal());


        }

        $Deduplicator->save();

    }

    public function handle(MaterialStockTotal $MaterialStockTotal, int $total): void
    {

        /** Добавляем приход на указанный профиль (склад) */
        $rows = $this->addMaterialStock
            ->total($total)
            ->reserve(null)
            ->updateById($MaterialStockTotal);

        if(empty($rows))
        {
            $this->logger->critical(
                'Ошибка при обновлении складских остатков',
                [
                    self::class.':'.__LINE__,
                    'MaterialStockTotalUid' => (string) $MaterialStockTotal->getId()
                ]
            );

            return;
        }

        $this->logger->info(
            'Добавили приход продукции на склад',
            [
                self::class.':'.__LINE__,
                'MaterialStockTotalUid' => (string) $MaterialStockTotal->getId()
            ]
        );
    }
}
