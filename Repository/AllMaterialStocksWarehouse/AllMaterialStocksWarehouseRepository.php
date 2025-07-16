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

namespace BaksDev\Materials\Stocks\Repository\AllMaterialStocksWarehouse;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Core\Form\Search\SearchDTO;
use BaksDev\Core\Services\Paginator\PaginatorInterface;
use BaksDev\Materials\Catalog\Entity\Category\MaterialCategory;
use BaksDev\Materials\Catalog\Entity\Event\MaterialEvent;
use BaksDev\Materials\Catalog\Entity\Info\MaterialInfo;
use BaksDev\Materials\Catalog\Entity\Material;
use BaksDev\Materials\Catalog\Entity\Offers\Image\MaterialOfferImage;
use BaksDev\Materials\Catalog\Entity\Offers\MaterialOffer;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Image\MaterialVariationImage;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\MaterialVariation;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\Image\MaterialModificationImage;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\MaterialModification;
use BaksDev\Materials\Catalog\Entity\Photo\MaterialPhoto;
use BaksDev\Materials\Catalog\Entity\Trans\MaterialTrans;
use BaksDev\Materials\Catalog\Forms\MaterialFilter\Admin\MaterialFilterDTO;
use BaksDev\Materials\Catalog\Forms\MaterialFilter\Admin\Property\MaterialFilterPropertyDTO;
use BaksDev\Materials\Category\Entity\CategoryMaterial;
use BaksDev\Materials\Category\Entity\Offers\CategoryMaterialOffers;
use BaksDev\Materials\Category\Entity\Offers\Variation\CategoryMaterialVariation;
use BaksDev\Materials\Category\Entity\Offers\Variation\Modification\CategoryMaterialModification;
use BaksDev\Materials\Category\Entity\Trans\CategoryMaterialTrans;
use BaksDev\Materials\Category\Type\Id\CategoryMaterialUid;
use BaksDev\Materials\Stocks\Entity\Stock\Event\MaterialStockEvent;
use BaksDev\Materials\Stocks\Entity\Stock\Invariable\MaterialStocksInvariable;
use BaksDev\Materials\Stocks\Entity\Stock\Materials\MaterialStockMaterial;
use BaksDev\Materials\Stocks\Entity\Stock\MaterialStock;
use BaksDev\Materials\Stocks\Entity\Stock\Modify\MaterialStockModify;
use BaksDev\Materials\Stocks\Entity\Stock\Move\MaterialStockMove;
use BaksDev\Materials\Stocks\Type\Status\MaterialStockStatus;
use BaksDev\Users\Profile\UserProfile\Entity\Event\Avatar\UserProfileAvatar;
use BaksDev\Users\Profile\UserProfile\Entity\Event\Info\UserProfileInfo;
use BaksDev\Users\Profile\UserProfile\Entity\Event\Personal\UserProfilePersonal;
use BaksDev\Users\Profile\UserProfile\Entity\Event\UserProfileEvent;
use BaksDev\Users\Profile\UserProfile\Entity\UserProfile;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;

final class AllMaterialStocksWarehouseRepository implements AllMaterialStocksWarehouseInterface
{
    private UserProfileUid|false $profile = false;

    private ?MaterialFilterDTO $filter = null;

    private ?SearchDTO $search = null;

    public function __construct(
        private readonly DBALQueryBuilder $DBALQueryBuilder,
        private readonly PaginatorInterface $paginator,
    ) {}

    public function search(SearchDTO $search): static
    {
        $this->search = $search;
        return $this;
    }

    public function filter(MaterialFilterDTO $filter): static
    {
        $this->filter = $filter;
        return $this;
    }

    public function profile(UserProfile|UserProfileUid|string $profile): self
    {
        if(is_string($profile))
        {
            $profile = new UserProfileUid($profile);
        }

        if($profile instanceof UserProfile)
        {
            $profile = $profile->getId();
        }

        $this->profile = $profile;

        return $this;
    }

