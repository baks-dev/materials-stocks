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

namespace BaksDev\Materials\Stocks\Controller\Admin\Total;

use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Core\Twig\CallTwigFuncExtension;
use BaksDev\Materials\Catalog\Forms\MaterialFilter\Admin\MaterialFilterDTO;
use BaksDev\Materials\Catalog\Forms\MaterialFilter\Admin\MaterialFilterForm;
use BaksDev\Materials\Stocks\Repository\AllMaterialStocks\AllMaterialStocksInterface;
use BaksDev\Reference\Money\Type\Money;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

#[AsController]
#[RoleSecurity('ROLE_MATERIAL_STOCK_INDEX')]
final class CsvController extends AbstractController
{
    /**
     * Печать остатков всего склада
     */
    #[Route('/admin/material/stock/csv', name: 'admin.total.csv', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        AllMaterialStocksInterface $allMaterialStocks,
        Environment $environment
    ): Response
    {
        /**
         * Фильтр сырья по ТП
         */
        $filter = new MaterialFilterDTO();
        $filterForm = $this
            ->createForm(
                type: MaterialFilterForm::class,
                data: $filter
            )
            ->handleRequest($request);

        $filter->setAll(false);

        $query = $allMaterialStocks
            ->filter($filter)
            ->setLimit(100000)
            ->findPaginator(
                $this->getUsr()?->getId(),
                $this->getProfileUid()
            );

        if(empty($query->getData()))
        {
            return $this->redirectToReferer();
        }

        $result = $query->getData();

        $call = $environment->getExtension(CallTwigFuncExtension::class);

        $response = new StreamedResponse(function() use ($call, $result, $environment) {

            $handle = fopen('php://output', 'w+');

            // Запись заголовков
            fputcsv($handle, ['Артикул', 'Наименование', 'Стоимость', 'Наличие', 'Резерв', 'Доступно', 'Сумма', 'Место']);

            $allTotal = 0;
            $allPrice = 0;

            // Запись данных
            foreach($result as $data)
            {
                $name = $data['material_name'];

                $variation = $call->call($environment, $data['material_variation_value'], $data['material_variation_reference'].'_render');
                $name .= $variation ? ' '.$variation : null;

                $modification = $call->call($environment, $data['material_modification_value'], $data['material_modification_reference'].'_render');
                $name .= $modification ?: null;

                $offer = $call->call($environment, $data['material_offer_value'], $data['material_offer_reference'].'_render');
                $name .= $offer ? ' '.$offer : null;


                $Money = new Money($data['material_price'], true);
                $quantity = ($data['stock_total'] - $data['stock_reserve']);
                $total_price = ($Money->getValue() * $data['stock_total']);

                $allTotal += $data['stock_total'];
                $allPrice += $total_price;

                fputcsv($handle, [
                    $data['material_article'],
                    $name,
                    $Money->getValue(),
                    $data['stock_total'],
                    $data['stock_reserve'],
                    $quantity,
                    $total_price,
                    '. '.$data['stock_storage']
                ]);
            }

            /** Общее количество сырья и общая стоимость */
            fputcsv($handle, [
                '',
                '',
                '',
                $allTotal,
                '',
                '',
                $allPrice,
                ''
            ]);

            fclose($handle);
        });

        $filename = current($result)['users_profile_username'].'.csv';
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');

        return $response;

    }
}
