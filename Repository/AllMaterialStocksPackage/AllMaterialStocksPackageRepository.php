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

namespace BaksDev\Materials\Stocks\Repository\AllMaterialStocksPackage;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Core\Form\Search\SearchDTO;
use BaksDev\Core\Services\Paginator\PaginatorInterface;
use BaksDev\Delivery\Entity\Event\DeliveryEvent;
use BaksDev\Delivery\Entity\Trans\DeliveryTrans;
use BaksDev\Delivery\Type\Id\DeliveryUid;
use BaksDev\DeliveryTransport\Entity\Package\DeliveryPackage;
use BaksDev\DeliveryTransport\Entity\Package\DeliveryPackageTransport;
use BaksDev\DeliveryTransport\Entity\Package\Stocks\DeliveryPackageStocks;
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
use BaksDev\Materials\Category\Entity\CategoryMaterial;
use BaksDev\Materials\Category\Entity\Offers\CategoryMaterialOffers;
use BaksDev\Materials\Category\Entity\Offers\Trans\CategoryMaterialOffersTrans;
use BaksDev\Materials\Category\Entity\Offers\Variation\CategoryMaterialVariation;
use BaksDev\Materials\Category\Entity\Offers\Variation\Modification\CategoryMaterialModification;
use BaksDev\Materials\Category\Entity\Offers\Variation\Modification\Trans\CategoryMaterialModificationTrans;
use BaksDev\Materials\Category\Entity\Offers\Variation\Trans\CategoryMaterialVariationTrans;
use BaksDev\Materials\Category\Entity\Trans\CategoryMaterialTrans;
use BaksDev\Materials\Stocks\Entity\Stock\Event\MaterialStockEvent;
use BaksDev\Materials\Stocks\Entity\Stock\Materials\MaterialStockMaterial;
use BaksDev\Materials\Stocks\Entity\Stock\MaterialStock;
use BaksDev\Materials\Stocks\Entity\Stock\Modify\MaterialStockModify;
use BaksDev\Materials\Stocks\Entity\Stock\Move\MaterialStockMove;
use BaksDev\Materials\Stocks\Entity\Stock\Orders\MaterialStockOrder;
use BaksDev\Materials\Stocks\Entity\Total\MaterialStockTotal;
use BaksDev\Materials\Stocks\Forms\PackageFilter\MaterialStockPackageFilterInterface;
use BaksDev\Materials\Stocks\Type\Status\MaterialStockStatus;
use BaksDev\Materials\Stocks\Type\Status\MaterialStockStatus\Collection\MaterialStockStatusDivide;
use BaksDev\Materials\Stocks\Type\Status\MaterialStockStatus\Collection\MaterialStockStatusError;
use BaksDev\Materials\Stocks\Type\Status\MaterialStockStatus\Collection\MaterialStockStatusMoving;
use BaksDev\Materials\Stocks\Type\Status\MaterialStockStatus\Collection\MaterialStockStatusPackage;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Entity\User\Delivery\OrderDelivery;
use BaksDev\Orders\Order\Entity\User\OrderUser;
use BaksDev\Users\Profile\UserProfile\Entity\Avatar\UserProfileAvatar;
use BaksDev\Users\Profile\UserProfile\Entity\Event\UserProfileEvent;
use BaksDev\Users\Profile\UserProfile\Entity\Info\UserProfileInfo;
use BaksDev\Users\Profile\UserProfile\Entity\Personal\UserProfilePersonal;
use BaksDev\Users\Profile\UserProfile\Entity\UserProfile;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use DateTimeImmutable;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;

final class AllMaterialStocksPackageRepository implements AllMaterialStocksPackageInterface
{
    private ?SearchDTO $search = null;

    private ?MaterialStockPackageFilterInterface $filter = null;

    public function __construct(
        private readonly DBALQueryBuilder $DBALQueryBuilder,
        private readonly PaginatorInterface $paginator,
    ) {}

    public function search(SearchDTO $search): self
    {
        $this->search = $search;
        return $this;
    }

    public function filter(MaterialStockPackageFilterInterface $filter): self
    {
        $this->filter = $filter;
        return $this;
    }

    public function setLimit(int $limit): self
    {
        $this->paginator->setLimit($limit);
        return $this;
    }


