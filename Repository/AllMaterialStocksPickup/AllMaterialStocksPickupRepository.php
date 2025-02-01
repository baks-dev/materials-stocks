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

namespace BaksDev\Materials\Stocks\Repository\AllMaterialStocksPickup;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Core\Form\Search\SearchDTO;
use BaksDev\Core\Services\Paginator\PaginatorInterface;
use BaksDev\Delivery\Entity\Event\DeliveryEvent;
use BaksDev\Delivery\Entity\Trans\DeliveryTrans;
use BaksDev\Delivery\Type\Id\DeliveryUid;
use BaksDev\DeliveryTransport\BaksDevDeliveryTransportBundle;
use BaksDev\Materials\Stocks\Entity\Stock\Event\MaterialStockEvent;
use BaksDev\Materials\Stocks\Entity\Stock\Materials\MaterialStockMaterial;
use BaksDev\Materials\Stocks\Entity\Stock\MaterialStock;
use BaksDev\Materials\Stocks\Entity\Stock\Modify\MaterialStockModify;
use BaksDev\Materials\Stocks\Entity\Stock\Orders\MaterialStockOrder;
use BaksDev\Materials\Stocks\Forms\PickupFilter\MaterialStockPickupFilterInterface;
use BaksDev\Materials\Stocks\Type\Status\MaterialStockStatus;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Entity\User\Delivery\OrderDelivery;
use BaksDev\Orders\Order\Entity\User\OrderUser;
use BaksDev\Products\Category\Entity\CategoryProduct;
use BaksDev\Products\Category\Entity\Info\CategoryProductInfo;
use BaksDev\Products\Category\Entity\Offers\CategoryProductOffers;
use BaksDev\Products\Category\Entity\Offers\Variation\CategoryProductVariation;
use BaksDev\Products\Category\Entity\Offers\Variation\Modification\CategoryProductModification;
use BaksDev\Products\Category\Entity\Trans\CategoryProductTrans;
use BaksDev\Products\Product\Entity\Category\ProductCategory;
use BaksDev\Products\Product\Entity\Event\ProductEvent;
use BaksDev\Products\Product\Entity\Info\ProductInfo;
use BaksDev\Products\Product\Entity\Offers\Image\ProductOfferImage;
use BaksDev\Products\Product\Entity\Offers\ProductOffer;
use BaksDev\Products\Product\Entity\Offers\Variation\Image\ProductVariationImage;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\Image\ProductModificationImage;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Products\Product\Entity\Offers\Variation\ProductVariation;
use BaksDev\Products\Product\Entity\Photo\ProductPhoto;
use BaksDev\Products\Product\Entity\Product;
use BaksDev\Products\Product\Entity\Trans\ProductTrans;
use BaksDev\Users\Profile\UserProfile\Entity\Event\UserProfileEvent;
use BaksDev\Users\Profile\UserProfile\Entity\Info\UserProfileInfo;
use BaksDev\Users\Profile\UserProfile\Entity\Personal\UserProfilePersonal;
use BaksDev\Users\Profile\UserProfile\Entity\UserProfile;
use BaksDev\Users\Profile\UserProfile\Entity\Value\UserProfileValue;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;

final class AllMaterialStocksPickupRepository implements AllMaterialStocksPickupInterface
{
    private ?SearchDTO $search = null;

    private ?MaterialStockPickupFilterInterface $filter = null;

    public function __construct(
        private readonly DBALQueryBuilder $DBALQueryBuilder,
        private readonly PaginatorInterface $paginator,
    ) {}

    public function search(SearchDTO $search): self
    {
        $this->search = $search;
        return $this;
    }

    public function filter(MaterialStockPickupFilterInterface $filter): self
    {
        $this->filter = $filter;
        return $this;
    }

    public function findAll(UserProfileUid $profile): PaginatorInterface
    {
        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class)->bindLocal();

        // MaterialStock
        $dbal
            ->select('stock.id AS stock_id')
            ->addSelect('stock.event AS stock_event')
            ->from(MaterialStock::class, 'stock');

        // MaterialStockEvent
        // $dbal->addSelect('event.total');
        $dbal
            ->addSelect('event.comment')
            ->addSelect('event.status')
            ->addSelect('event.number')
            ->join(
                'stock',
                MaterialStockEvent::class,
                'event',
                'event.id = stock.event AND event.status = :status AND event.profile = :profile'
            )
            ->setParameter('profile', $profile, UserProfileUid::TYPE)
            ->setParameter('status', new MaterialStockStatus(new MaterialStockstatus\MaterialStockStatusExtradition()), MaterialStockStatus::TYPE);


        // MaterialStockModify
        $dbal->addSelect('modify.mod_date')
            ->join(
                'event',
                MaterialStockModify::class,
                'modify',
                'modify.event = stock.event'
            );

        $dbal
            ->addSelect('stock_material.id as material_stock_id')
            ->addSelect('stock_material.total')
            ->addSelect('stock_material.storage')
            ->join(
                'event',
                MaterialStockMaterial::class,
                'stock_material',
                'stock_material.event = stock.event'
            );


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

        // Получаем тип множественного варианта
        $dbal
            ->addSelect('category_offer_variation.reference as material_variation_reference')
            ->leftJoin(
                'material_variation',
                CategoryProductVariation::class,
                'category_offer_variation',
                'category_offer_variation.id = material_variation.category_variation'
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
                'material_modification.variation = material_variation.id AND material_modification.const = stock_material.modification'
            );


