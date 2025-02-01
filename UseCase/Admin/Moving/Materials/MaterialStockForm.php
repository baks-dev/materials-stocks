<?php
/*
 *  Copyright 2022.  Baks.dev <admin@baks.dev>
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *  http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *   limitations under the License.
 *
 */

namespace BaksDev\Materials\Stocks\UseCase\Admin\Moving\Materials;

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

        // Продукт

        $builder->add('material', HiddenType::class);

        $builder->get('material')->addModelTransformer(
            new CallbackTransformer(
                function($material) {
                    return $material instanceof ProductUid ? $material->getValue() : $material;
                },
                function($material) {
                    return new ProductUid($material);
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
