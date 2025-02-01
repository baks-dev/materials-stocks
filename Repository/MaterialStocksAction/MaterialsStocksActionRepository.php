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

declare(strict_types=1);

namespace BaksDev\Materials\Stocks\Repository\MaterialsStocksAction;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Materials\Stocks\Entity\Stock\Event\MaterialStockEvent;
use BaksDev\Materials\Stocks\Entity\Stock\MaterialStock;
use BaksDev\Materials\Stocks\Entity\Stock\Modify\MaterialStockModify;
use BaksDev\Materials\Stocks\Type\Id\MaterialStockUid;
use BaksDev\Users\Profile\UserProfile\Entity\Info\UserProfileInfo;
use BaksDev\Users\Profile\UserProfile\Entity\Personal\UserProfilePersonal;
use BaksDev\Users\Profile\UserProfile\Entity\UserProfile;
use InvalidArgumentException;

final class MaterialsStocksActionRepository implements MaterialsStocksActionInterface
{
    private ?MaterialStockUid $main = null;

    public function __construct(private readonly DBALQueryBuilder $DBALQueryBuilder) {}

    public function main(MaterialStock|MaterialStockUid|string $main): self
    {
        if(is_string($main))
        {
            $main = new MaterialStockUid($main);
        }

        if($main instanceof MaterialStock)
        {
            $main = $main->getId();
        }

        $this->main = $main;

        return $this;
    }


    public function findAll(): array|bool
    {
        if(empty($this->main))
        {
            throw new InvalidArgumentException('Не указан идентификатор заявки MaterialStockUid с помощью метода ...->main($stock)');
        }

        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbal
            ->addSelect('event.id AS event_id')
            ->addSelect('event.number AS event_number')
            ->addSelect('event.status AS event_status')
            ->addSelect('event.comment AS event_comment')
            ->from(MaterialStockEvent::class, 'event')
            ->where('event.main = :main')
            ->setParameter('main', $this->main, MaterialStockUid::TYPE);

        $dbal
            ->addSelect('modify.mod_date AS date_modify')
            ->leftJoin(
                'event',
                MaterialStockModify::class,
                'modify',
                'modify.event = event.id'
            );

        $dbal
            ->leftJoin(
                'modify',
                UserProfileInfo::class,
                'info',
                'info.usr = modify.usr AND info.active = true'
            );


        $dbal
            ->leftJoin(
                'info',
                UserProfile::class,
                'profile',
                'profile.id = info.profile'
            );

        $dbal
            ->addSelect('personal.username AS profile_username')
            ->leftJoin(
                'info',
                UserProfilePersonal::class,
                'personal',
                'personal.event = profile.event'
            );


        $dbal->orderBy('event.id');

        return $dbal
            ->enableCache('materials-stocks', 3600)
            ->fetchAllAssociative();
    }
}
