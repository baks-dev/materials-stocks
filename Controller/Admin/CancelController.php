<?php
/*
 *  Copyright 2024.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Materials\Stocks\Controller\Admin;

use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Materials\Stocks\Entity\Stock\Event\MaterialStockEvent;
use BaksDev\Materials\Stocks\Entity\Stock\MaterialStock;
use BaksDev\Materials\Stocks\UseCase\Admin\Cancel\CancelMaterialStockDTO;
use BaksDev\Materials\Stocks\UseCase\Admin\Cancel\CancelMaterialStockForm;
use BaksDev\Materials\Stocks\UseCase\Admin\Cancel\CancelMaterialStockHandler;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[RoleSecurity('ROLE_MATERIAL_STOCK_CANCEL')]
final class CancelController extends AbstractController
{
    /** Отмена складской заявки (БЕЗ ОТМЕНЫ ЗАКАЗА) */
    #[Route('/admin/material/stock/cancel/{id}', name: 'admin.cancel', methods: ['GET', 'POST'])]
    public function cancel(
        #[MapEntity] MaterialStockEvent $Event,
        Request $request,
        CancelMaterialStockHandler $MovingMaterialStockCancelHandler,
    ): Response
    {

        $CancelMaterialStockDTO = new CancelMaterialStockDTO();
        $Event->getDto($CancelMaterialStockDTO);

        // Форма заявки
        $form = $this
            ->createForm(
                type: CancelMaterialStockForm::class,
                data: $CancelMaterialStockDTO,
                options: ['action' => $this->generateUrl('materials-stocks:admin.cancel', ['id' => (string) $Event])]
            )
            ->handleRequest($request);


        if($form->isSubmitted() && $form->isValid() && $form->has('material_stock_cancel'))
        {
            $this->refreshTokenForm($form);

            $handle = $MovingMaterialStockCancelHandler->handle($CancelMaterialStockDTO);

            $this->addFlash(
                'page.cancel',
                $handle instanceof MaterialStock ? 'success.cancel' : 'danger.cancel',
                'materials-stocks.admin',
                $handle
            );

            return $this->redirectToReferer();
        }

        return $this->render([
            'form' => $form->createView(),
            'number' => $Event->getNumber()
        ]);
    }
}
