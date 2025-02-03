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

namespace BaksDev\Materials\Stocks\UseCase\Admin\Purchase\Materials;

use BaksDev\Materials\Catalog\Type\Id\MaterialUid;
use BaksDev\Materials\Catalog\Type\Offers\ConstId\MaterialOfferConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\MaterialVariationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\ConstId\MaterialModificationConst;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class MaterialStockForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Склад

        //        $builder->add('warehouse', HiddenType::class);
        //
        //        $builder->get('warehouse')->addModelTransformer(
        //            new CallbackTransformer(
        //                function ($warehouse) {
        //                    return $warehouse instanceof ContactsRegionCallUid ? $warehouse->getValue() : $warehouse;
        //                },
        //                function ($warehouse) {
        //                    return new ContactsRegionCallUid($warehouse);
        //                }
        //            )
        //        );

        // Продукт

        $builder->add('material', HiddenType::class);

        $builder->get('material')->addModelTransformer(
            new CallbackTransformer(
                function($material) {
                    return $material instanceof MaterialUid ? $material->getValue() : $material;
                },
                function($material) {
                    return new MaterialUid($material);
                }
            )
        );

        // Торговое предложение

        $builder->add('offer', HiddenType::class);

        $builder->get('offer')->addModelTransformer(
            new CallbackTransformer(
                function($offer) {
                    return $offer instanceof MaterialOfferConst ? $offer->getValue() : $offer;
                },
                function($offer) {
                    return $offer ? new MaterialOfferConst($offer) : null;
                }
            )
        );

        // Множественный вариант

        $builder->add('variation', HiddenType::class);

        $builder->get('variation')->addModelTransformer(
            new CallbackTransformer(
                function($variation) {
                    return $variation instanceof MaterialVariationConst ? $variation->getValue() : $variation;
                },
                function($variation) {
                    return $variation ? new MaterialVariationConst($variation) : null;
                }
            )
        );

        // Модификация множественного варианта

        $builder->add('modification', HiddenType::class);

        $builder->get('modification')->addModelTransformer(
            new CallbackTransformer(
                function($modification) {
                    return $modification instanceof MaterialModificationConst ? $modification->getValue() : $modification;
                },
                function($modification) {
                    return $modification ? new MaterialModificationConst($modification) : null;
                }
            )
        );

        // Количество

        $builder->add('total', HiddenType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MaterialStockDTO::class,
        ]);
    }
}
