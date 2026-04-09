<?php
/*
 *  Copyright 2026.  Baks.dev <admin@baks.dev>
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
 *
 */

declare(strict_types=1);

namespace BaksDev\Materials\Stocks\Messenger\PurchaseMaterialStock;

use BaksDev\Materials\Stocks\Entity\Stock\MaterialStock;
use BaksDev\Materials\Stocks\UseCase\Admin\Purchase\Materials\MaterialStockDTO;
use BaksDev\Materials\Stocks\UseCase\Admin\Purchase\PurchaseMaterialStockDTO;
use BaksDev\Materials\Stocks\UseCase\Admin\Purchase\PurchaseMaterialStockHandler;
use BaksDev\Users\Profile\UserProfile\Repository\UserByUserProfile\UserByUserProfileInterface;
use BaksDev\Users\User\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Создает складскую заявку в статусе Purchase «Закупка»
 */
#[Autoconfigure(shared: false)]
#[AsMessageHandler(priority: 0)]
final readonly class PurchaseMaterialStockDispatcher
{
    public function __construct(
        #[Target('materialsStocksLogger')] private LoggerInterface $logger,
        private PurchaseMaterialStockHandler $purchaseMaterialStockHandler,
        private UserByUserProfileInterface $userByUserProfileRepository,
    ) {}

    public function __invoke(PurchaseMaterialStockMessage $message): void
    {
        /** Получаем идентификатор пользователя по профилю */
        $User = $this->userByUserProfileRepository
            ->forProfile($message->getProfile())
            ->find();

        if(false === ($User instanceof User))
        {
            $this->logger->critical(
                message: sprintf(
                    'materials-sign: Не найден профиль пользователя для создания закупочного листа при обработке Честного знака'),
                context: [
                    var_export($message, true),
                    self::class.':'.__LINE__
                ],
            );

            return;
        }

        if(true === ($User instanceof User))
        {
            /** Генерируем номер */
            $PurchaseNumber = number_format(
                microtime(true) * 100,
                0,
                '.',
                '.'
            );

            $PurchaseMaterialStockDTO = new PurchaseMaterialStockDTO();
            $PurchaseMaterialInvariableDTO = $PurchaseMaterialStockDTO->getInvariable();

            $PurchaseMaterialInvariableDTO
                ->setUsr($User->getId())
                ->setProfile($message->getProfile())
                ->setNumber($PurchaseNumber);

            $MaterialStockDTO = new MaterialStockDTO()
                ->setMaterial($message->getMaterial())
                ->setOffer($message->getOffer())
                ->setVariation($message->getVariation())
                ->setModification($message->getModification())
                ->setTotal($message->getTotal());

            $PurchaseMaterialStockDTO->addMaterial($MaterialStockDTO);

            $MaterialStock = $this->purchaseMaterialStockHandler->handle($PurchaseMaterialStockDTO);

            if(true === ($MaterialStock instanceof MaterialStock))
            {
                $this->logger->info(
                    message: sprintf(
                        '%s: Создана складская заявка в статусе Purchase «Закупка» при обработке Честного знака',
                        $PurchaseMaterialInvariableDTO->getNumber()
                    ),
                    context: [
                        self::class.':'.__LINE__,
                        var_export($message, true),
                    ],
                );

                return;
            }

            if(false === ($MaterialStock instanceof MaterialStock))
            {
                $this->logger->critical(
                    sprintf('materials-sign: Ошибка %s при создании закупочного листа', $MaterialStock),
                    [
                        self::class.':'.__LINE__,
                        var_export($message, true),
                    ],
                );
            }
        }
    }
}
