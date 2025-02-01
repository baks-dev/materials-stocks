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

namespace BaksDev\Materials\Stocks\UseCase\Admin\Purchase;

use BaksDev\Materials\Catalog\Type\Event\ProductEventUid;
use BaksDev\Materials\Catalog\Type\Id\MaterialUid;
use BaksDev\Materials\Catalog\Type\Offers\ConstId\MaterialOfferConst;
use BaksDev\Materials\Catalog\Type\Offers\Id\ProductOfferUid;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\MaterialVariationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Id\ProductVariationUid;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\ConstId\MaterialModificationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\Id\ProductModificationUid;
use BaksDev\Materials\Category\Repository\CategoryChoice\CategoryChoiceInterface;
use BaksDev\Materials\Category\Type\Id\CategoryMaterialUid;
use BaksDev\Materials\Product\Repository\ProductChoice\ProductChoiceInterface;
use BaksDev\Materials\Product\Repository\ProductModificationChoice\ProductModificationChoiceInterface;
use BaksDev\Materials\Product\Repository\ProductOfferChoice\ProductOfferChoiceInterface;
use BaksDev\Materials\Product\Repository\ProductVariationChoice\ProductVariationChoiceInterface;
use BaksDev\Users\Profile\UserProfile\Repository\UserProfileTokenStorage\UserProfileTokenStorageInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class PurchaseMaterialStockForm extends AbstractType
{
    public function __construct(
        #[AutowireIterator('baks.reference.choice')] private readonly iterable $reference,
        private readonly CategoryChoiceInterface $categoryChoice,
        private readonly ProductChoiceInterface $materialChoice,
        private readonly ProductOfferChoiceInterface $materialOfferChoice,
        private readonly ProductVariationChoiceInterface $materialVariationChoice,
        private readonly ProductModificationChoiceInterface $modificationChoice,
        private readonly UserProfileTokenStorageInterface $userProfileTokenStorage
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Номер заявки
        $builder->add('number', TextType::class);


        $builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event): void {
            /** @var PurchaseMaterialStockDTO $PurchaseMaterialStockDTO */
            $PurchaseMaterialStockDTO = $event->getData();
            $PurchaseMaterialStockDTO->setProfile($this->userProfileTokenStorage->getProfile());
        });

        $builder->add('category', ChoiceType::class, [
            'choices' => $this->categoryChoice->findAll(),
            'choice_value' => function(?CategoryMaterialUid $category) {
                return $category?->getValue();
            },
            'choice_label' => function(CategoryMaterialUid $category) {
                return (is_int($category->getAttr()) ? str_repeat(' - ', $category->getAttr() - 1) : '').$category->getOptions();
            },
            'label' => false,
            'required' => false,
        ]);


        /**
         * Продукция категории
         */

        $builder->add(
            'preProduct',
            HiddenType::class,
        );

        $builder
            ->get('preProduct')->addModelTransformer(
                new CallbackTransformer(
                    function($material) {
                        return $material instanceof ProductUid ? $material->getValue() : $material;
                    },
                    function($material) {
                        return $material ? new ProductUid($material) : null;
                    }
                ),
            );


        /**
         * Торговые предложения
         */

        $builder->add(
            'preOffer',
            HiddenType::class,
        );

        $builder->get('preOffer')->addModelTransformer(
            new CallbackTransformer(
                function($offer) {
                    return $offer instanceof MaterialOfferConst ? $offer->getValue() : $offer;
                },
                function($offer) {
                    return $offer ? new MaterialOfferConst($offer) : null;
                }
            ),
        );

        /**
         * Множественный вариант торгового предложения
         */

        $builder->add(
            'preVariation',
            HiddenType::class,
        );

        $builder->get('preVariation')->addModelTransformer(
            new CallbackTransformer(
                function($variation) {
                    return $variation instanceof MaterialVariationConst ? $variation->getValue() : $variation;
                },
                function($variation) {
                    return $variation ? new MaterialVariationConst($variation) : null;
                }
            ),
        );

        /**
         * Модификация множественного варианта торгового предложения
         */

        $builder->add(
            'preModification',
            HiddenType::class,
        );

        $builder->get('preModification')->addModelTransformer(
            new CallbackTransformer(
                function($modification) {
                    return $modification instanceof MaterialModificationConst ? $modification->getValue() : $modification;
                },
                function($modification) {
                    return $modification ? new MaterialModificationConst($modification) : null;
                }
            ),
        );


        /**
         * Событие на изменение
         */

        $builder->get('preVariation')->addEventListener(
            FormEvents::POST_SUBMIT,
            function(FormEvent $event): void {

                $parent = $event->getForm()->getParent();

                if(!$parent)
                {
                    return;
                }

                $category = $parent->get('category')->getData();
                $material = $parent->get('preProduct')->getData();
                $offer = $parent->get('preOffer')->getData();
                $variation = $parent->get('preVariation')->getData();

                if($category)
                {
                    $this->formProductModifier($event->getForm()->getParent(), $category);
                }

                if($material)
                {
                    $this->formOfferModifier($event->getForm()->getParent(), $material);
                }

                if($offer)
                {
                    $this->formVariationModifier($event->getForm()->getParent(), $offer);
                }

                if($variation)
                {
                    $this->formModificationModifier($event->getForm()->getParent(), $variation);
                }
            },
        );


        // Количество
        $builder->add('preTotal', IntegerType::class, ['required' => false]);

        // Section Collection
        $builder->add('material', CollectionType::class, [
            'entry_type' => Materials\MaterialStockForm::class,
            'entry_options' => ['label' => false],
            'label' => false,
            'by_reference' => false,
            'allow_delete' => true,
            'allow_add' => true,
            'prototype_name' => '__material__',
        ]);

        $builder->add('comment', TextareaType::class, ['required' => false]);

        // Сохранить
        $builder->add(
            'purchase',
            ButtonType::class,
            ['label' => 'Save', 'label_html' => true, 'attr' => ['class' => 'btn-primary']]
        );

    }


    private function formProductModifier(FormInterface $form, ?CategoryMaterialUid $category): void
    {

        /** Получаем список доступной продукции */
        $materialChoice = $this->materialChoice->fetchAllMaterial($category ?: false);


        $form->add(
            'preProduct',
            ChoiceType::class,
            [
                'choices' => $materialChoice,
                'choice_value' => function(?MaterialUid $material) {
                    return $material?->getValue();
                },

                'choice_label' => function(MaterialUid $material) {
                    return $material->getAttr();
                },
                'choice_attr' => function(?MaterialUid $material) {
                    return $material ? [
                        'data-filter' => ' ['.$material->getOption().']',
                        'data-max' => $material->getOption(),
                        'data-name' => $material->getAttr(),
                    ] : [];
                },
                'label' => false,
            ]
        );
    }


    private function formOfferModifier(FormInterface $form, ?MaterialUid $material): void
    {

        if(null === $material)
        {
            return;
        }

        $offer = $this->materialOfferChoice->findByMaterial($material);

        // Если у продукта нет ТП
        if(!$offer->valid())
        {
            $form->add('preOffer', HiddenType::class);
            $form->add('preVariation', HiddenType::class);
            $form->add('preModification', HiddenType::class);
            return;
        }

        $currenOffer = $offer->current();
        $label = $currenOffer->getOption();
        $domain = null;

        if($currenOffer->getProperty())
        {
            /** Если торговое предложение Справочник - ищем домен переводов */
            foreach($this->reference as $reference)
            {
                if($reference->type() === $currenOffer->getProperty())
                {
                    $domain = $reference->domain();
                }
            }
        }

        $form
            ->add('preOffer', ChoiceType::class, [
                'choices' => $offer,
                'choice_value' => function(?MaterialOfferConst $offer) {
                    return $offer?->getValue();
                },
                'choice_label' => function(MaterialOfferConst $offer) {
                    return $offer->getAttr();
                },

                'choice_attr' => function(?MaterialOfferConst $offer) {
                    return $offer?->getCharacteristic() ? ['data-filter' => $offer?->getCharacteristic()] : [];
                },

                'label' => $label,
                'translation_domain' => $domain,
                'placeholder' => sprintf('Выберите %s из списка...', $label),
            ]);
    }

    private function formVariationModifier(FormInterface $form, ?MaterialOfferConst $offer): void
    {

        if(null === $offer)
        {
            return;
        }

        $variations = $this->materialVariationChoice->fetchProductVariationByOfferConst($offer);

        // Если у продукта нет множественных вариантов
        if(!$variations->valid())
        {
            $form->add('preVariation', HiddenType::class);
            $form->add('preModification', HiddenType::class);

            return;
        }

        $currenVariation = $variations->current();
        $label = $currenVariation->getOption();
        $domain = null;

        /** Если множественный вариант Справочник - ищем домен переводов */
        if($currenVariation->getProperty())
        {
            foreach($this->reference as $reference)
            {
                if($reference->type() === $currenVariation->getProperty())
                {
                    $domain = $reference->domain();
                }
            }
        }

        $form
            ->add('preVariation', ChoiceType::class, [
                'choices' => $variations,
                'choice_value' => function(?MaterialVariationConst $variation) {
                    return $variation?->getValue();
                },
                'choice_label' => function(MaterialVariationConst $variation) {
                    return $variation->getAttr();
                },
                'choice_attr' => function(?MaterialVariationConst $variation) {
                    return $variation?->getCharacteristic() ? ['data-filter' => ' ('.$variation?->getCharacteristic().')'] : [];
                },
                'label' => $label,
                'translation_domain' => $domain,
                'placeholder' => sprintf('Выберите %s из списка...', $label),
            ]);
    }

    private function formModificationModifier(FormInterface $form, ?MaterialVariationConst $variation): void
    {
        if(null === $variation)
        {
            return;
        }

        $modifications = $this->modificationChoice->fetchMaterialModificationConstByVariationConst($variation);

        // Если у продукта нет модификаций множественных вариантов
        if(!$modifications->valid())
        {
            $form->add('preModification', HiddenType::class);
            return;
        }

        $currenModifications = $modifications->current();
        $label = $currenModifications->getOption();
        $domain = null;

        /** Если модификация Справочник - ищем домен переводов */
        if($currenModifications->getProperty())
        {
            foreach($this->reference as $reference)
            {
                if($reference->type() === $currenModifications->getProperty())
                {
                    $domain = $reference->domain();
                }
            }
        }

        $form
            ->add('preModification', ChoiceType::class, [
                'choices' => $modifications,
                'choice_value' => function(?MaterialModificationConst $modification) {
                    return $modification?->getValue();
                },
                'choice_label' => function(MaterialModificationConst $modification) {
                    return $modification->getAttr();
                },
                'choice_attr' => function(?MaterialModificationConst $modification) {
                    return $modification?->getCharacteristic() ? ['data-filter' => ' ('.$modification?->getCharacteristic().')'] : [];
                },
                'label' => $label,
                'translation_domain' => $domain,
                'placeholder' => sprintf('Выберите %s из списка...', $label),
            ]);
    }


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => PurchaseMaterialStockDTO::class,
                'method' => 'POST',
                'attr' => ['class' => 'w-100'],
            ]
        );
    }
}
