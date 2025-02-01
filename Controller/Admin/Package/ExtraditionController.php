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

namespace BaksDev\Materials\Stocks\Controller\Admin\Package;

use BaksDev\Centrifugo\Server\Publish\CentrifugoPublishInterface;
use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Materials\Stocks\Entity\Stock\Event\MaterialStockEvent;
use BaksDev\Materials\Stocks\Entity\Stock\MaterialStock;
use BaksDev\Materials\Stocks\Repository\MaterialsByMaterialStocks\MaterialByMaterialStocksInterface;
use BaksDev\Materials\Stocks\UseCase\Admin\Extradition\ExtraditionMaterialStockDTO;
use BaksDev\Materials\Stocks\UseCase\Admin\Extradition\ExtraditionMaterialStockForm;
use BaksDev\Materials\Stocks\UseCase\Admin\Extradition\ExtraditionMaterialStockHandler;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[RoleSecurity('ROLE_MATERIAL_STOCK_PACKAGE')]
final class ExtraditionController extends AbstractController
{
    /**
     * Укомплектовать складскую заявку
     */
    #[Route('/admin/material/stock/package/extradition/{id}', name: 'admin.package.extradition', methods: ['GET', 'POST'])]
    public function extradition(
        Request $request,
        #[MapEntity] MaterialStockEvent $MaterialStockEvent,
        ExtraditionMaterialStockHandler $ExtraditionMaterialStockHandler,
        MaterialByMaterialStocksInterface $materialDetail,
        CentrifugoPublishInterface $publish,
    ): Response
    {

        /** Скрываем идентификатор у остальных пользователей */
        $publish
            ->addData(['profile' => (string) $this->getCurrentProfileUid()])
            ->addData(['identifier' => (string) $MaterialStockEvent->getMain()])
            ->send('remove');

        $ExtraditionMaterialStockDTO = new ExtraditionMaterialStockDTO();
        $MaterialStockEvent->getDto($ExtraditionMaterialStockDTO);

        // Форма заявки
        $form = $this
            ->createForm(
                type: ExtraditionMaterialStockForm::class,
                data: $ExtraditionMaterialStockDTO,
                options: ['action' => $this->generateUrl('materials-stocks:admin.package.extradition', ['id' => $ExtraditionMaterialStockDTO->getEvent()])]
            )
            ->handleRequest($request);

        if($form->isSubmitted() && $form->isValid() && $form->has('extradition'))
        {
            $this->refreshTokenForm($form);

            $handle = $ExtraditionMaterialStockHandler->handle($ExtraditionMaterialStockDTO);

            /** Скрываем идентификатор у всех пользователей */
            $remove = $publish
                ->addData(['profile' => false]) // Скрывает у всех
                ->addData(['identifier' => (string) $handle->getId()])
                ->send('remove');

            $flash = $this->addFlash
            (
                'page.package',
                $handle instanceof MaterialStock ? 'success.extradition' : 'danger.extradition',
                'materials-stocks.admin',
                $handle,
                $remove ? 200 : 302
            );

            return $flash ?: $this->redirectToRoute('materials-stocks:admin.package.index');
        }

        $materials = $materialDetail
            ->stock($MaterialStockEvent->getMain())
            ->find();

        return $this->render([
            'form' => $form->createView(),
            'name' => $MaterialStockEvent->getNumber(),
            'materials' => $materials
        ]);
    }
}
