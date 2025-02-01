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

namespace BaksDev\Materials\Stocks\Repository\MaterialsByMaterialStocks;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Delivery\Entity\Fields\DeliveryField;
use BaksDev\Delivery\Entity\Fields\Trans\DeliveryFieldTrans;
use BaksDev\Materials\Stocks\Entity\Stock\Event\MaterialStockEvent;
use BaksDev\Materials\Stocks\Entity\Stock\Materials\MaterialStockMaterial;
use BaksDev\Materials\Stocks\Entity\Stock\MaterialStock;
use BaksDev\Materials\Stocks\Entity\Stock\Move\MaterialStockMove;
use BaksDev\Materials\Stocks\Entity\Stock\Orders\MaterialStockOrder;
use BaksDev\Materials\Stocks\Entity\Total\MaterialStockTotal;
use BaksDev\Materials\Stocks\Type\Id\MaterialStockUid;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Entity\User\Delivery\Field\OrderDeliveryField;
use BaksDev\Orders\Order\Entity\User\Delivery\OrderDelivery;
use BaksDev\Orders\Order\Entity\User\OrderUser;
use BaksDev\Products\Category\Entity\CategoryProduct;
use BaksDev\Products\Category\Entity\Info\CategoryProductInfo;
use BaksDev\Products\Category\Entity\Offers\CategoryProductOffers;
use BaksDev\Products\Category\Entity\Offers\Trans\CategoryProductOffersTrans;
use BaksDev\Products\Category\Entity\Offers\Variation\CategoryProductVariation;
use BaksDev\Products\Category\Entity\Offers\Variation\Modification\CategoryProductModification;
use BaksDev\Products\Category\Entity\Offers\Variation\Modification\Trans\CategoryProductModificationTrans;
use BaksDev\Products\Category\Entity\Offers\Variation\Trans\CategoryProductVariationTrans;
use BaksDev\Products\Product\Entity\Category\ProductCategory;
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
use BaksDev\Users\Profile\UserProfile\Entity\Personal\UserProfilePersonal;
use BaksDev\Users\Profile\UserProfile\Entity\UserProfile;
use InvalidArgumentException;

final  class MaterialByMaterialStocksRepository implements MaterialByMaterialStocksInterface
{
    private MaterialStockUid|false $stock = false;

    public function __construct(private readonly DBALQueryBuilder $DBALQueryBuilder) {}

    public function stock(MaterialStock|MaterialStockUid|string $stock): self
    {
        if(is_string($stock))
        {
            $stock = new MaterialStockUid($stock);
        }

        if($stock instanceof MaterialStock)
        {
            $stock = $stock->getId();
        }

        $this->stock = $stock;

        return $this;
    }

