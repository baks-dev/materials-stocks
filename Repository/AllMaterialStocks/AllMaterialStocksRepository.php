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

namespace BaksDev\Materials\Stocks\Repository\AllMaterialStocks;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Core\Form\Search\SearchDTO;
use BaksDev\Core\Services\Paginator\PaginatorInterface;
use BaksDev\Materials\Catalog\Forms\MaterialFilter\Admin\MaterialFilterDTO;
use BaksDev\Materials\Catalog\Forms\MaterialFilter\Admin\Property\MaterialFilterPropertyDTO;
use BaksDev\Materials\Stocks\Entity\Total\MaterialStockTotal;
use BaksDev\Products\Category\Entity\CategoryProduct;
use BaksDev\Products\Category\Entity\Info\CategoryProductInfo;
use BaksDev\Products\Category\Entity\Offers\CategoryProductOffers;
use BaksDev\Products\Category\Entity\Offers\Variation\CategoryProductVariation;
use BaksDev\Products\Category\Entity\Offers\Variation\Modification\CategoryProductModification;
use BaksDev\Products\Category\Entity\Trans\CategoryProductTrans;
use BaksDev\Products\Category\Type\Id\CategoryMaterialUid;
use BaksDev\Products\Product\Entity\Category\ProductCategory;
use BaksDev\Products\Product\Entity\Event\ProductEvent;
use BaksDev\Products\Product\Entity\Info\ProductInfo;
use BaksDev\Products\Product\Entity\Offers\Image\ProductOfferImage;
use BaksDev\Products\Product\Entity\Offers\Price\ProductOfferPrice;
use BaksDev\Products\Product\Entity\Offers\ProductOffer;
use BaksDev\Products\Product\Entity\Offers\Variation\Image\ProductVariationImage;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\Image\ProductModificationImage;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\Price\ProductModificationPrice;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Products\Product\Entity\Offers\Variation\Price\ProductVariationPrice;
use BaksDev\Products\Product\Entity\Offers\Variation\ProductVariation;
use BaksDev\Products\Product\Entity\Photo\ProductPhoto;
use BaksDev\Products\Product\Entity\Price\ProductPrice;
use BaksDev\Products\Product\Entity\Product;
use BaksDev\Products\Product\Entity\Property\ProductProperty;
use BaksDev\Products\Product\Entity\Trans\ProductTrans;
use BaksDev\Users\Profile\UserProfile\Entity\Personal\UserProfilePersonal;
use BaksDev\Users\Profile\UserProfile\Entity\UserProfile;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Users\User\Entity\User;
use BaksDev\Users\User\Type\Id\UserUid;

final class AllMaterialStocksRepository implements AllMaterialStocksInterface
{
    private ?int $limit = null;

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

