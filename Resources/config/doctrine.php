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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use BaksDev\Materials\Stocks\BaksDevMaterialsStocksBundle;
use BaksDev\Materials\Stocks\Type\Event\MaterialStockEventType;
use BaksDev\Materials\Stocks\Type\Event\MaterialStockEventUid;
use BaksDev\Materials\Stocks\Type\Id\MaterialStockType;
use BaksDev\Materials\Stocks\Type\Id\MaterialStockUid;
use BaksDev\Materials\Stocks\Type\Material\MaterialStockCollectionType;
use BaksDev\Materials\Stocks\Type\Material\MaterialStockCollectionUid;
use BaksDev\Materials\Stocks\Type\Parameters\MaterialStockParameterType;
use BaksDev\Materials\Stocks\Type\Parameters\MaterialStockParameterUid;
use BaksDev\Materials\Stocks\Type\Status\MaterialStockStatus;
use BaksDev\Materials\Stocks\Type\Status\MaterialStockStatusType;
use BaksDev\Materials\Stocks\Type\Total\MaterialStockTotalType;
use BaksDev\Materials\Stocks\Type\Total\MaterialStockTotalUid;
use Symfony\Config\DoctrineConfig;

return static function(ContainerConfigurator $container, DoctrineConfig $doctrine) {

    $doctrine->dbal()->type(MaterialStockUid::TYPE)->class(MaterialStockType::class);
    $doctrine->dbal()->type(MaterialStockEventUid::TYPE)->class(MaterialStockEventType::class);
    $doctrine->dbal()->type(MaterialStockCollectionUid::TYPE)->class(MaterialStockCollectionType::class);
    $doctrine->dbal()->type(MaterialStockStatus::TYPE)->class(MaterialStockStatusType::class);
    $doctrine->dbal()->type(MaterialStockTotalUid::TYPE)->class(MaterialStockTotalType::class);
    $doctrine->dbal()->type(MaterialStockParameterUid::TYPE)->class(MaterialStockParameterType::class);

    $emDefault = $doctrine->orm()->entityManager('default')->autoMapping(true);

    $emDefault->mapping('materials-stocks')
        ->type('attribute')
        ->dir(BaksDevMaterialsStocksBundle::PATH.'Entity')
        ->isBundle(false)
        ->prefix(BaksDevMaterialsStocksBundle::NAMESPACE.'\\Entity')
        ->alias('materials-stocks');
};
