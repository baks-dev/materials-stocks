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

namespace BaksDev\Materials\Stocks\Controller\Admin\Pickup;

use BaksDev\Centrifugo\Server\Publish\CentrifugoPublishInterface;
use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\DeliveryTransport\UseCase\Admin\Package\Completed\CompletedProductStockDTO;
use BaksDev\DeliveryTransport\UseCase\Admin\Package\Completed\CompletedProductStockForm;
use BaksDev\DeliveryTransport\UseCase\Admin\Package\Completed\CompletedProductStockHandler;
use BaksDev\DeliveryTransport\UseCase\Admin\Package\Delivery\DeliveryProductStockDTO;
use BaksDev\Materials\Stocks\Entity\Stock\MaterialStock;
use BaksDev\Materials\Stocks\Repository\MaterialsByMaterialStocks\MaterialByMaterialStocksInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[RoleSecurity('ROLE_MATERIAL_STOCK_PICKUP')]
final class CompletedController extends AbstractController
{
    /**
     * Выдать заказ клиенту.
     */
    #[Route('/admin/material/stocks/completed/{id}', name: 'admin.pickup.completed', methods: ['GET', 'POST'])]
    public function delivery(
        Request $request,
        #[MapEntity] MaterialStock $MaterialStock,
        CompletedProductStockHandler $CompletedMaterialStockHandler,
        MaterialByMaterialStocksInterface $materialDetail,
        CentrifugoPublishInterface $publish,
    ): Response
    {

        /** Скрываем идентификатор у остальных пользователей */
        $publish
            ->addData(['profile' => (string) $this->getCurrentProfileUid()])
            ->addData(['identifier' => (string) $MaterialStock->getId()])
            ->send('remove');

        /**
         * @var DeliveryProductStockDTO $DeliveryMaterialStockDTO
         */
        $CompletedMaterialStockDTO = new CompletedProductStockDTO(
            $MaterialStock->getEvent(),
            $this->getProfileUid()
        );

        // Форма
        $form = $this
            ->createForm(
                type: CompletedProductStockForm::class,
                data: $CompletedMaterialStockDTO,
                options: ['action' => $this->generateUrl('materials-stocks:admin.pickup.completed', ['id' => $MaterialStock->getId()])]
            )
            ->handleRequest($request);

        if($form->isSubmitted() && $form->isValid() && $form->has('completed_package'))
        {
            $this->refreshTokenForm($form);

            $handle = $CompletedMaterialStockHandler->handle($CompletedMaterialStockDTO);

            /** Скрываем идентификатор у всех пользователей */
            $remove = $publish
                ->addData(['profile' => false]) // Скрывает у всех
                ->addData(['identifier' => (string) $handle->getId()])
                ->send('remove');

            $flash = $this->addFlash
            (
                'page.pickup',
                $handle instanceof MaterialStock ? 'success.pickup' : 'danger.pickup',
                'materials-stocks.admin',
                $handle,
                $remove ? 200 : 302
            );

            return $flash ?: $this->redirectToRoute('materials-stocks:admin.pickup.index');

        }

        $materials = $materialDetail
            ->stock($MaterialStock)
            ->find();

        return $this->render(
            [
                'form' => $form->createView(),
                'materials' => $materials
            ]
        );
    }
}