    public function setLimit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }


    /**
     * Метод возвращает полное состояние складских остатков продукции
     */
    public function findPaginator(
        User|UserUid $user,
        UserProfileUid $profile,
    ): PaginatorInterface
    {

        $user = $user instanceof User ? $user->getId() : $user;

        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $dbal
            ->select('stock_material.id AS stock_id')
            ->addSelect('stock_material.total AS stock_total')
            ->addSelect('stock_material.storage AS stock_storage')
            ->addSelect('stock_material.reserve AS stock_reserve')
            ->addSelect('stock_material.comment AS stock_comment')
            ->addSelect('stock_material.profile AS users_profile_id')
            ->from(MaterialStockTotal::class, 'stock_material')
            ->andWhere('stock_material.total != 0')
            ->andWhere('stock_material.reserve >= 0');

        if($this->filter->getAll())
        {
            $dbal->andWhere('stock_material.usr = :usr')
                ->setParameter('usr', $user, UserUid::TYPE);
        }
        else
        {
            $dbal->andWhere('stock_material.profile = :profile')
                ->setParameter('profile', $profile, UserProfileUid::TYPE);
        }

        // Product
        $dbal
            ->addSelect('material.id as material_id')
            ->addSelect('material.event as material_event')
            ->join(
                'stock_material',
                Product::class,
                'material',
                'material.id = stock_material.material'
            );

        // Material Event
        $dbal->join(
            'material',
            ProductEvent::class,
            'material_event',
            'material_event.id = material.event'
        );

        $dbal
            ->addSelect('material_info.url AS material_url')
            ->leftJoin(
                'material_event',
                ProductInfo::class,
                'material_info',
                'material_info.material = material.id'
            );

        // Material Trans
        $dbal
            ->addSelect('material_trans.name as material_name')
            ->join(
                'material_event',
                ProductTrans::class,
                'material_trans',
                'material_trans.event = material_event.id AND material_trans.local = :local'
            );

        // Торговое предложение

        $dbal
            ->addSelect('material_offer.id as material_offer_uid')
            ->addSelect('material_offer.value as material_offer_value')
            ->addSelect('material_offer.postfix as material_offer_postfix')
            ->leftJoin(
                'material_event',
                ProductOffer::class,
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
                CategoryProductOffers::class,
                'category_offer',
                'category_offer.id = material_offer.category_offer'
            );

        // Множественные варианты торгового предложения

        $dbal
            ->addSelect('material_variation.id as material_variation_uid')
            ->addSelect('material_variation.value as material_variation_value')
            ->addSelect('material_variation.postfix as material_variation_postfix')
            ->leftJoin(
                'material_offer',
                ProductVariation::class,
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
            ->addSelect('category_variation.reference as material_variation_reference')
            ->leftJoin(
                'material_variation',
                CategoryProductVariation::class,
                'category_variation',
                'category_variation.id = material_variation.category_variation'
            );

        // Модификация множественного варианта торгового предложения

        $dbal
            ->addSelect('material_modification.id as material_modification_uid')
            ->addSelect('material_modification.value as material_modification_value')
            ->addSelect('material_modification.postfix as material_modification_postfix')
            ->leftJoin(
                'material_variation',
                ProductModification::class,
                'material_modification',
                'material_modification.variation = material_variation.id  AND material_modification.const = stock_material.modification'
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
                CategoryProductModification::class,
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
            ProductModificationImage::class,
            'material_modification_image',
            '
			material_modification_image.modification = material_modification.id AND
			material_modification_image.root = true
			'
        );

        $dbal->leftJoin(
            'material_offer',
            ProductVariationImage::class,
            'material_variation_image',
            '
			material_variation_image.variation = material_variation.id AND
			material_variation_image.root = true
			'
        );

        $dbal->leftJoin(
            'material_offer',
            ProductOfferImage::class,
            'material_offer_images',
            '
			material_variation_image.name IS NULL AND
			material_offer_images.offer = material_offer.id AND
			material_offer_images.root = true
			'
        );

        $dbal->leftJoin(
            'material_offer',
            ProductPhoto::class,
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
					CONCAT ( '/upload/".$dbal->table(ProductModificationImage::class)."' , '/', material_modification_image.name)
			   WHEN material_variation_image.name IS NOT NULL THEN
					CONCAT ( '/upload/".$dbal->table(ProductVariationImage::class)."' , '/', material_variation_image.name)
			   WHEN material_offer_images.name IS NOT NULL THEN
					CONCAT ( '/upload/".$dbal->table(ProductOfferImage::class)."' , '/', material_offer_images.name)
			   WHEN material_photo.name IS NOT NULL THEN
					CONCAT ( '/upload/".$dbal->table(ProductPhoto::class)."' , '/', material_photo.name)
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
            ProductCategory::class,
            'material_event_category',
            'material_event_category.event = material_event.id AND material_event_category.root = true'
        );

        if($this->filter?->getCategory())
        {
            $dbal->andWhere('material_event_category.category = :category');
            $dbal->setParameter('category', $this->filter->getCategory(), CategoryMaterialUid::TYPE);
        }

        $dbal->leftJoin(
            'material_event_category',
            CategoryProduct::class,
            'category',
            'category.id = material_event_category.category'
        );

        $dbal
            ->addSelect('category_trans.name AS category_name')
            ->leftJoin(
                'category',
                CategoryProductTrans::class,
                'category_trans',
                'category_trans.event = category.event AND category_trans.local = :local'
            );

        $dbal
            ->addSelect('category_info.url AS category_url')
            ->leftJoin(
                'category',
                CategoryProductInfo::class,
                'category_info',
                'category_info.event = category.event'
            );

        /** Ответственное лицо (Склад) */

        $dbal
            ->join(
                'stock_material',
                UserProfile::class,
                'users_profile',
                'users_profile.id = stock_material.profile'
            );

        $dbal
            ->addSelect('users_profile_personal.username AS users_profile_username')
            ->addSelect('users_profile_personal.location AS users_profile_location')
            ->join(
                'users_profile',
                UserProfilePersonal::class,
                'users_profile_personal',
                'users_profile_personal.event = users_profile.event'
            );


        /** Стоимость продукции */

        /* Базовая Цена товара */
        $dbal->leftJoin(
            'material',
            ProductPrice::class,
            'material_price',
            'material_price.event = material.event'
        );

        /* Цена торгового предположения */
        $dbal
            ->leftJoin(
                'material_offer',
                ProductOfferPrice::class,
                'material_offer_price',
                'material_offer_price.offer = material_offer.id'
            );

        /* Цена множественного варианта */
        $dbal
            ->leftJoin(
                'material_variation',
                ProductVariationPrice::class,
                'material_variation_price',
                'material_variation_price.variation = material_variation.id'
            );

        /* Цена модификации множественного варианта */
        $dbal->leftJoin(
            'material_modification',
            ProductModificationPrice::class,
            'material_modification_price',
            'material_modification_price.modification = material_modification.id'
        );

        $dbal->addSelect('
            COALESCE(
                material_modification_price.price,
                material_variation_price.price,
                material_offer_price.price,
                material_price.price
            ) AS material_price
        ');


        /**
         * Фильтр по свойства продукта
         */
        if($this->filter->getProperty())
        {
            /** @var MaterialFilterPropertyDTO $property */
            foreach($this->filter->getProperty() as $property)
            {
                if($property->getValue())
                {
                    $dbal->join(
                        'material',
                        ProductProperty::class,
                        'material_property_'.$property->getType(),
                        'material_property_'.$property->getType().'.event = material.event AND 
                        material_property_'.$property->getType().'.field = :'.$property->getType().'_const AND 
                        material_property_'.$property->getType().'.value = :'.$property->getType().'_value'
                    );

                    $dbal->setParameter($property->getType().'_const', $property->getConst());
                    $dbal->setParameter($property->getType().'_value', $property->getValue());
                }
            }
        }


        // Поиск
        if($this->search?->getQuery())
        {
            //            for ($i = 0; $i <= 2; $i++) {
            //
            //                /** Поиск по модификации */
            //                $result = $this->elasticGetIndex ? $this->elasticGetIndex->handle(ProductModification::class, $this->search->getQueryFilter(), $i) : false;
            //
            //                if($result)
            //                {
            //                    $counter = $result['hits']['total']['value'];
            //
            //                    if($counter)
            //                    {
            //
            //                        /** Идентификаторы */
            //                        $data = array_column($result['hits']['hits'], "_source");
            //
            //                        $dbal
            //                            ->createSearchQueryBuilder($this->search)
            //                            ->addSearchInArray('material_modification.id', array_column($data, "id"));
            //
            //                        return $this->paginator->fetchAllAssociative($dbal);
            //                    }
            //
            //                    /** Поиск по продукции */
            //                    $result = $this->elasticGetIndex->handle(Product::class, $this->search->getQueryFilter(), $i);
            //
            //                    $counter = $result['hits']['total']['value'];
            //
            //                    if($counter)
            //                    {
            //                        /** Идентификаторы */
            //                        $data = array_column($result['hits']['hits'], "_source");
            //
            //                        $dbal
            //                            ->createSearchQueryBuilder($this->search)
            //                            ->addSearchInArray('material.id', array_column($data, "id"));
            //
            //                        return $this->paginator->fetchAllAssociative($dbal);
            //                    }
            //                }
            //            }


            $dbal
                ->createSearchQueryBuilder($this->search)
                ->addSearchEqualUid('stock_material.id')

                //->addSearchEqualUid('warehouse.id')
                //->addSearchEqualUid('warehouse.event')
                //->addSearchLike('warehouse_trans.name')
                ->addSearchLike('users_profile_personal.username')
                ->addSearchLike('users_profile_personal.location')
                ->addSearchLike('material_trans.name')
                ->addSearchLike('category_trans.name')
                ->addSearchLike('material_modification.article')
                ->addSearchLike('material_variation.article')
                ->addSearchLike('material_offer.article')
                ->addSearchLike('material_info.article');

        }

        $dbal->addOrderBy('material.id');
        $dbal->addOrderBy('stock_material.profile');

        $dbal->addOrderBy('material_offer.value');
        $dbal->addOrderBy('material_variation.value');
        $dbal->addOrderBy('material_modification.value');
        $dbal->addOrderBy('stock_material.total');

        if($this->limit)
        {
            $this->paginator->setLimit($this->limit);
        }

        return $this
            ->paginator
            ->fetchAllAssociative($dbal);

    }
}
