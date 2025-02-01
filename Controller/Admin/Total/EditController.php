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

namespace BaksDev\Materials\Stocks\Controller\Admin\Total;

use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Materials\Catalog\Repository\MaterialDetail\MaterialDetailByConstInterface;
use BaksDev\Materials\Stocks\Entity\Total\MaterialStockTotal;
use BaksDev\Materials\Stocks\UseCase\Admin\EditTotal\MaterialStockTotalEditDTO;
use BaksDev\Materials\Stocks\UseCase\Admin\EditTotal\MaterialStockTotalEditForm;
use BaksDev\Materials\Stocks\UseCase\Admin\EditTotal\MaterialStockTotalEditHandler;
use InvalidArgumentException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[RoleSecurity('ROLE_MATERIAL_STOCK_EDIT')]
final class EditController extends AbstractController
{
    #[Route('/admin/material/stocks/total/{id}', name: 'admin.total.edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        #[MapEntity] MaterialStockTotal $MaterialStocksTotal,
        MaterialStockTotalEditHandler $MaterialStocksHandler,
        MaterialDetailByConstInterface $MaterialDetailByConst
    ): Response
    {

        $MaterialStocksDTO = new MaterialStockTotalEditDTO();
        $MaterialStocksTotal->getDto($MaterialStocksDTO);

        if(!$MaterialStocksDTO->getProfile()->equals($this->getProfileUid()))
        {
            throw new InvalidArgumentException('Invalid profile');
        }

        // Форма
        $form = $this
            ->createForm(
                type: MaterialStockTotalEditForm::class,
                data: $MaterialStocksDTO,
                options: ['action' => $this->generateUrl('materials-stocks:admin.total.edit', ['id' => (string) $MaterialStocksTotal])]
            )
            ->handleRequest($request);

        if($form->isSubmitted() && $form->isValid() && $form->has('material_stock_total_edit'))
        {
            $this->refreshTokenForm($form);

            $handle = $MaterialStocksHandler->handle($MaterialStocksDTO);

            $this->addFlash(
                'page.total',
                $handle instanceof MaterialStockTotal ? 'success.total' : 'danger.total',
                'materials-stocks.admin',
                $handle
            );

            return $this->redirectToReferer();
        }

        $MaterialDetail = $MaterialDetailByConst
            ->material($MaterialStocksDTO->getMaterial())
            ->offerConst($MaterialStocksDTO->getOffer())
            ->variationConst($MaterialStocksDTO->getVariation())
            ->modificationConst($MaterialStocksDTO->getModification())
            ->find();

        return $this->render([
            'form' => $form->createView(),
            'card' => $MaterialDetail
        ]);
    }
}