    /**
     * Метод возвращает список всех поступлений на склад
     */
    public function findPaginator(): PaginatorInterface
    {

        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $dbal
            ->addSelect('invariable.number')
            ->from(MaterialStocksInvariable::class, 'invariable');

        if($this->profile instanceof UserProfileUid)
        {
            $dbal
                ->where('invariable.profile = :profile')
                ->setParameter('profile', $this->profile, UserProfileUid::TYPE);
        }

        $dbal
            ->addSelect('stock.id AS id')
            ->addSelect('stock.event AS event')
            ->join(
                'invariable',
                MaterialStock::class,
                'stock',
                'stock.id = invariable.main'
            );


        $dbal
            ->addSelect('event.comment')
            ->addSelect('event.status')
            ->leftJoin(
                'stock',
                MaterialStockEvent::class,
                'event',
                '
                event.id = stock.event AND
                event.status = :status
            '
            )
            ->setParameter(
                'status',
                MaterialStockStatus\Collection\MaterialStockStatusWarehouse::class,
                MaterialStockStatus::TYPE
            );


        $dbal
            ->leftJoin(
                'event',
                MaterialStockMove::class,
                'move',
                'move.event = event.id'
            );


        $dbal
            ->addSelect('modify.mod_date')
            ->join(
                'event',
                MaterialStockModify::class,
                'modify',
                'modify.event = stock.event'
            );


        $dbal
            ->addSelect('stock_material.id as material_stock_id')
            ->addSelect('stock_material.total')
            ->join(
                'event',
                MaterialStockMaterial::class,
                'stock_material',
                'stock_material.event = stock.event'
            );


        $dbal
            ->addSelect('material.id as material_id')
            ->addSelect('material.event as material_event')
            ->join(
                'stock_material',
                Material::class,
                'material',
                'material.id = stock_material.material'
            );

        // Material Event
        $dbal->join(
            'material',
            MaterialEvent::class,
            'material_event',
            'material_event.id = material.event'
        );

        $dbal
            ->leftJoin(
                'material_event',
                MaterialInfo::class,
                'material_info',
                'material_info.material = material.id'
            );

        // Material Trans
        $dbal
            ->addSelect('material_trans.name as material_name')
            ->join(
                'material_event',
                MaterialTrans::class,
                'material_trans',
                'material_trans.event = material_event.id AND material_trans.local = :local'
            );

        // Торговое предложение

        $dbal
            ->addSelect('material_offer.id as material_offer_uid')
            ->addSelect('material_offer.value as material_offer_value')
            ->leftJoin(
                'material_event',
                MaterialOffer::class,
                'material_offer',
                'material_offer.event = material_event.id AND material_offer.const = stock_material.offer'
            );

        if($this->filter?->getOffer())
        {
            $dbal->andWhere('material_offer.value = :offer');
            $dbal->setParameter('offer', $this->filter->getOffer());
        }


        // Получаем тип торгового предложения
        $dbal
            ->addSelect('category_offer.reference as material_offer_reference')
            ->leftJoin(
                'material_offer',
                CategoryMaterialOffers::class,
                'category_offer',
                'category_offer.id = material_offer.category_offer'
            );

        // Множественные варианты торгового предложения

        $dbal
            ->addSelect('material_variation.id as material_variation_uid')
            ->addSelect('material_variation.value as material_variation_value')
            ->leftJoin(
                'material_offer',
                MaterialVariation::class,
                'material_variation',
                'material_variation.offer = material_offer.id AND material_variation.const = stock_material.variation'
            );

        if($this->filter?->getVariation())
        {
            $dbal->andWhere('material_variation.value = :variation');
            $dbal->setParameter('variation', $this->filter->getVariation());
        }

        // Получаем тип множественного варианта
        $dbal
            ->addSelect('category_offer_variation.reference as material_variation_reference')
            ->leftJoin(
                'material_variation',
                CategoryMaterialVariation::class,
                'category_offer_variation',
                'category_offer_variation.id = material_variation.category_variation'
            );


        // Модификация множественного варианта торгового предложения

        $dbal
            ->addSelect('material_modification.id as material_modification_uid')
            ->addSelect('material_modification.value as material_modification_value')
            ->leftJoin(
                'material_variation',
                MaterialModification::class,
                'material_modification',
                'material_modification.variation = material_variation.id AND material_modification.const = stock_material.modification'
            );


        if($this->filter?->getModification())
        {
            $dbal->andWhere('material_modification.value = :modification');
            $dbal->setParameter('modification', $this->filter->getModification());
        }

        // Получаем тип модификации множественного варианта
        $dbal
            ->addSelect('category_offer_modification.reference as material_modification_reference')
            ->leftJoin(
                'material_modification',
                CategoryMaterialModification::class,
                'category_offer_modification',
                'category_offer_modification.id = material_modification.category_modification'
            );

        // Артикул продукта

        $dbal->addSelect('
            COALESCE(
                material_modification.article, 
                material_variation.article, 
                material_offer.article, 
                material_info.article
            ) AS material_article
		');

        // Фото продукта

        $dbal->leftJoin(
            'material_modification',
            MaterialModificationImage::class,
            'material_modification_image',
            '
			material_modification_image.modification = material_modification.id AND
			material_modification_image.root = true
			'
        );

        $dbal->leftJoin(
            'material_offer',
            MaterialVariationImage::class,
            'material_variation_image',
            '
			material_variation_image.variation = material_variation.id AND
			material_variation_image.root = true
			'
        );

        $dbal->leftJoin(
            'material_offer',
            MaterialOfferImage::class,
            'material_offer_images',
            '
			material_variation_image.name IS NULL AND
			material_offer_images.offer = material_offer.id AND
			material_offer_images.root = true
			'
        );

        $dbal->leftJoin(
            'material_offer',
            MaterialPhoto::class,
            'material_photo',
            '
			material_offer_images.name IS NULL AND
			material_photo.event = material_event.id AND
			material_photo.root = true
			'
        );

        $dbal->addSelect(
            "
			CASE
			 
			 WHEN material_modification_image.name IS NOT NULL THEN
					CONCAT ( '/upload/".$dbal->table(MaterialModificationImage::class)."' , '/', material_modification_image.name)
			   WHEN material_variation_image.name IS NOT NULL THEN
					CONCAT ( '/upload/".$dbal->table(MaterialVariationImage::class)."' , '/', material_variation_image.name)
			   WHEN material_offer_images.name IS NOT NULL THEN
					CONCAT ( '/upload/".$dbal->table(MaterialOfferImage::class)."' , '/', material_offer_images.name)
			   WHEN material_photo.name IS NOT NULL THEN
					CONCAT ( '/upload/".$dbal->table(MaterialPhoto::class)."' , '/', material_photo.name)
			   ELSE NULL
			END AS material_image
		"
        );

        // Расширение файла
        $dbal->addSelect(
            "
			CASE
			
			    WHEN material_modification_image.name IS NOT NULL THEN  material_modification_image.ext
			   WHEN material_variation_image.name IS NOT NULL THEN material_variation_image.ext
			   WHEN material_offer_images.name IS NOT NULL THEN material_offer_images.ext
			   WHEN material_photo.name IS NOT NULL THEN material_photo.ext
			   ELSE NULL
			   
			END AS material_image_ext
		"
        );

        // Флаг загрузки файла CDN
        $dbal->addSelect(
            '
			CASE
			   WHEN material_variation_image.name IS NOT NULL THEN
					material_variation_image.cdn
			   WHEN material_offer_images.name IS NOT NULL THEN
					material_offer_images.cdn
			   WHEN material_photo.name IS NOT NULL THEN
					material_photo.cdn
			   ELSE NULL
			END AS material_image_cdn
		'
        );

        // Категория
        $dbal->leftJoin(
            'material_event',
            MaterialCategory::class,
            'material_event_category',
            'material_event_category.event = material_event.id AND material_event_category.root = true'
        );

        $dbal->leftJoin(
            'material_event_category',
            CategoryMaterial::class,
            'category',
            'category.id = material_event_category.category'
        );

        if($this->filter?->getCategory())
        {
            $dbal->andWhere('material_event_category.category = :category');
            $dbal->setParameter('category', $this->filter->getCategory(), CategoryMaterialUid::TYPE);
        }

        $dbal
            ->addSelect('category_trans.name AS category_name')
            ->leftJoin(
                'category',
                CategoryMaterialTrans::class,
                'category_trans',
                'category_trans.event = category.event AND category_trans.local = :local'
            );


        // ОТВЕТСТВЕННЫЙ

        // UserProfile
        $dbal
            ->addSelect('users_profile.id as user_profile_id')
            ->addSelect('users_profile.event as user_profile_event')
            ->join(
                'event',
                UserProfile::class,
                'users_profile',
                'users_profile.id = invariable.profile'
            );

        // Info
        $dbal->join(
            'event',
            UserProfileInfo::class,
            'users_profile_info',
            'users_profile_info.profile = invariable.profile'
        );

        // Event
        $dbal->join(
            'users_profile',
            UserProfileEvent::class,
            'users_profile_event',
            'users_profile_event.id = users_profile.event'
        );

        // Personal
        $dbal
            ->addSelect('users_profile_personal.username AS user_profile_username')
            ->join(
                'users_profile_event',
                UserProfilePersonal::class,
                'users_profile_personal',
                'users_profile_personal.event = users_profile_event.id'
            );

        // Avatar

        $dbal
            ->addSelect("CONCAT ( '/upload/".$dbal->table(UserProfileAvatar::class)."' , '/', users_profile_avatar.name) AS user_profile_avatar")
            ->addSelect("CASE WHEN users_profile_avatar.cdn THEN  CONCAT ( 'small.', users_profile_avatar.ext) ELSE users_profile_avatar.ext END AS user_profile_avatar_ext")
            ->addSelect('users_profile_avatar.cdn AS user_profile_avatar_cdn')
            ->leftJoin(
                'users_profile_event',
                UserProfileAvatar::class,
                'users_profile_avatar',
                'users_profile_avatar.event = users_profile_event.id'
            );

        // Группа
        $dbal->addSelect('NULL AS group_name'); // Название группы


        // Поиск
        if($this->search?->getQuery())
        {
            $dbal
                ->createSearchQueryBuilder($this->search)
                ->addSearchLike('invariable.number')
                ->addSearchLike('material_trans.name');
        }

        $dbal->orderBy('modify.mod_date', 'DESC');

        return $this->paginator->fetchAllAssociative($dbal);

    }
}
