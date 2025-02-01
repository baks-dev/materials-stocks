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

namespace BaksDev\Materials\Stocks\Controller\Admin\Total;

use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Form\Search\SearchDTO;
use BaksDev\Core\Form\Search\SearchForm;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Materials\Catalog\Forms\MaterialFilter\Admin\MaterialFilterDTO;
use BaksDev\Materials\Catalog\Forms\MaterialFilter\Admin\MaterialFilterForm;
use BaksDev\Materials\Stocks\Repository\AllMaterialStocks\AllMaterialStocksInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[RoleSecurity('ROLE_MATERIAL_STOCK_INDEX')]
final class IndexController extends AbstractController
{
    /**
     * Состояние склада ответственного лица
     */
    #[Route('/admin/material/stocks/{page<\d+>}', name: 'admin.total.index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        AllMaterialStocksInterface $allMaterialStocks,
        int $page = 0,
    ): Response
    {
        // Поиск
        $search = new SearchDTO();

        $searchForm = $this
            ->createForm(
                type: SearchForm::class,
                data: $search,
                options: ['action' => $this->generateUrl('materials-stocks:admin.total.index')]
            )
            ->handleRequest($request);

        /**
         * Фильтр продукции по ТП
         */
        $filter = new MaterialFilterDTO($request);
        $filter->allVisible();

        $filterForm = $this
            ->createForm(
                type: MaterialFilterForm::class,
                data: $filter,
                options: ['action' => $this->generateUrl('materials-stocks:admin.total.index'),]
            )
            ->handleRequest($request);
        !$filterForm->isSubmitted() ?: $this->redirectToReferer();

        /** Если ajax (печать) - показываем только свой склад */
        if($request->isXmlHttpRequest())
        {
            $filter->setAll(false);
            $allMaterialStocks->setLimit(1000);
        }

        $query = $allMaterialStocks
            ->search($search)
            ->filter($filter)
            ->findPaginator(
                $this->getUsr()?->getId(),
                $this->getProfileUid()
            );

        return $this->render(
            [
                'query' => $query,
                'search' => $searchForm->createView(),
                'filter' => $filterForm->createView(),
            ],
        );
    }
}
