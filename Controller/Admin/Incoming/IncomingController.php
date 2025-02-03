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

namespace BaksDev\Materials\Stocks\Controller\Admin\Incoming;

use BaksDev\Centrifugo\Server\Publish\CentrifugoPublishInterface;
use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Materials\Stocks\Entity\Stock\Event\MaterialStockEvent;
use BaksDev\Materials\Stocks\Entity\Stock\MaterialStock;
use BaksDev\Materials\Stocks\Repository\MaterialStockMinQuantity\MaterialStockQuantityInterface;
use BaksDev\Materials\Stocks\UseCase\Admin\Incoming\IncomingMaterialStockDTO;
use BaksDev\Materials\Stocks\UseCase\Admin\Incoming\IncomingMaterialStockForm;
use BaksDev\Materials\Stocks\UseCase\Admin\Incoming\IncomingMaterialStockHandler;
use BaksDev\Materials\Stocks\UseCase\Admin\Incoming\Materials\MaterialStockDTO;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[RoleSecurity('ROLE_MATERIAL_STOCK_INCOMING_ACCEPT')]
final class IncomingController extends AbstractController
{
    /**
     * Добавить приход на склад
     */
    #[Route('/admin/material/stock/incoming/{id}', name: 'admin.incoming.accept', methods: ['GET', 'POST'])]
    public function incoming(
        #[MapEntity] MaterialStockEvent $MaterialStockEvent,
        Request $request,
        IncomingMaterialStockHandler $IncomingMaterialStockHandler,
        MaterialStockQuantityInterface $materialStockQuantity,
        CentrifugoPublishInterface $publish,
    ): Response
    {
        /** Скрываем идентификатор у остальных пользователей */
        $publish
            ->addData(['identifier' => (string) $MaterialStockEvent->getMain()])
            ->addData(['profile' => (string) $this->getCurrentProfileUid()])
            ->send('remove');

        $IncomingMaterialStockDTO = new IncomingMaterialStockDTO();
        $MaterialStockEvent->getDto($IncomingMaterialStockDTO);

        // Форма добавления
        $form = $this
            ->createForm(
                type: IncomingMaterialStockForm::class,
                data: $IncomingMaterialStockDTO,
                options: ['action' => $this->generateUrl('materials-stocks:admin.incoming.accept', ['id' => $IncomingMaterialStockDTO->getEvent()])]
            )
            ->handleRequest($request);

        if($form->isSubmitted() && $form->isValid() && $form->has('incoming'))
        {
            $this->refreshTokenForm($form);

            $handle = $IncomingMaterialStockHandler->handle($IncomingMaterialStockDTO);

            /** Скрываем идентификатор у всех пользователей */
            $remove = $publish
                ->addData(['identifier' => (string) $handle->getId()])
                ->addData(['profile' => false]) // Скрывает у всех
                ->send('remove');

            $flash = $this->addFlash(
                'page.orders',
                $handle instanceof MaterialStock ? 'success.accept' : 'danger.accept',
                'materials-stocks.admin',
                $handle,
                $remove ? 200 : 302
            );

            return $flash ?: $this->redirectToRoute('materials-stocks:admin.warehouse.index');
        }


        /** Рекомендуемое место складирования */

        /** @var MaterialStockDTO $MaterialStockDTO */

        $MaterialStockDTO = $IncomingMaterialStockDTO->getMaterial()->current();

        $materialStorage = $materialStockQuantity
            ->profile($this->getProfileUid())
            ->material($MaterialStockDTO->getMaterial())
            ->offerConst($MaterialStockDTO->getOffer())
            ->variationConst($MaterialStockDTO->getVariation())
            ->modificationConst($MaterialStockDTO->getModification())
            ->findOneByTotalMax();


        return $this->render([
            'form' => $form->createView(),
            'name' => $MaterialStockEvent->getNumber(),
            'order' => $MaterialStockEvent->getOrder() !== null,
            'recommender' => $materialStorage
        ]);
    }
}
