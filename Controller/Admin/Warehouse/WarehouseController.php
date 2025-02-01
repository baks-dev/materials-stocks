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

namespace BaksDev\Materials\Stocks\Controller\Admin\Warehouse;

use BaksDev\Centrifugo\Server\Publish\CentrifugoPublishInterface;
use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Materials\Stocks\Entity\Stock\Event\MaterialStockEvent;
use BaksDev\Materials\Stocks\Entity\Stock\MaterialStock;
use BaksDev\Materials\Stocks\UseCase\Admin\Warehouse\WarehouseMaterialStockDTO;
use BaksDev\Materials\Stocks\UseCase\Admin\Warehouse\WarehouseMaterialStockForm;
use BaksDev\Materials\Stocks\UseCase\Admin\Warehouse\WarehouseMaterialStockHandler;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

#[AsController]
#[RoleSecurity('ROLE_MATERIAL_STOCK_WAREHOUSE_SEND')]
final class WarehouseController extends AbstractController
{
    /**
     * Отправить закупку на указанный склад
     */
    #[Route('/admin/material/stock/warehouse/{id}', name: 'admin.warehouse.send', methods: ['GET', 'POST'])]
    public function incoming(
        #[MapEntity] MaterialStockEvent $MaterialStockEvent,
        Request $request,
        WarehouseMaterialStockHandler $handler,
        CentrifugoPublishInterface $publish,
    ): Response
    {

        if(!$this->getProfileUid())
        {
            throw new UserNotFoundException('User Profile not found');
        }

        /** Скрываем идентификатор у остальных пользователей */
        $publish
            ->addData(['profile' => (string) $this->getCurrentProfileUid()])
            ->addData(['identifier' => (string) $MaterialStockEvent->getMain()])
            ->send('remove');

        $WarehouseMaterialStockDTO = new WarehouseMaterialStockDTO($this->getUsr());

        $MaterialStockEvent->getDto($WarehouseMaterialStockDTO);

        /**
         * Если заявка на перемещение - присваиваем склад назначения
         * и профиль пользователя, отпарившего заявку
         */
        if($MaterialStockEvent->getMoveDestination())
        {
            $WarehouseMaterialStockDTO->setProfile($MaterialStockEvent->getMoveDestination());
            $WarehouseMaterialStockDTO->getMove()?->setDestination($this->getProfileUid());
        }

        // Форма добавления
        $form = $this
            ->createForm(
                type: WarehouseMaterialStockForm::class,
                data: $WarehouseMaterialStockDTO,
                options: ['action' => $this->generateUrl('materials-stocks:admin.warehouse.send', ['id' => $WarehouseMaterialStockDTO->getEvent()]),]
            )
            ->handleRequest($request);

        if($form->isSubmitted() && $form->isValid() && $form->has('send'))
        {
            $this->refreshTokenForm($form);

            $handle = $handler->handle($WarehouseMaterialStockDTO);

            /** Скрываем идентификатор у всех пользователей */
            $remove = $publish
                ->addData(['profile' => false]) // Скрывает у всех
                ->addData(['identifier' => (string) $handle->getId()])
                ->send('remove');

            $flash = $this->addFlash(
                'page.warehouse',
                $handle instanceof MaterialStock ? 'success.warehouse' : 'danger.warehouse',
                'materials-stocks.admin',
                $handle,
                $remove ? 200 : 302
            );

            return $flash ?: $this->redirectToRoute('materials-stocks:admin.purchase.index');

        }

        return $this->render(['form' => $form->createView()]);
    }
}
