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

namespace BaksDev\Materials\Stocks\Controller\Admin\Package;

use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Form\Search\SearchDTO;
use BaksDev\Core\Form\Search\SearchForm;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Materials\Stocks\Forms\PackageFilter\Admin\MaterialStockPackageFilterDTO;
use BaksDev\Materials\Stocks\Repository\AllMaterialStocksPackage\AllMaterialStocksPackageInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[RoleSecurity('ROLE_MATERIAL_STOCK_PACKAGE')]
final class SelectionController extends AbstractController
{
    /**
     * Заказы на сборке
     */
    #[Route('/admin/material/stocks/selection', name: 'admin.package.selection', methods: ['GET'])]
    public function incoming(
        Request $request,
        AllMaterialStocksPackageInterface $allPackage,
        int $page = 0
    ): Response
    {

        // Поиск
        $search = new SearchDTO();
        //        $searchForm = $this
        //    ->createForm(SearchForm::class, $search,
        //            ['action' => $this->generateUrl('materials-stocks:admin.package.index')]
        //        );
        //$searchForm->handleRequest($request);
        //$searchForm->createView();


        // Фильтр
        $filter = new MaterialStockPackageFilterDTO($request);
        //$filterForm = $this
        //    ->createForm(MaterialStockPackageFilterForm::class, $filter);
        //$filterForm->handleRequest($request);
        //$filterForm->createView();


        // Получаем список заявок на упаковку
        $query = $allPackage
            ->setLimit(1000)
            ->search($search)
            ->filter($filter)
            ->findAll($this->getProfileUid());


        return $this->render(
            [
                'query' => $query,
                //'search' => $searchForm->createView(),
                //'filter' => $filterForm->createView(),
            ], file: 'content.html.twig'
        );
    }
}
