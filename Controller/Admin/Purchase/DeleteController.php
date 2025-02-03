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

namespace BaksDev\Materials\Stocks\Controller\Admin\Purchase;

use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Materials\Catalog\Repository\MaterialDetail\MaterialDetailByConstInterface;
use BaksDev\Materials\Stocks\Entity\Stock\Event\MaterialStockEvent;
use BaksDev\Materials\Stocks\Entity\Stock\Materials\MaterialStockMaterial;
use BaksDev\Materials\Stocks\Entity\Stock\MaterialStock;
use BaksDev\Materials\Stocks\UseCase\Admin\Delete\DeleteMaterialStocksDTO;
use BaksDev\Materials\Stocks\UseCase\Admin\Delete\DeleteMaterialStocksForm;
use BaksDev\Materials\Stocks\UseCase\Admin\Delete\DeleteMaterialStocksHandler;
use InvalidArgumentException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[RoleSecurity('ROLE_MATERIAL_STOCK_PURCHASE_DELETE')]
final class DeleteController extends AbstractController
{
    #[Route('/admin/material/stock/purchase/delete/{id}', name: 'admin.purchase.delete', methods: ['GET', 'POST'])]
    public function delete(
        Request $request,
        #[MapEntity] MaterialStockEvent $MaterialStocksEvent,
        DeleteMaterialStocksHandler $MaterialStocksDeleteHandler,
        MaterialDetailByConstInterface $MaterialDetailByConst
    ): Response
    {

        $MaterialStocksDeleteDTO = $MaterialStocksEvent
            ->getDto(DeleteMaterialStocksDTO::class);

        $form = $this
            ->createForm(
                type: DeleteMaterialStocksForm::class,
                data: $MaterialStocksDeleteDTO,
                options: ['action' => $this->generateUrl('materials-stocks:admin.purchase.delete', ['id' => $MaterialStocksDeleteDTO->getEvent()])]
            )
            ->handleRequest($request);


        if($form->isSubmitted() && $form->isValid() && $form->has('delete_material_stock'))
        {
            $this->refreshTokenForm($form);

            $handle = $MaterialStocksDeleteHandler->handle($MaterialStocksDeleteDTO);

            $this->addFlash(
                'page.purchase',
                $handle instanceof MaterialStock ? 'success.delete' : 'danger.delete',
                'materials-stocks.admin',
                $handle
            );

            return $this->redirectToRoute('materials-stocks:admin.purchase.index');
        }

        /**
         * Получаем информацию о продукте для отображения в форме.
         * Предполагается что в коллекции закупки должен быть один продукт.
         * @var MaterialStockMaterial $MaterialStockMaterial
         */

        if($MaterialStocksEvent->getMaterial()->isEmpty())
        {
            throw new InvalidArgumentException('Material not found');
        }

        $MaterialStockMaterial = $MaterialStocksEvent->getMaterial()->current();

        $material = $MaterialDetailByConst
            ->material($MaterialStockMaterial->getMaterial())
            ->offerConst($MaterialStockMaterial->getOffer())
            ->variationConst($MaterialStockMaterial->getVariation())
            ->modificationConst($MaterialStockMaterial->getModification())
            ->find();

        if($material === false)
        {
            throw new InvalidArgumentException('Material not found');
        }

        return $this->render(['form' => $form->createView(), 'material' => $material]);
    }
}
