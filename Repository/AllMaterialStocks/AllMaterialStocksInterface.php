<?php

namespace BaksDev\Materials\Stocks\Repository\AllMaterialStocks;

use BaksDev\Core\Form\Search\SearchDTO;
use BaksDev\Core\Services\Paginator\PaginatorInterface;
use BaksDev\Materials\Catalog\Forms\MaterialFilter\Admin\MaterialFilterDTO;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Users\User\Entity\User;
use BaksDev\Users\User\Type\Id\UserUid;

interface AllMaterialStocksInterface
{
    /** Метод возвращает полное состояние складских остатков продукции */
    public function findPaginator(
        User|UserUid $user,
        UserProfileUid $profile
    ): PaginatorInterface;

    public function search(SearchDTO $search): static;

    public function filter(MaterialFilterDTO $filter): static;
}
