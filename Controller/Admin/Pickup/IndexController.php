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

use BaksDev\Centrifugo\Services\Token\TokenUserGenerator;
use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Form\Search\SearchDTO;
use BaksDev\Core\Form\Search\SearchForm;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Materials\Stocks\Forms\PickupFilter\Admin\MaterialStockPickupFilterDTO;
use BaksDev\Materials\Stocks\Forms\PickupFilter\Admin\MaterialStockPickupFilterForm;
use BaksDev\Materials\Stocks\Repository\AllMaterialStocksPickup\AllMaterialStocksPickupInterface;
use DateInterval;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[RoleSecurity('ROLE_MATERIAL_STOCK_PICKUP')]
final class IndexController extends AbstractController
{
    /**
     * Список всех заказов, готовых к выдаче
     */
    #[Route('/admin/material/stocks/pickup{page<\d+>}', name: 'admin.pickup.index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        AllMaterialStocksPickupInterface $allMaterialStocksPickup,
        TokenUserGenerator $tokenUserGenerator,
        int $page = 0,
    ): Response
    {
        // Поиск
        $search = new SearchDTO();

        $searchForm = $this
            ->createForm(
                type: SearchForm::class,
                data: $search,
                options: ['action' => $this->generateUrl('materials-stocks:admin.pickup.index')]
            )
            ->handleRequest($request);

        // Фильтр
        $filter = new MaterialStockPickupFilterDTO($request);
        $filterForm = $this
            ->createForm(
                type: MaterialStockPickupFilterForm::class,
                data: $filter,
                options: ['action' => $this->generateUrl('materials-stock:admin.pickup.index')]
            )
            ->handleRequest($request);

        if($filterForm->isSubmitted())
        {
            if($filterForm->get('back')->isClicked())
            {
                $filter->setDate($filter->getDate()?->sub(new DateInterval('P1D')));
                return $this->redirectToReferer();
            }

            if($filterForm->get('next')->isClicked())
            {
                $filter->setDate($filter->getDate()?->add(new DateInterval('P1D')));
                return $this->redirectToReferer();
            }
        }

        // Получаем список
        $Orders = $allMaterialStocksPickup
            ->search($search)
            ->filter($filter)
            ->findAll($this->getProfileUid());

        return $this->render(
            [
                'query' => $Orders,
                'search' => $searchForm->createView(),
                'filter' => $filterForm->createView(),
                'current_profile' => $this->getCurrentProfileUid(),
                'token' => $tokenUserGenerator->generate($this->getUsr()),
            ]
        );
    }
}