        // Получаем тип модификации множественного варианта
        $dbal
            ->addSelect('category_modification.reference as material_modification_reference')
            ->leftJoin(
                'material_modification',
                CategoryProductModification::class,
                'category_modification',
                'category_modification.id = material_modification.category_modification'
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
			 
               WHEN material_modification_image.name IS NOT NULL 
               THEN CONCAT ( '/upload/".$dbal->table(ProductModificationImage::class)."' , '/', material_modification_image.name)
					
			   WHEN material_variation_image.name IS NOT NULL 
			   THEN CONCAT ( '/upload/".$dbal->table(ProductVariationImage::class)."' , '/', material_variation_image.name)
					
			   WHEN material_offer_images.name IS NOT NULL 
			   THEN CONCAT ( '/upload/".$dbal->table(ProductOfferImage::class)."' , '/', material_offer_images.name)
					
			   WHEN material_photo.name IS NOT NULL 
			   THEN CONCAT ( '/upload/".$dbal->table(ProductPhoto::class)."' , '/', material_photo.name)
					
			   ELSE NULL
			END AS material_image
		"
        );

        // Расширение файла
        $dbal->addSelect(
            "
			CASE
			
			    WHEN material_modification_image.name IS NOT NULL 
			    THEN  material_modification_image.ext
			    
			   WHEN material_variation_image.name IS NOT NULL 
			   THEN material_variation_image.ext
			   
			   WHEN material_offer_images.name IS NOT NULL 
			   THEN material_offer_images.ext
			   
			   WHEN material_photo.name IS NOT NULL 
			   THEN material_photo.ext
				
			   ELSE NULL
			   
			END AS material_image_ext
		"
        );

        // Флаг загрузки файла CDN
        $dbal->addSelect(
            '
			CASE
			   WHEN material_variation_image.name IS NOT NULL 
			   THEN material_variation_image.cdn
					
			   WHEN material_offer_images.name IS NOT NULL 
			   THEN material_offer_images.cdn
					
			   WHEN material_photo.name IS NOT NULL 
			   THEN material_photo.cdn
					
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

        // ОТВЕТСТВЕННЫЙ

        // UserProfile
        $dbal
            ->addSelect('users_profile.event as users_profile_event')
            ->join(
                'event',
                UserProfile::class,
                'users_profile',
                'users_profile.id = event.profile'
            );

        // Info
        $dbal->join(
            'event',
            UserProfileInfo::class,
            'users_profile_info',
            'users_profile_info.profile = event.profile'
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
            ->addSelect('users_profile_personal.username AS users_profile_username')
            ->join(
                'users_profile_event',
                UserProfilePersonal::class,
                'users_profile_personal',
                'users_profile_personal.event = users_profile_event.id'
            );


        $dbal->join(
            'stock',
            MaterialStockOrder::class,
            'material_stock_order',
            'material_stock_order.event = stock.event'
        );


        $dbal
            ->addSelect('ord.id AS order_id')
            ->join(
                'material_stock_order',
                Order::class,
                'ord',
                'ord.id = material_stock_order.ord'
            );

        $dbal
            ->addSelect('order_user.profile AS client_profile_event')
            ->leftJoin(
                'ord',
                OrderUser::class,
                'order_user',
                'order_user.event = ord.event'
            );


        $dbal->addSelect('order_delivery.delivery_date');

        $delivery_condition = 'order_delivery.usr = order_user.id';

        if($this->filter !== null)
        {
            $dateFrom = $this->filter->getDate();
            $dateTo = $dateFrom?->modify('+1 day');

            if($dateFrom instanceof DateTimeImmutable && $dateTo instanceof DateTimeImmutable)
            {
                $delivery_condition .= ' AND order_delivery.delivery_date >= :delivery_date_start AND order_delivery.delivery_date < :delivery_date_end';
                $dbal->setParameter('delivery_date_start', $dateFrom, Types::DATE_IMMUTABLE);
                $dbal->setParameter('delivery_date_end', $dateTo, Types::DATE_IMMUTABLE);
            }

            if($this->filter->getDelivery())
            {
                $delivery_condition .= ' AND order_delivery.delivery = :delivery';
                $dbal->setParameter('delivery', $this->filter->getDelivery(), DeliveryUid::TYPE);
            }

            if($this->filter->getPhone())
            {
                $dbal->join(
                    'order_user',
                    UserProfileValue::class,
                    'client_profile_value',
                    " client_profile_value.event = order_user.profile AND client_profile_value.value LIKE '%' || :phone || '%'"
                );

                $phone = explode('(', $this->filter->getPhone());
                $dbal->setParameter('phone', end($phone));
            }

        }

        $dbal
            ->join(
                'order_user',
                OrderDelivery::class,
                'order_delivery',
                $delivery_condition
            );

        $dbal->leftJoin(
            'order_delivery',
            DeliveryEvent::class,
            'delivery_event',
            'delivery_event.id = order_delivery.event'
        );

        $dbal
            ->addSelect('delivery_trans.name AS delivery_name')
            ->leftJoin(
                'delivery_event',
                DeliveryTrans::class,
                'delivery_trans',
                'delivery_trans.event = delivery_event.id AND delivery_trans.local = :local'
            );


        if(class_exists(BaksDevDeliveryTransportBundle::class))
        {
            /** Проверяем, чтобы не было на доставке упаковки */
            //$dbal->andWhereNotExists(DeliveryPackageStocks::class, 'tmp', 'tmp.stock = stock.id');
        }

        // Поиск
        if($this->search->getQuery())
        {
            $dbal
                ->createSearchQueryBuilder($this->search)
                ->addSearchLike('event.number')
                ->addSearchLike('material_modification.article')
                ->addSearchLike('material_variation.article')
                ->addSearchLike('material_offer.article')
                ->addSearchLike('material_info.article');
        }

        $dbal->orderBy('modify.mod_date', 'DESC');

        return $this->paginator->fetchAllAssociative($dbal);
    }
}