    /**
     * Метод возвращает информацию о продукции в складской заявке.
     */
    public function find(): array|bool
    {
        if(false === $this->stock)
        {
            throw new InvalidArgumentException('Invalid Argument MaterialStock');
        }


        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $dbal
            ->from(MaterialStock::class, 'stock')
            ->where('stock.id = :stock')
            ->setParameter('stock', $this->stock, MaterialStockUid::TYPE);


        $dbal
            ->addSelect('stock_event.number')
            ->
            join(
                'stock',
                MaterialStockEvent::class,
                'stock_event',
                'stock_event.id = stock.event'
            );


        $dbal
            ->addSelect('stock_material.total')
            ->join(
                'stock',
                MaterialStockMaterial::class,
                'stock_material',
                'stock_material.event = stock.event'
            );


        /** Информация о заказе */

        $dbal->leftJoin(
            'stock_event',
            MaterialStockOrder::class,
            'stock_order',
            'stock_order.event = stock_event.id'
        );


        $dbal
            ->leftJoin(
                'stock_event',
                MaterialStockMove::class,
                'stock_move',
                'stock_move.event = stock_event.id'
            );

        $dbal->join(
            'stock_order',
            Order::class,
            'orders',
            'orders.id = 
            
                CASE
                    WHEN stock_move.ord IS NOT NULL THEN stock_move.ord
                   ELSE stock_order.ord
                END
            
            ');


        $dbal
            ->addSelect('destination.id AS destination')
            ->leftJoin(
                'stock_move',
                UserProfile::class,
                'destination',
                'destination.id = stock_move.destination '
            );


        $dbal
            ->addSelect('destination_personal.location AS destination_location')
            ->addSelect('destination_personal.latitude AS destination_latitude')
            ->addSelect('destination_personal.longitude AS destination_longitude')
            ->leftJoin(
                'destination',
                UserProfilePersonal::class,
                'destination_personal',
                'destination_personal.event = destination.event '
            );


        $dbal->leftJoin(
            'orders',
            OrderEvent::class,
            'orders_event',
            'orders_event.id = orders.event'
        );


        $dbal
            ->addSelect('order_user.profile AS order_client')
            ->leftJoin(
                'orders',
                OrderUser::class,
                'order_user',
                'order_user.event = orders.event'
            );


        $dbal->leftJoin(
            'order_user',
            OrderDelivery::class,
            'order_delivery',
            'order_delivery.usr = order_user.id'
        );

        $dbal->leftJoin(
            'order_delivery',
            OrderDeliveryField::class,
            'order_delivery_fields',
            'order_delivery_fields.delivery = order_delivery.id'
        );

        $dbal->leftJoin(
            'order_delivery',
            DeliveryField::class,
            'delivery_field',
            'delivery_field.id = order_delivery_fields.field'
        );

        $dbal->leftJoin(
            'delivery_field',
            DeliveryFieldTrans::class,
            'delivery_field_trans',
            'delivery_field_trans.field = delivery_field.id AND delivery_field_trans.local = :local'
        );


        /* Информация о доставке  */
        $dbal->addSelect(
            "JSON_AGG
            ( /*DISTINCT*/

                    JSONB_BUILD_OBJECT
                    (
                        'order_field_name', delivery_field_trans.name,
                        'order_field_type', delivery_field.type,
                        'order_field_value', order_delivery_fields.value
                    )
            )
			AS order_fields"
        );


        /**
         * Продукция
         */

        $dbal
            ->addSelect('material.id AS material_id')
            ->join(
                'stock',
                Product::class,
                'material',
                'material.id = stock_material.material'
            );


        $dbal->addSelect('material_info.url AS material_url');//->addGroupBy('material_info.url');
        $dbal->join(
            'material',
            ProductInfo::class,
            'material_info',
            'material_info.material = stock_material.material '
        );


        $dbal
            ->addSelect('material_trans.name AS material_name')
            ->leftJoin(
                'material',
                ProductTrans::class,
                'material_trans',
                'material_trans.event = material.event AND material_trans.local = :local'
            );


        /* Торговое предложение */

        $dbal
            ->addSelect('material_offer.const AS material_offer_const')
            ->addSelect('material_offer.value AS material_offer_value')
            ->addSelect('material_offer.postfix AS material_offer_postfix')
            ->leftJoin(
                'material',
                ProductOffer::class,
                'material_offer',
                'material_offer.const = stock_material.offer AND material_offer.event = material.event'
            );


        /* Тип торгового предложения */

        $dbal
            ->addSelect('category_offer.reference AS material_offer_reference')
            ->addSelect('category_offer_trans.name AS material_offer_name')
            ->leftJoin(
                'material_offer',
                CategoryProductOffers::class,
                'category_offer',
                'category_offer.id = material_offer.category_offer'
            );

        /* Название торгового предложения */
        $dbal->leftJoin(
            'category_offer',
            CategoryProductOffersTrans::class,
            'category_offer_trans',
            'category_offer_trans.offer = category_offer.id AND category_offer_trans.local = :local'
        );


        /**
         * Множественный вариант
         */

        $dbal
            ->addSelect('material_variation.const AS material_variation_const')
            ->addSelect('material_variation.value AS material_variation_value')
            ->addSelect('material_variation.postfix AS material_variation_postfix')
            ->leftJoin(
                'material_offer',
                ProductVariation::class,
                'material_variation',
                'stock_material.variation IS NOT NULL AND  material_variation.offer = material_offer.id AND material_variation.const = stock_material.variation'
            );

        /* Получаем тип множественного варианта */

        $dbal
            ->addSelect('category_variation.reference AS material_variation_reference')
            ->leftJoin(
                'material_variation',
                CategoryProductVariation::class,
                'category_variation',
                'category_variation.id = material_variation.category_variation'
            );

        /* Получаем название множественного варианта */
        $dbal
            ->addSelect('category_variation_trans.name AS material_variation_name')
            ->leftJoin(
                'category_variation',
                CategoryProductVariationTrans::class,
                'category_variation_trans',
                'category_variation_trans.variation = category_variation.id AND category_variation_trans.local = :local'
            );


        /**
         * Модификация множественного варианта торгового предложения
         */

        $dbal
            ->addSelect('material_modification.const AS material_modification_const')
            ->addSelect('material_modification.value AS material_modification_value')
            ->addSelect('material_modification.postfix AS material_modification_postfix')
            ->leftJoin(
                'material_variation',
                ProductModification::class,
                'material_modification',
                'stock_material.modification IS NOT NULL AND material_modification.variation = material_variation.id AND material_modification.const = stock_material.modification'
            );


        $dbal
            ->addSelect('category_modification.reference AS material_modification_reference')
            ->leftJoin(
                'material_modification',
                CategoryProductModification::class,
                'category_modification',
                'category_modification.id = material_modification.category_modification'
            );

        /* Получаем название типа модификации */
        $dbal
            ->addSelect('category_modification_trans.name AS material_modification_name')
            ->leftJoin(
                'category_modification',
                CategoryProductModificationTrans::class,
                'category_modification_trans',
                'category_modification_trans.modification = category_modification.id AND category_modification_trans.local = :local'
            );


        /* Фото продукта */

        $dbal->leftJoin(
            'material',
            ProductPhoto::class,
            'material_photo',
            'material_photo.event = material.event AND material_photo.root = true'
        );

        $dbal->leftJoin(
            'material_offer',
            ProductOfferImage::class,
            'material_offer_image',
            'material_offer_image.offer = material_offer.id AND material_offer_image.root = true'
        );

        $dbal->leftJoin(
            'material_variation',
            ProductVariationImage::class,
            'material_variation_image',
            'material_variation_image.variation = material_variation.id AND material_variation_image.root = true'
        );

        $dbal->leftJoin(
            'material_modification',
            ProductModificationImage::class,
            'material_modification_image',
            'material_modification_image.modification = material_modification.id AND material_modification_image.root = true'
        );

        $dbal
            ->addSelect("
         CASE
               WHEN material_modification_image.name IS NOT NULL THEN
                    CONCAT ( '/upload/".$dbal->table(ProductModificationImage::class)."' , '/', material_modification_image.name)
               WHEN material_variation_image.name IS NOT NULL THEN
                    CONCAT ( '/upload/".$dbal->table(ProductVariationImage::class)."' , '/', material_variation_image.name)
               WHEN material_offer_image.name IS NOT NULL THEN
                    CONCAT ( '/upload/".$dbal->table(ProductOfferImage::class)."' , '/', material_offer_image.name)
               WHEN material_photo.name IS NOT NULL THEN
                    CONCAT ( '/upload/".$dbal->table(ProductPhoto::class)."' , '/', material_photo.name)
               ELSE NULL
            END
           AS material_image")
            ->addGroupBy('material_modification_image.name')
            ->addGroupBy('material_variation_image.name')
            ->addGroupBy('material_offer_image.name')
            ->addGroupBy('material_photo.name');

        $dbal->addSelect('
         CASE
                WHEN material_modification_image.name IS NOT NULL THEN
                    material_modification_image.ext
               WHEN material_variation_image.name IS NOT NULL THEN
                    material_variation_image.ext
               WHEN material_offer_image.name IS NOT NULL THEN
                    material_offer_image.ext
               WHEN material_photo.name IS NOT NULL THEN
                    material_photo.ext
               ELSE NULL
            END 
            AS material_image_ext')
            ->addGroupBy('material_modification_image.ext')
            ->addGroupBy('material_variation_image.ext')
            ->addGroupBy('material_offer_image.ext')
            ->addGroupBy('material_photo.ext');

        $dbal->addSelect('
        CASE
                WHEN material_modification_image.name IS NOT NULL THEN
                    material_modification_image.cdn
               WHEN material_variation_image.name IS NOT NULL THEN
                    material_variation_image.cdn
               WHEN material_offer_image.name IS NOT NULL THEN
                    material_offer_image.cdn
               WHEN material_photo.name IS NOT NULL THEN
                    material_photo.cdn
               ELSE NULL
            END
            AS material_image_cdn')
            ->addGroupBy('material_modification_image.cdn')
            ->addGroupBy('material_variation_image.cdn')
            ->addGroupBy('material_offer_image.cdn')
            ->addGroupBy('material_photo.cdn');


        /* Категория */
        $dbal->join(
            'material',
            ProductCategory::class,
            'material_category',
            'material_category.event = material.event AND material_category.root = true'
        );


        $dbal->join(
            'material_category',
            CategoryProduct::class,
            'category',
            'category.id = material_category.category'
        );


        $dbal
            ->addSelect('category_info.url AS category_url')
            ->leftJoin(
                'category',
                CategoryProductInfo::class,
                'category_info',
                'category_info.event = category.event'
            );


        /** Наличие и место на складе */

        $dbal
            ->addSelect("STRING_AGG(stock_total.storage, ', ') AS stock_total_storage")
            ->leftJoin(
                'material_modification',
                MaterialStockTotal::class,
                'stock_total',
                '
                    stock_total.profile = stock_event.profile AND
                    stock_total.material = material.id AND
                    stock_total.offer = material_offer.const AND
                    stock_total.variation = material_variation.const AND
                    stock_total.modification = material_modification.const
                '
            );


        $dbal->allGroupByExclude();

        return $dbal
            ->enableCache('materials-stocks', 86400)
            ->fetchAllAssociative();

    }
}
