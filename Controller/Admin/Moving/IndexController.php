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

namespace BaksDev\Materials\Stocks\Controller\Admin\Moving;

use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Form\Search\SearchDTO;
use BaksDev\Core\Form\Search\SearchForm;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Materials\Catalog\Forms\MaterialFilter\Admin\MaterialFilterDTO;
use BaksDev\Materials\Catalog\Forms\MaterialFilter\Admin\MaterialFilterForm;
use BaksDev\Materials\Stocks\Repository\AllMaterialStocksMove\AllMaterialStocksMoveInterface;
use chillerlan\QRCode\QRCode;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[RoleSecurity('ROLE_MATERIAL_STOCK_MOVING')]
final class IndexController extends AbstractController
{
    /** Перемещения сырья на другой склад */
    #[Route('/admin/material/stocks/movings/{page<\d+>}', name: 'admin.moving.index', methods: ['GET', 'POST'])]
    public function incoming(
        Request $request,
        AllMaterialStocksMoveInterface $allMove,
        int $page = 0
    ): Response
    {
        //$ROLE_ADMIN = $this->isGranted('ROLE_ADMIN');

        // Поиск
        $search = new SearchDTO();

        $searchForm = $this
            ->createForm(
                type: SearchForm::class,
                data: $search,
                options: ['action' => $this->generateUrl('materials-stocks:admin.moving.index')]
            )
            ->handleRequest($request);

        /**
         * Фильтр сырья по ТП
         */
        $filter = new MaterialFilterDTO($request);
        $filterForm = $this
            ->createForm(
                type: MaterialFilterForm::class,
                data: $filter,
                options: ['action' => $this->generateUrl('materials-stocks:admin.moving.index')]
            )
            ->handleRequest($request);

        // Получаем список закупок ответственного лица
        $query = $allMove
            ->search($search)
            ->filter($filter)
            ->fetchAllMaterialStocksAssociative($this->getProfileUid());

        return $this->render(
            [
                'query' => $query,
                'search' => $searchForm->createView(),
                'filter' => $filterForm->createView(),
            ]
        );
    }
}
