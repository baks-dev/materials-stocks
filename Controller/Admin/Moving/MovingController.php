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

namespace BaksDev\Materials\Stocks\Controller\Admin\Moving;

use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Materials\Catalog\Repository\MaterialDetail\MaterialDetailByConstInterface;
use BaksDev\Materials\Stocks\Entity\Stock\MaterialStock;
use BaksDev\Materials\Stocks\Repository\MaterialWarehouseTotal\MaterialWarehouseTotalInterface;
use BaksDev\Materials\Stocks\UseCase\Admin\Moving\Materials\MaterialStockDTO;
use BaksDev\Materials\Stocks\UseCase\Admin\Moving\MovingMaterialStockDTO;
use BaksDev\Materials\Stocks\UseCase\Admin\Moving\MovingMaterialStockForm;
use BaksDev\Materials\Stocks\UseCase\Admin\Moving\MovingMaterialStockHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[RoleSecurity('ROLE_MATERIAL_STOCK_MOVING_NEW')]
final class MovingController extends AbstractController
{
    /** Заявка на перемещение товара на другой склад */
    #[Route('/admin/material/stock/moving/new', name: 'admin.moving.new', methods: ['GET', 'POST'])]
    public function moving(
        Request $request,
        MovingMaterialStockHandler $handler,
        MaterialWarehouseTotalInterface $materialWarehouseTotal,
        MaterialDetailByConstInterface $MaterialDetailByConst
    ): Response
    {
        $movingDTO = new MovingMaterialStockDTO($this->getUsr());

        // Форма заявки
        $form = $this
            ->createForm(
                type: MovingMaterialStockForm::class,
                data: $movingDTO,
                options: ['action' => $this->generateUrl('materials-stocks:admin.moving.new')]
            )
            ->handleRequest($request);


        if($form->isSubmitted() && $form->isValid() && $form->has('moving'))
        {
            $this->refreshTokenForm($form);

            $success = true;

            /** Создаем каждое отдельно перемещение */

            /** @var \BaksDev\Materials\Stocks\UseCase\Admin\Moving\MaterialStockDTO $move */
            foreach($movingDTO->getMove() as $move)
            {
                /** Проверяем, что на складе не изменилось доступное количество */

                /** @var MaterialStockDTO $material */
                foreach($move->getMaterial() as $material)
                {
                    $MaterialStockTotal = $materialWarehouseTotal->getMaterialProfileTotal(
                        $move->getMove()->getWarehouse(),
                        $material->getMaterial(),
                        $material->getOffer(),
                        $material->getVariation(),
                        $material->getModification()
                    );

                    if($material->getTotal() > $MaterialStockTotal)
                    {
                        $materialDetail = $MaterialDetailByConst
                            ->material($material->getMaterial())
                            ->offerConst($material->getOffer())
                            ->variationConst($material->getVariation())
                            ->modificationConst($material->getModification())
                            ->find();

                        $msg = '<b>'.$materialDetail['material_name'].'</b>';
                        $msg .= ' ('.$materialDetail['material_article'].')';

                        if($materialDetail['material_offer_value'])
                        {
                            $msg .= '<br>'.$materialDetail['material_offer_name'].': ';
                            $msg .= '<b>'.$materialDetail['material_offer_value'].'</b>';
                        }

                        if($materialDetail['material_variation_name'])
                        {
                            $msg .= ' '.$materialDetail['material_variation_name'].': ';
                            $msg .= '<b>'.$materialDetail['material_variation_value'].'</b>';
                        }

                        if($materialDetail['material_modification_value'])
                        {
                            $msg .= ' '.$materialDetail['material_modification_name'].': ';
                            $msg .= '<b>'.$materialDetail['material_modification_value'].'</b>';
                        }


                        $msg .= '<br>Доступно: <b>'.$MaterialStockTotal.'</b>';


                        $this->addFlash('Недостаточное количество сырья', $msg, 'materials-stocks.admin');
                        continue 2;
                    }
                }

                $move->setProfile($move->getMove()->getWarehouse());
                $move->setComment($movingDTO->getComment());

                $MaterialStock = $handler->handle($move);

                if(!$MaterialStock instanceof MaterialStock)
                {
                    $success = false;
                    $this->addFlash('danger', 'danger.move', 'materials-stocks.admin', $MaterialStock);
                }
            }

            if($success)
            {
                $this->addFlash('success', 'success.move', 'materials-stocks.admin');
            }

            return $this->redirectToRoute('materials-stocks:admin.moving.index');

        }

        return $this->render(['form' => $form->createView()]);
    }
}