    /**
     * Метод возвращает все заявки на упаковку заказов.
     */
    public function findPaginator(UserProfileUid $profile): PaginatorInterface
    {
        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        // Stock

        // MaterialStock
        $dbal->select('stock.id');
        $dbal->addSelect('stock.event');

        $dbal->from(MaterialStock::class, 'stock');

        // MaterialStockEvent
        $dbal->addSelect('event.number');
        $dbal->addSelect('event.comment');
        $dbal->addSelect('event.status');

        $dbal->join(
            'stock',
            MaterialStockEvent::class,
            'event',
            '
            event.id = stock.event AND 
            event.profile = :profile AND  
            event.status IN (:status)
            ')
            ->setParameter(
                'delivery',
                [
                    MaterialStockStatusPackage::STATUS,
                    MaterialStockStatusMoving::STATUS,
                    MaterialStockStatusError::STATUS,
                    MaterialStockStatusDivide::STATUS,
                ],
                ArrayParameterType::STRING
            );

        //        $dbal->setParameter('package', MaterialStockStatusPackage::class, MaterialStockStatus::TYPE);
        //        $dbal->setParameter('move', MaterialStockStatusMoving::class, MaterialStockStatus::TYPE);
        //        $dbal->setParameter('error', MaterialStockStatusError::class, MaterialStockStatus::TYPE);
        //        $dbal->setParameter('divide', MaterialStockStatusDivide::class, MaterialStockStatus::TYPE);


        $dbal->setParameter('profile', $profile, UserProfileUid::TYPE);


        /** Погрузка на доставку */

        if(class_exists(DeliveryPackage::class))
        {

            /** Подгружаем разделенные заказы */


            $existDeliveryPackage = $this->DBALQueryBuilder->createQueryBuilder(self::class);
            $existDeliveryPackage->select('1');
            $existDeliveryPackage->from(DeliveryPackage::class, 'bGIuGLiNkf');
            $existDeliveryPackage->where('bGIuGLiNkf.event = delivery_stocks.event');

            $dbal->leftJoin(
                'stock',
                DeliveryPackageStocks::class,
                'delivery_stocks',
                'delivery_stocks.stock = stock.id AND EXISTS('.$existDeliveryPackage->getSQL().')'
            );


            $dbal->leftJoin(
                'delivery_stocks',
                DeliveryPackage::class,
                'delivery_package',
                'delivery_package.event = delivery_stocks.event'
            );

            $dbal->addSelect('delivery_transport.date_package');

            $dbal->leftJoin(
                'delivery_package',
                DeliveryPackageTransport::class,
                'delivery_transport',
                'delivery_transport.package = delivery_package.id'
            );

            //$dbal->addOrderBy('delivery_transport.date_package');

        }
        else
        {
            $dbal->addSelect('NULL AS date_package');
            $dbal->setParameter('divide', 'ntUIGnScMq');
        }


        $dbal
            ->addSelect('modify.mod_date')
            ->join(
                'stock',
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
            ->addSelect('SUM(total.total) AS stock_total')
            ->addSelect("STRING_AGG(CONCAT(total.storage, ': [', total.total, ']'), ', ' ORDER BY total.total) AS stock_storage")
            ->leftJoin(
                'stock_material',
                MaterialStockTotal::class,
                'total',
                '
                total.profile = event.profile AND
                total.material = stock_material.material AND 
                (total.offer IS NULL OR total.offer = stock_material.offer) AND 
                (total.variation IS NULL OR total.variation = stock_material.variation) AND 
                (total.modification IS NULL OR total.modification = stock_material.modification) AND
                total.total > 0
            '
            );

        $dbal->join(
            'stock',
            MaterialStockOrder::class,
            'ord',
            'ord.event = stock.event'
        );


        $dbal
            ->addSelect('orders.id AS order_id')
            ->leftJoin(
                'ord',
                Order::class,
                'orders',
                'orders.id = ord.ord'
            );

        $dbal
            ->addSelect('order_event.danger AS order_danger')
            ->addSelect('order_event.comment AS order_comment')
            ->leftJoin(
                'ord',
                OrderEvent::class,
                'order_event',
                'order_event.id = orders.event'
            );

        $dbal->leftJoin(
            'orders',
            OrderUser::class,
            'order_user',
            'order_user.event = orders.event'
        );


        $dbal->addSelect('order_delivery.delivery_date');

        $delivery_condition = 'order_delivery.usr = order_user.id';

        if($this->filter !== null)
        {
            if($this->filter->getDate() instanceof DateTimeImmutable)
            {
                $delivery_condition .= ' AND order_delivery.delivery_date >= :delivery_date_start AND order_delivery.delivery_date < :delivery_date_end';
                $dbal->setParameter('delivery_date_start', $this->filter->getDate(), Types::DATE_IMMUTABLE);
                $dbal->setParameter('delivery_date_end', $this->filter->getDate()?->modify('+1 day'), Types::DATE_IMMUTABLE);
            }


            if($this->filter->getDelivery() instanceof DeliveryUid)
            {
                $delivery_condition .= ' AND order_delivery.delivery = :delivery';
                $dbal->setParameter('delivery', $this->filter->getDelivery(), DeliveryUid::TYPE);
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
            'delivery_event.id = order_delivery.event AND delivery_event.main = order_delivery.delivery'
        );

        $dbal
            ->addSelect('delivery_trans.name AS delivery_name')
            ->leftJoin(
                'delivery_event',
                DeliveryTrans::class,
                'delivery_trans',
                'delivery_trans.event = delivery_event.id AND delivery_trans.local = :local'
            );


        $dbal
            ->addSelect('material.id as material_id')
            ->addSelect('material.event as material_event')
            ->leftJoin(
                'stock_material',
                Material::class,
                'material',
                'material.id = stock_material.material'
            );

        // Material Event
        $dbal->leftJoin(
            'material',
            MaterialEvent::class,
            'material_event',
            'material_event.id = material.event'
        );

        // Material Info
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
            ->leftJoin(
                'material_event',
                MaterialTrans::class,
                'material_trans',
                'material_trans.event = material_event.id AND material_trans.local = :local'
            );

        /*
         * Торговое предложение
         */

        $dbal
            ->addSelect('material_offer.id as material_offer_uid')
            ->addSelect('material_offer.value as material_offer_value')
            ->leftJoin(
                'material_event',
                MaterialOffer::class,
                'material_offer',
                'material_offer.event = material_event.id AND material_offer.const = stock_material.offer'
            );

        // Получаем тип торгового предложения
        $dbal
            ->addSelect('category_offer.reference as material_offer_reference')
            ->leftJoin(
                'material_offer',
                CategoryMaterialOffers::class,
                'category_offer',
                'category_offer.id = material_offer.category_offer'
            );

        $dbal
            ->addSelect('category_offer_trans.name as material_offer_name')
            ->leftJoin(
                'category_offer',
                CategoryMaterialOffersTrans::class,
                'category_offer_trans',
                'category_offer_trans.offer = category_offer.id AND category_offer_trans.local = :local'
            );

        /*
         * Множественные варианты торгового предложения
         */

        $dbal
            ->addSelect('material_variation.id as material_variation_uid')
            ->addSelect('material_variation.value as material_variation_value')
            ->leftJoin(
                'material_offer',
                MaterialVariation::class,
                'material_variation',
                'material_variation.offer = material_offer.id AND material_variation.const = stock_material.variation'
            );

        // Получаем тип множественного варианта
        $dbal
            ->addSelect('category_variation.reference as material_variation_reference')
            ->leftJoin(
                'material_variation',
                CategoryMaterialVariation::class,
                'category_variation',
                'category_variation.id = material_variation.category_variation'
            );

        $dbal
            ->addSelect('category_variation_trans.name as material_variation_name')
            ->leftJoin(
                'category_variation',
                CategoryMaterialVariationTrans::class,
                'category_variation_trans',
                'category_variation_trans.variation = category_variation.id AND category_variation_trans.local = :local'
            );

        /*
         * Модификация множественного варианта торгового предложения
         */

        $dbal
            ->addSelect('material_modification.id as material_modification_uid')
            ->addSelect('material_modification.value as material_modification_value')
            ->leftJoin(
                'material_variation',
                MaterialModification::class,
                'material_modification',
                'material_modification.variation = material_variation.id AND material_modification.const = stock_material.modification'
            );

        // Получаем тип модификации множественного варианта
        $dbal
            ->addSelect('category_modification.reference as material_modification_reference')
            ->leftJoin(
                'material_modification',
                CategoryMaterialModification::class,
                'category_modification',
                'category_modification.id = material_modification.category_modification'
            );

        $dbal
            ->addSelect('category_modification_trans.name as material_modification_name')
            ->leftJoin(
                'category_modification',
                CategoryMaterialModificationTrans::class,
                'category_modification_trans',
                'category_modification_trans.modification = category_modification.id AND category_modification_trans.local = :local'
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


        /*$dbal->addSelect("
			COALESCE(
                NULLIF(COUNT(material_offer), 0),
                NULLIF(COUNT(material_variation), 0),
                NULLIF(COUNT(material_modification), 0),
                0
            ) AS offer_count
		");*/


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

        $dbal->addSelect('category_trans.name AS category_name');
        $dbal->leftJoin(
            'category',
            CategoryMaterialTrans::class,
            'category_trans',
            'category_trans.event = category.event AND category_trans.local = :local'
        );

        // ОТВЕТСТВЕННЫЙ

        // UserProfile
        $dbal->addSelect('users_profile.event as users_profile_event');
        $dbal->leftJoin(
            'event',
            UserProfile::class,
            'users_profile',
            'users_profile.id = event.profile'
        );

        // Info
        $dbal->leftJoin(
            'event',
            UserProfileInfo::class,
            'users_profile_info',
            'users_profile_info.profile = event.profile'
        );

        // Event
        $dbal->leftJoin(
            'users_profile',
            UserProfileEvent::class,
            'users_profile_event',
            'users_profile_event.id = users_profile.event'
        );

        // Personal
        $dbal->addSelect('users_profile_personal.username AS users_profile_username');

        $dbal->leftJoin(
            'users_profile_event',
            UserProfilePersonal::class,
            'users_profile_personal',
            'users_profile_personal.event = users_profile_event.id'
        );

        $dbal->leftJoin(
            'users_profile_event',
            UserProfileAvatar::class,
            'users_profile_avatar',
            'users_profile_avatar.event = users_profile_event.id'
        );

        // Группа


        /** Проверка перемещения по заказу */
        $dbalExist = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbalExist->select('1');
        $dbalExist->from(MaterialStockMove::class, 'exist_move');
        $dbalExist->where('exist_move.ord = ord.ord ');

        $dbalExist->join(
            'exist_move',
            MaterialStockEvent::class,
            'exist_move_event',
            'exist_move_event.id = exist_move.event AND  (
                exist_move_event.status != :incoming
            )'
        );


        $dbalExist->join(
            'exist_move_event',
            MaterialStock::class,
            'exist_move_stock',
            'exist_move_stock.event = exist_move_event.id'
        );


        $dbal->addSelect(sprintf('EXISTS(%s) AS materials_move', $dbalExist->getSQL()));
        $dbal->setParameter('incoming', new MaterialStockStatus(MaterialStockstatus\Collection\MaterialStockStatusIncoming::class), MaterialStockStatus::TYPE);


        /** Пункт назначения при перемещении */

        $dbal->leftJoin(
            'event',
            MaterialStockMove::class,
            'move_stock',
            'move_stock.event = event.id'
        );


        // UserProfile
        $dbal->leftJoin(
            'move_stock',
            UserProfile::class,
            'users_profile_move',
            'users_profile_move.id = move_stock.destination'
        );

        $dbal
            ->addSelect('users_profile_personal_move.username AS users_profile_destination')
            ->leftJoin(
                'users_profile_move',
                UserProfilePersonal::class,
                'users_profile_personal_move',
                'users_profile_personal_move.event = users_profile_move.event'
            );

        /** Пункт назначения при перемещении */

        $dbal->leftOneJoin(
            'ord',
            MaterialStockMove::class,
            'destination_stock',
            'destination_stock.event != stock.event AND destination_stock.ord = ord.ord',
            'event'
        );


        $dbal->leftJoin(
            'destination_stock',
            MaterialStockEvent::class,
            'destination_event',
            'destination_event.id = destination_stock.event'
        );

        // UserProfile
        $dbal->leftJoin(
            'destination_stock',
            UserProfile::class,
            'users_profile_destination',
            'users_profile_destination.id = destination_event.profile'
        );

        $dbal
            ->addSelect('users_profile_personal_destination.username AS users_profile_move')
            ->leftJoin(
                'users_profile_destination',
                UserProfilePersonal::class,
                'users_profile_personal_destination',
                'users_profile_personal_destination.event = users_profile_destination.event'
            );


        // Поиск
        if($this->search?->getQuery())
        {
            $dbal
                ->createSearchQueryBuilder($this->search)
                ->addSearchLike('event.number')
                ->addSearchLike('material_modification.article')
                ->addSearchLike('material_variation.article')
                ->addSearchLike('material_offer.article')
                ->addSearchLike('material_info.article');
        }


        $dbal->addOrderBy('materials_move', 'ASC');
        $dbal->addOrderBy('order_delivery.delivery_date', 'ASC');
        $dbal->addOrderBy('stock.id', 'ASC');

        $dbal->addGroupBy('ord.ord');
        $dbal->allGroupByExclude();

        return $this->paginator->fetchAllAssociative($dbal);

    }


    /**
     * Метод возвращает всю сырьё требующая сборки
     */
    public function findAll(UserProfileUid $profile): ?array
    {
        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        // Stock

        // MaterialStock
        //$dbal->select('stock.id');
        //$dbal->addSelect('stock.event');

        $dbal->from(MaterialStock::class, 'stock');

        // MaterialStockEvent
        //$dbal->addSelect('event.number');
        //$dbal->addSelect('event.comment');
        //$dbal->addSelect('event.status');

        $dbal->join(
            'stock',
            MaterialStockEvent::class,
            'event',
            '
            event.id = stock.event AND 
            event.profile = :profile AND  
            (
                event.status = :package OR 
                event.status = :move
                
            )'
        );

        $dbal->setParameter('package', new MaterialStockStatus(new MaterialStockstatus\Collection\MaterialStockStatusPackage()), MaterialStockStatus::TYPE);
        $dbal->setParameter('move', new MaterialStockStatus(new MaterialStockstatus\Collection\MaterialStockStatusMoving()), MaterialStockStatus::TYPE);
        $dbal->setParameter('profile', $profile, UserProfileUid::TYPE);


        /** Погрузка на доставку */

        //        if(defined(DeliveryPackage::class.'::class'))
        //        {
        //
        //            /** Подгружаем разделенные заказы */
        //
        //
        //            $existDeliveryPackage = $this->DBALQueryBuilder->createQueryBuilder(self::class);
        //            $existDeliveryPackage->select('1');
        //            $existDeliveryPackage->from(DeliveryPackage::class, 'bGIuGLiNkf');
        //            $existDeliveryPackage->where('bGIuGLiNkf.event = delivery_stocks.event');
        //
        //            $dbal->leftJoin(
        //                'stock',
        //                DeliveryPackageStocks::class,
        //                'delivery_stocks',
        //                'delivery_stocks.stock = stock.id AND EXISTS('.$existDeliveryPackage->getSQL().')'
        //            );
        //
        //
        //            $dbal->leftJoin(
        //                'delivery_stocks',
        //                DeliveryPackage::class,
        //                'delivery_package',
        //                'delivery_package.event = delivery_stocks.event'
        //            );
        //
        //            //$dbal->addSelect('delivery_transport.date_package');
        //
        //            $dbal->leftJoin(
        //                'delivery_package',
        //                DeliveryPackageTransport::class,
        //                'delivery_transport',
        //                'delivery_transport.package = delivery_package.id'
        //            );
        //
        //            //$dbal->addOrderBy('delivery_transport.date_package');
        //
        //        }
        //        else
        //        {
        //            $dbal->addSelect('NULL AS date_package');
        //            $dbal->setParameter('divide', 'ntUIGnScMq');
        //        }

        //
        //        $dbal
        //            ->addSelect('modify.mod_date')
        //            ->join(
        //                'stock',
        //                MaterialStockModify::class,
        //                'modify',
        //                'modify.event = stock.event'
        //            );


        $dbal
            ->addSelect('SUM(stock_material.total) AS total')
            ->leftJoin(
                'event',
                MaterialStockMaterial::class,
                'stock_material',
                'stock_material.event = stock.event'
            );


        /* Получаем наличие на указанном складе */

        $storage = $this->DBALQueryBuilder->createQueryBuilder(self::class);
        $storage->select("STRING_AGG(DISTINCT CONCAT(total.storage, ' [', total.total, ']'), ', ' ) AS stock_storage");
        $storage
            ->from(MaterialStockTotal::class, 'total')
            ->where('total.profile = :profile')
            ->andWhere('total.material = stock_material.material')
            ->andWhere('total.offer = stock_material.offer')
            ->andWhere('total.variation = stock_material.variation')
            ->andWhere('total.modification = stock_material.modification')
            ->andWhere('total.total > 0');


        $dbal->addSelect('('.$storage->getSQL().') AS stock_storage');


        $dbal
            ->addGroupBy('stock_material.material')
            ->addGroupBy('stock_material.offer')
            ->addGroupBy('stock_material.variation')
            ->addGroupBy('stock_material.modification');

        $dbal->join(
            'stock',
            MaterialStockOrder::class,
            'ord',
            'ord.event = stock.event'
        );


        $dbal
            //->addSelect('orders.id AS order_id')
            ->leftJoin(
                'ord',
                Order::class,
                'orders',
                'orders.id = ord.ord'
            );

        $dbal->leftJoin(
            'orders',
            OrderUser::class,
            'order_user',
            'order_user.event = orders.event'
        );


        $delivery_condition = 'order_delivery.usr = order_user.id';

        if($this->filter !== null)
        {
            if($this->filter->getDate() instanceof DateTimeImmutable)
            {
                $delivery_condition .= ' AND order_delivery.delivery_date >= :delivery_date_start AND order_delivery.delivery_date < :delivery_date_end';
                $dbal->setParameter('delivery_date_start', $this->filter->getDate(), Types::DATE_IMMUTABLE);
                $dbal->setParameter('delivery_date_end', $this->filter->getDate()->modify('+1 day'), Types::DATE_IMMUTABLE);
            }


            if($this->filter->getDelivery() instanceof DeliveryUid)
            {
                $delivery_condition .= ' AND order_delivery.delivery = :delivery';
                $dbal->setParameter('delivery', $this->filter->getDelivery(), DeliveryUid::TYPE);
            }

        }

        $dbal
            ->join(
                'order_user',
                OrderDelivery::class,
                'order_delivery',
                $delivery_condition
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


        // Material Trans
        $dbal
            ->addSelect('material_trans.name as material_name')
            ->join(
                'material',
                MaterialTrans::class,
                'material_trans',
                'material_trans.event = material.event AND material_trans.local = :local'
            );

        /*
         * Торговое предложение
         */

        $dbal
            ->addSelect('material_offer.id as material_offer_uid')
            ->addSelect('material_offer.value as material_offer_value')
            ->leftJoin(
                'material',
                MaterialOffer::class,
                'material_offer',
                'material_offer.event = material.event AND material_offer.const = stock_material.offer'
            );

        // Получаем тип торгового предложения
        $dbal
            ->addSelect('category_offer.reference as material_offer_reference')
            ->leftJoin(
                'material_offer',
                CategoryMaterialOffers::class,
                'category_offer',
                'category_offer.id = material_offer.category_offer'
            );


        /*
         * Множественные варианты торгового предложения
         */

        $dbal
            ->addSelect('material_variation.id as material_variation_uid')
            ->addSelect('material_variation.value as material_variation_value')
            ->leftJoin(
                'material_offer',
                MaterialVariation::class,
                'material_variation',
                'material_variation.offer = material_offer.id AND material_variation.const = stock_material.variation'
            );

        // Получаем тип множественного варианта
        $dbal
            ->addSelect('category_variation.reference as material_variation_reference')
            ->leftJoin(
                'material_variation',
                CategoryMaterialVariation::class,
                'category_variation',
                'category_variation.id = material_variation.category_variation'
            );


        /*
         * Модификация множественного варианта торгового предложения
         */

        $dbal
            ->addSelect('material_modification.id as material_modification_uid')
            ->addSelect('material_modification.value as material_modification_value')
            ->leftJoin(
                'material_variation',
                MaterialModification::class,
                'material_modification',
                'material_modification.variation = material_variation.id AND material_modification.const = stock_material.modification'
            );

        // Получаем тип модификации множественного варианта
        $dbal
            ->addSelect('category_modification.reference as material_modification_reference')
            ->leftJoin(
                'material_modification',
                CategoryMaterialModification::class,
                'category_modification',
                'category_modification.id = material_modification.category_modification'
            );


        // ОТВЕТСТВЕННЫЙ

        // UserProfile
        //$dbal->addSelect('users_profile.event as users_profile_event');
        $dbal->join(
            'event',
            UserProfile::class,
            'users_profile',
            'users_profile.id = event.profile'
        );

        // Info
        //        $dbal->leftJoin(
        //            'event',
        //            UserProfileInfo::class,
        //            'users_profile_info',
        //            'users_profile_info.profile = event.profile'
        //        );

        // Event
        //        $dbal->leftJoin(
        //            'users_profile',
        //            UserProfileEvent::class,
        //            'users_profile_event',
        //            'users_profile_event.id = users_profile.event'
        //        );

        // Personal
        $dbal->addSelect('users_profile_personal.username AS users_profile_username');

        $dbal->leftJoin(
            'users_profile',
            UserProfilePersonal::class,
            'users_profile_personal',
            'users_profile_personal.event = users_profile.event'
        );


        // Группа


        //$dbal->addSelect('NULL AS group_name'); // Название группы

        //        /** Проверка перемещения по заказу */
        //        $dbalExist = $this->DBALQueryBuilder->createQueryBuilder(self::class);
        //
        //        $dbalExist->select('1');
        //        $dbalExist->from(MaterialStockMove::class, 'exist_move');
        //        $dbalExist->where('exist_move.ord = ord.ord ');
        //
        //        $dbalExist->join(
        //            'exist_move',
        //            MaterialStockEvent::class,
        //            'exist_move_event',
        //            'exist_move_event.id = exist_move.event AND  (
        //                exist_move_event.status != :incoming
        //            )'
        //        );
        //
        //
        //        $dbalExist->join(
        //            'exist_move_event',
        //            MaterialStock::class,
        //            'exist_move_stock',
        //            'exist_move_stock.event = exist_move_event.id'
        //        );
        //
        //
        //        $dbal->addSelect(sprintf('EXISTS(%s) AS materials_move', $dbalExist->getSQL()));
        //        $dbal->setParameter('incoming', new MaterialStockstatus(new MaterialStockstatus\MaterialStockstatusIncoming()), MaterialStockstatus::TYPE);


        /** Пункт назначения при перемещении */

        $dbal->leftJoin(
            'event',
            MaterialStockMove::class,
            'move_stock',
            'move_stock.event = event.id'
        );


        // UserProfile
        $dbal->leftJoin(
            'move_stock',
            UserProfile::class,
            'users_profile_move',
            'users_profile_move.id = move_stock.destination'
        );

        $dbal
            //->addSelect('users_profile_personal_move.username AS users_profile_destination')
            ->leftJoin(
                'users_profile_move',
                UserProfilePersonal::class,
                'users_profile_personal_move',
                'users_profile_personal_move.event = users_profile_move.event'
            );

        /** Пункт назначения при перемещении */

        $dbal->leftOneJoin(
            'ord',
            MaterialStockMove::class,
            'destination_stock',
            'destination_stock.event != stock.event AND destination_stock.ord = ord.ord',
            'event'
        );


        $dbal->leftJoin(
            'destination_stock',
            MaterialStockEvent::class,
            'destination_event',
            'destination_event.id = destination_stock.event'
        );

        // UserProfile
        $dbal->leftJoin(
            'destination_stock',
            UserProfile::class,
            'users_profile_destination',
            'users_profile_destination.id = destination_event.profile'
        );

        $dbal
            //->addSelect('users_profile_personal_destination.username AS users_profile_move')
            ->leftJoin(
                'users_profile_destination',
                UserProfilePersonal::class,
                'users_profile_personal_destination',
                'users_profile_personal_destination.event = users_profile_destination.event'
            );


        // Поиск
        //        if($this->search?->getQuery())
        //        {
        //            $dbal
        //                ->createSearchQueryBuilder($this->search)
        //                ->addSearchLike('event.number')
        //                ->addSearchLike('material_modification.article')
        //                ->addSearchLike('material_variation.article')
        //                ->addSearchLike('material_offer.article')
        //                ->addSearchLike('material_info.article');
        //        }


        //$dbal->addOrderBy('stock_storage', 'ASC');

        //$dbal->addOrderBy('order_delivery.delivery_date', 'ASC');
        //$dbal->addOrderBy('stock.id', 'ASC');

        //$dbal->addGroupBy('ord.ord');
        $dbal->allGroupByExclude();
        $dbal->addOrderBy('stock_storage', 'ASC');

        ///$dbal->addGroupBy('ord.ord');
        //$dbal->allGroupByExclude();

        //        $dbal->setMaxResults(24);
        //        dd($dbal->fetchAllAssociative());
        //        dd($this->paginator->fetchAllAssociative($dbal));


        return $dbal
            //->enableCache('materials-stocks')
            ->fetchAllAssociative();

    }

}
